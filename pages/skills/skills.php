<?php
require_once __DIR__ . '/../../components/skill-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
// TODO(auth): read from the logged-in user's own user_skills rows once a real session exists.
$myLearningSkills = ['Python', 'MySQL', 'Data Analysis', 'Public Speaking'];
$myTeachingSkills = ['Python', 'Database Design', 'Data Analysis'];

$categories = ['All'];
$skills = [];
$skillsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $categoryStmt = $pdo->query("SELECT name FROM skill_categories WHERE status = 'active' ORDER BY name");
    foreach ($categoryStmt->fetchAll(PDO::FETCH_COLUMN) as $categoryName) {
        $categories[] = $categoryName;
    }

    $skillStmt = $pdo->query(
        "SELECT s.id, s.name, sc.name AS category, s.description,
                (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'teaching') AS mentors,
                (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'learning') AS learners
         FROM skills s
         JOIN skill_categories sc ON sc.id = s.category_id
         WHERE s.status = 'active'
         ORDER BY s.name"
    );
    $skills = $skillStmt->fetchAll();
} catch (Throwable $e) {
    error_log('[UKN skills] ' . $e->getMessage());
    $skillsDbError = true;
}

foreach ($skills as &$skill) {
    $skill['mentors'] = (int) $skill['mentors'];
    $skill['learners'] = (int) $skill['learners'];
    $skill['href'] = ukn_route_href('skill-details') . '&id=' . $skill['id'];
    if ($isMentor) {
        $skill['teachingState'] = in_array($skill['name'], $myTeachingSkills, true) ? 'added' : 'add';
    } else {
        $skill['learningState'] = in_array($skill['name'], $myLearningSkills, true) ? 'added' : 'add';
    }
}
unset($skill);
?>
<div class="ukn-page-header">
  <div>
    <h1>Explore Skills</h1>
    <p class="ukn-page-header__sub">Discover skills to learn, teach and discuss with the university community.</p>
  </div>
</div>
<div class="ukn-search mb-3">
  <span class="ms" aria-hidden="true">search</span>
  <label for="uknSkillsSearch" class="ukn-visually-hidden">Search skills</label>
  <input type="search" id="uknSkillsSearch" class="form-control" placeholder="Search skills...">
</div>
<div class="ukn-tabs-pill mb-4" data-skill-categories role="group" aria-label="Filter skills by category">
  <?php foreach ($categories as $category): ?>
    <button
      type="button"
      class="ukn-tab-pill<?= $category === 'All' ? ' is-active' : '' ?>"
      data-skill-category-filter="<?= htmlspecialchars($category) ?>"
      aria-pressed="<?= $category === 'All' ? 'true' : 'false' ?>"
    ><?= htmlspecialchars($category) ?></button>
  <?php endforeach; ?>
</div>
<?php if ($skillsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load skills.',
      'message' => 'Something went wrong while loading the skill directory. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="row g-3" data-skill-grid>
  <?php foreach ($skills as $skill): ?>
    <div
      class="col-md-6 col-lg-4"
      data-skill-item
      data-skill-category="<?= htmlspecialchars($skill['category']) ?>"
      data-skill-search="<?= htmlspecialchars(strtolower($skill['name'] . ' ' . $skill['category'] . ' ' . $skill['description'])) ?>"
    >
      <?php ukn_skill_card($skill); ?>
    </div>
  <?php endforeach; ?>
</div>
<div<?= $skills ? ' hidden' : '' ?> data-skill-empty>
  <?php ukn_empty_state([
      'icon' => 'search_off',
      'title' => 'No skills found.',
      'message' => 'Try a different search term or category.',
      'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-skills-clear-filters'],
      'dashed' => true,
  ]); ?>
</div>
<?php endif; ?>