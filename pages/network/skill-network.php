<?php
/**
 * Skill Network — main center content only. Routed via
 * index.php?page=skill-network (see index.php's $routes map). The
 * header, left sidebar and footer come from the shell; this route is
 * deliberately absent from index.php's $sidebarContextByPage map (no
 * app-level right sidebar) — docs/ui's own r.skillNetwork block gives the
 * graph a fixed-width "Selected node" panel INSIDE the main content
 * instead of using the app shell's contextual right sidebar, and a
 * network diagram benefits from the extra width anyway.
 *
 * The graph itself (Cytoscape.js — already the project's approved
 * visualization library alongside Chart.js, see
 * docs/ui/.../uploads/UKN-FRONTEND-STRUCTURE.md) is entirely built and
 * driven by assets/js/pages/network.js; this file only serves the mock
 * node/edge data as JSON on data-* attributes (the exact same convention
 * pages/dashboard/*.php already use to feed assets/js/pages/dashboard.js's
 * Chart.js canvases — see data-chart-labels/data-chart-values there).
 *
 * Primary entities are SKILLS ONLY — no Mentor/Learner person-nodes (this
 * is deliberately not a social graph, unlike docs/ui's own rough
 * wireframe legend, which sketched Mentor/Learner dot kinds; this
 * implementation scopes the network to skill-to-skill relationships only,
 * per this prompt's explicit "primary entities are SKILLS" instruction).
 *
 * Node mentor/learner counts and descriptions for the 10 already-
 * established skills are copied verbatim from pages/skills/skill-details.php
 * (same ids 1-10) so this page never contradicts that one. JavaScript,
 * Presentation Skills, Machine Learning and Embedded Systems are network-
 * only additions (ids 11-14) — already-referenced skills elsewhere
 * (mentor-card.php examples, Hasan Mahmud's Arduino/Embedded Systems
 * teaching pair, Rahim Ahmed's Machine Learning) that skill-details.php
 * itself has no dedicated record for; their skill-details links safely
 * fall back to that page's own existing "unrecognized id -> id 1" mock
 * behavior, the same fallback convention used throughout this project.
 *
 * Frontend-only mock data throughout: no database, no backend graph
 * generation, no real similarity/recommendation calculation. Edge
 * "strength" and "reason" text are hand-authored mock content, not an
 * algorithm's output.
 */
require_once __DIR__ . '/../../components/error-state.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';

// Same mock lists already established in pages/skills/learning-skills.php
// and pages/skills/teaching-skills.php — not a second skill-state system.
$learningSkills = ['Python', 'MySQL', 'Data Analysis'];
$teachingSkills = ['Python', 'Database Design', 'Data Analysis'];
$currentSkills = $isMentor ? $teachingSkills : $learningSkills;

$nodes = [
    ['id' => 1, 'name' => 'Python', 'category' => 'Programming', 'mentors' => 124, 'learners' => 340, 'sessions' => 1248, 'description' => 'A versatile programming language used for software development, automation, data analysis and machine learning.'],
    ['id' => 2, 'name' => 'MySQL', 'category' => 'Data', 'mentors' => 82, 'learners' => 214, 'sessions' => 640, 'description' => 'A widely used relational database system for storing and querying structured data.'],
    ['id' => 3, 'name' => 'React', 'category' => 'Programming', 'mentors' => 76, 'learners' => 196, 'sessions' => 590, 'description' => 'A JavaScript library for building interactive user interfaces and single-page applications.'],
    ['id' => 4, 'name' => 'UI/UX Design', 'category' => 'Design', 'mentors' => 68, 'learners' => 173, 'sessions' => 480, 'description' => 'Designing interfaces and experiences that are functional, accessible and easy to use.'],
    ['id' => 5, 'name' => 'Data Analysis', 'category' => 'Data', 'mentors' => 91, 'learners' => 256, 'sessions' => 710, 'description' => 'Turning raw data into insight using spreadsheets, Python and statistical thinking.'],
    ['id' => 6, 'name' => 'Public Speaking', 'category' => 'Communication', 'mentors' => 54, 'learners' => 161, 'sessions' => 390, 'description' => 'Structuring and delivering talks and presentations with confidence.'],
    ['id' => 7, 'name' => 'Database Design', 'category' => 'Data', 'mentors' => 63, 'learners' => 147, 'sessions' => 460, 'description' => 'Modeling data relationships and normalizing schemas for reliable, efficient systems.'],
    ['id' => 8, 'name' => 'Arduino', 'category' => 'Engineering', 'mentors' => 47, 'learners' => 128, 'sessions' => 310, 'description' => 'Building and programming microcontroller projects, from sensors to simple robotics.'],
    ['id' => 9, 'name' => 'Academic Writing', 'category' => 'Academic', 'mentors' => 39, 'learners' => 104, 'sessions' => 260, 'description' => 'Structuring essays, reports and citations for university-level coursework.'],
    ['id' => 10, 'name' => 'Digital Marketing', 'category' => 'Business', 'mentors' => 44, 'learners' => 121, 'sessions' => 300, 'description' => 'Reaching an audience through social media, content and basic campaign analytics.'],
    ['id' => 11, 'name' => 'JavaScript', 'category' => 'Programming', 'mentors' => 58, 'learners' => 162, 'sessions' => 420, 'description' => "The core scripting language of the web, used alongside React for interactive interfaces."],
    ['id' => 12, 'name' => 'Presentation Skills', 'category' => 'Communication', 'mentors' => 31, 'learners' => 89, 'sessions' => 210, 'description' => "Turning a talk's structure into a confident, well-paced delivery in front of an audience."],
    ['id' => 13, 'name' => 'Machine Learning', 'category' => 'Data', 'mentors' => 35, 'learners' => 97, 'sessions' => 240, 'description' => 'Using data and Python to build models that recognize patterns and make predictions.'],
    ['id' => 14, 'name' => 'Embedded Systems', 'category' => 'Engineering', 'mentors' => 22, 'learners' => 58, 'sessions' => 150, 'description' => 'Programming the hardware side of microcontroller projects — sensors, timing and low-level control.'],
];

$edges = [
    ['source' => 1, 'target' => 5, 'strength' => 'Strong', 'reason' => 'Python is the most common language used for data analysis workflows on the network.'],
    ['source' => 2, 'target' => 7, 'strength' => 'Strong', 'reason' => 'Database Design sessions are usually taught and practiced directly in MySQL.'],
    ['source' => 3, 'target' => 11, 'strength' => 'Strong', 'reason' => 'React is a JavaScript library — mentors expect basic JavaScript before starting React.'],
    ['source' => 4, 'target' => 3, 'strength' => 'Medium', 'reason' => 'Many UI/UX mentees go on to implement their designs as real React interfaces.'],
    ['source' => 6, 'target' => 12, 'strength' => 'Strong', 'reason' => 'Presentation Skills sessions build directly on Public Speaking fundamentals.'],
    ['source' => 8, 'target' => 14, 'strength' => 'Strong', 'reason' => 'Arduino projects are the most common hands-on introduction to embedded systems.'],
    ['source' => 1, 'target' => 2, 'strength' => 'Medium', 'reason' => 'Python is frequently used to connect to and query MySQL databases.'],
    ['source' => 5, 'target' => 2, 'strength' => 'Medium', 'reason' => 'Data analysis sessions often pull data directly from a MySQL database.'],
    ['source' => 5, 'target' => 7, 'strength' => 'Medium', 'reason' => 'Well-modeled data makes analysis sessions more straightforward.'],
    ['source' => 10, 'target' => 6, 'strength' => 'Related', 'reason' => 'Presenting campaign results is a common Digital Marketing session topic.'],
    ['source' => 9, 'target' => 12, 'strength' => 'Medium', 'reason' => 'Both skills focus on structuring an argument clearly for an audience.'],
    ['source' => 8, 'target' => 1, 'strength' => 'Related', 'reason' => 'Some Arduino projects are scripted or logged using Python.'],
    ['source' => 1, 'target' => 13, 'strength' => 'Related', 'reason' => 'Machine Learning sessions build on existing Python fluency.'],
];

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
    $node['skillDetailsHref'] = ukn_route_href('skill-details') . '&id=' . $node['id'];
    $node['findMentorsHref'] = ukn_route_href('find-mentors') . '&skill=' . rawurlencode(strtolower($node['name']));
    $node['isCurrent'] = in_array($node['name'], $currentSkills, true);
}
unset($node);

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
        data-network-nodes="<?= htmlspecialchars(json_encode($nodes)) ?>"
        data-network-edges="<?= htmlspecialchars(json_encode($edges)) ?>"
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
            <a href="<?= htmlspecialchars($node['skillDetailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Skill</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
