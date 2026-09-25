<?php
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/skill-toggle.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/skills.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$mySkills = uknCurrentUserSkills();
$currentSkillIds = $isMentor ? $mySkills['teaching'] : $mySkills['learning']; // id => name
$toggleKind = $isMentor ? 'teaching' : 'learning';
$canToggleSkills = !empty($currentUser['loggedIn']);
// Step 44 limits: the graph stays readable and the embedded JSON small.
$networkMaxSkills = 200;
$networkMentorsPerSkill = 5;

$nodes = [];
$edges = [];
$networkDbError = false;

try {
    $pdo = getDatabaseConnection();

    // Nodes: active skills. Mentor/learner counts come from user_skills (teaching/learning),
    // completed sessions from mentoring_sessions — grouped once, no per-skill queries.
    $nodeStmt = $pdo->query(
        "SELECT s.id, s.name, sc.name AS category, s.description,
                COALESCE(us.mentors, 0) AS mentors, COALESCE(us.learners, 0) AS learners,
                COALESCE(ms.sessions, 0) AS sessions
         FROM skills s
         JOIN skill_categories sc ON sc.id = s.category_id
         LEFT JOIN (SELECT skill_id,
                           SUM(skill_type = 'teaching') AS mentors,
                           SUM(skill_type = 'learning') AS learners
                    FROM user_skills GROUP BY skill_id) us ON us.skill_id = s.id
         LEFT JOIN (SELECT skill_id, COUNT(*) AS sessions
                    FROM mentoring_sessions WHERE status = 'completed' GROUP BY skill_id) ms ON ms.skill_id = s.id
         WHERE s.status = 'active'
         ORDER BY s.id
         LIMIT {$networkMaxSkills}"
    );
    $nodes = array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['description'] = (string) ($row['description'] ?? '');
        $row['mentors'] = (int) $row['mentors'];
        $row['learners'] = (int) $row['learners'];
        $row['sessions'] = (int) $row['sessions'];
        return $row;
    }, $nodeStmt->fetchAll());
    $nodeIds = array_fill_keys(array_column($nodes, 'id'), true);

    // Edges: the curated skill_relations table only, between two active skills.
    $edgeStmt = $pdo->query(
        "SELECT sr.source_skill_id AS source, sr.target_skill_id AS target, sr.strength, sr.reason
         FROM skill_relations sr
         JOIN skills a ON a.id = sr.source_skill_id AND a.status = 'active'
         JOIN skills b ON b.id = sr.target_skill_id AND b.status = 'active'
         ORDER BY sr.id"
    );
    foreach ($edgeStmt->fetchAll() as $row) {
        $row['source'] = (int) $row['source'];
        $row['target'] = (int) $row['target'];
        if (isset($nodeIds[$row['source']], $nodeIds[$row['target']])) {
            $edges[] = $row;
        }
    }

    // Who teaches each skill (user_skills 'teaching', active mentors), top few per skill in
    // one windowed query — shown in the selected-skill panel.
    $mentorStmt = $pdo->query(
        "SELECT skill_id, id, name FROM (
             SELECT us.skill_id, u.id, u.full_name AS name,
                    ROW_NUMBER() OVER (PARTITION BY us.skill_id
                                       ORDER BY us.sessions_count DESC, us.proficiency DESC, u.full_name, u.id) AS rn
             FROM user_skills us
             JOIN users u ON u.id = us.user_id
             WHERE us.skill_type = 'teaching' AND u.status = 'active' AND u.role = 'dual'
         ) ranked
         WHERE rn <= {$networkMentorsPerSkill}
         ORDER BY skill_id, rn"
    );
    $mentorsBySkill = [];
    foreach ($mentorStmt->fetchAll() as $row) {
        $mentorsBySkill[(int) $row['skill_id']][] = [
            'name' => $row['name'],
            'href' => ukn_route_href('mentor-profile') . '&id=' . (int) $row['id'],
        ];
    }
} catch (Throwable $e) {
    error_log('[UKN skill-network] ' . $e->getMessage());
    $networkDbError = true;
    $nodes = [];
    $edges = [];
    $mentorsBySkill = [];
}

$nodesById = [];
foreach ($nodes as $node) {
    $nodesById[$node['id']] = $node['name'];
}
$relatedNames = array_fill_keys(array_column($nodes, 'id'), []);
foreach ($edges as $edge) {
    $relatedNames[$edge['source']][] = $nodesById[$edge['target']];
    $relatedNames[$edge['target']][] = $nodesById[$edge['source']];
}
foreach ($nodes as &$node) {
    $node['related'] = $relatedNames[$node['id']];
    $node['topMentors'] = $mentorsBySkill[$node['id']] ?? [];
    $node['skillDetailsHref'] = ukn_route_href('skill-details') . '&id=' . $node['id'];
    $node['findMentorsHref'] = ukn_route_href('find-mentors') . '&skill=' . rawurlencode(strtolower($node['name']));
    $node['isCurrent'] = isset($currentSkillIds[$node['id']]);
}
unset($node);
// JSON for data-* attributes: hex-escape markup characters, then HTML-escape for the attribute.
$networkJsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
$categories = array_values(array_unique(array_column($nodes, 'category')));
sort($categories);
$joinWithAnd = static function (array $items): string {
    if (count($items) <= 1) {
        return $items[0] ?? '';
    }
    $last = array_pop($items);
    return implode(', ', $items) . ' and ' . $last;
};
?>
<div class="ukn-page-header">
  <div>
    <h1>Skill Network</h1>
    <p class="ukn-page-header__sub">Explore how skills connect and discover related areas to learn or teach.</p>
  </div>
</div>
<?php if ($networkDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load the skill network.',
      'message' => 'Something went wrong while loading skills and their relationships. Please try again shortly.',
  ]); ?>
<?php elseif ($nodes === []): ?>
  <?php ukn_empty_state([
      'icon' => 'hub',
      'title' => 'No skills to display yet.',
      'message' => 'The skill network will appear once skills have been added.',
  ]); ?>
<?php else: ?>
<div class="ukn-network-layout">
  <div class="card ukn-network-graph-card">
    <div class="ukn-network-toolbar">
      <div class="ukn-search ukn-network-toolbar__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="networkSearchInput" class="ukn-visually-hidden">Find a skill in the network</label>
        <input type="search" id="networkSearchInput" class="form-control" placeholder="Find a skill in the network…" data-network-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="networkCategoryFilter">Filter by category</label>
      <select id="networkCategoryFilter" class="form-select form-select-sm w-auto" data-network-category>
        <option value="">All Categories</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-network-clear-filters>Clear Filters</button>
      <div class="d-flex gap-1 ms-sm-auto">
        <button type="button" class="btn-icon btn-icon-sm" data-network-zoom-in aria-label="Zoom in"><span class="ms" aria-hidden="true">add</span></button>
        <button type="button" class="btn-icon btn-icon-sm" data-network-zoom-out aria-label="Zoom out"><span class="ms" aria-hidden="true">remove</span></button>
        <button type="button" class="btn-icon btn-icon-sm" data-network-fit aria-label="Fit network"><span class="ms" aria-hidden="true">fit_screen</span></button>
        <button type="button" class="btn-icon btn-icon-sm" data-network-reset aria-label="Reset network view"><span class="ms" aria-hidden="true">restart_alt</span></button>
      </div>
    </div>

    <p class="ukn-body-sm ukn-text-muted px-3 pt-2 mb-0" data-network-search-feedback role="status" hidden></p>
    <div class="ukn-network-graph-wrap">
      <div
        id="skill-network-graph"
        data-network-nodes="<?= htmlspecialchars((string) json_encode($nodes, $networkJsonFlags)) ?>"
        data-network-edges="<?= htmlspecialchars((string) json_encode($edges, $networkJsonFlags)) ?>"
        data-is-mentor="<?= $isMentor ? '1' : '0' ?>"
        role="img"
        aria-label="Interactive diagram of how skills in the network relate to each other. A full text list of the same skills and relationships follows below."
      ></div>
      <div class="p-3" data-network-error hidden>
        <?php ukn_error_state([
            'title' => 'Unable to display the skill network',
            'message' => 'The interactive graph could not be loaded. Use the skill list below instead.',
        ]); ?>
      </div>
      <div class="ukn-network-legend" aria-hidden="true">
        <div class="ukn-eyebrow mb-1">Legend</div>
        <div><span class="ukn-network-legend__dot"></span> Skill</div>
        <div><span class="ukn-network-legend__dot ukn-network-legend__dot--current"></span> <?= $isMentor ? 'Your Teaching Skill' : 'Your Learning Skill' ?></div>
        <div><span class="ukn-network-legend__line ukn-network-legend__line--strong"></span> Strong link</div>
        <div><span class="ukn-network-legend__line ukn-network-legend__line--related"></span> Related link</div>
      </div>
    </div>
  </div>
  <div class="card ukn-network-panel" data-network-panel>
    <div class="ukn-eyebrow mb-2">Selected Skill</div>
    <div data-network-panel-empty>
      <p class="ukn-body-sm mb-0">Select a skill node in the graph (or from the list below) to see its details, related skills and mentors.</p>
    </div>
    <div data-network-panel-content hidden></div>
  </div>
</div>
<section class="mt-4" aria-labelledby="networkListHeading">
  <h2 id="networkListHeading" class="ukn-h4">Skills in This Network</h2>
  <p class="ukn-body-sm ukn-text-muted">
    A full text list of the same network shown in the graph above — useful on small screens or with a screen reader.
  </p>
  <div class="row g-3" data-network-list>
    <?php foreach ($nodes as $node): ?>
      <div class="col-md-6 col-lg-4" data-network-list-item data-list-node-id="<?= $node['id'] ?>" data-list-node-category="<?= htmlspecialchars($node['category']) ?>">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <h3 class="ukn-h4 mb-0"><a href="<?= htmlspecialchars($node['skillDetailsHref']) ?>"><?= htmlspecialchars($node['name']) ?></a></h3>
              <?php if ($node['isCurrent']): ?>
                <span class="ukn-status ukn-status-accent"><?= $isMentor ? 'Teaching' : 'Learning' ?></span>
              <?php endif; ?>
            </div>
            <div class="ukn-eyebrow mt-1 mb-2"><?= htmlspecialchars($node['category']) ?></div>
            <p class="ukn-body-sm mb-2">
              <?= htmlspecialchars($node['name']) ?> — <?= htmlspecialchars($node['category']) ?>.
              <?php if ($node['related']): ?>
                Related to <?= htmlspecialchars($joinWithAnd($node['related'])) ?>.
              <?php else: ?>
                No directly related skills in this network yet.
              <?php endif; ?>
            </p>
            <div class="ukn-body-sm ukn-text-muted mb-3"><?= $node['mentors'] ?> mentors &middot; <?= $node['learners'] ?> learners</div>
            <div class="d-flex flex-wrap gap-2">
              <a href="<?= htmlspecialchars($node['skillDetailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Skill</a>
              <?php if ($canToggleSkills): ?>
                <div class="d-inline-flex" data-network-toggle><?php ukn_skill_toggle_form((int) $node['id'], $toggleKind, $node['isCurrent']); ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>