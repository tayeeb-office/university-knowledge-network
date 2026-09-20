<?php
require_once __DIR__ . '/../../components/skill-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$myLearningSkills = ['Python', 'MySQL', 'Data Analysis', 'Public Speaking'];
$myTeachingSkills = ['Python', 'Database Design', 'Data Analysis'];
$categories = ['All', 'Programming', 'Data', 'Design', 'Communication', 'Business', 'Engineering', 'Academic'];
$skills = [
    ['id' => 1, 'name' => 'Python', 'category' => 'Programming', 'mentors' => 124, 'learners' => 340, 'description' => 'A versatile programming language used for software development, automation, data analysis and machine learning.'],
    ['id' => 2, 'name' => 'MySQL', 'category' => 'Data', 'mentors' => 82, 'learners' => 214, 'description' => 'A widely used relational database system for storing and querying structured data.'],
    ['id' => 3, 'name' => 'React', 'category' => 'Programming', 'mentors' => 76, 'learners' => 196, 'description' => 'A JavaScript library for building interactive user interfaces and single-page applications.'],
    ['id' => 4, 'name' => 'UI/UX Design', 'category' => 'Design', 'mentors' => 68, 'learners' => 173, 'description' => 'Designing interfaces and experiences that are functional, accessible and easy to use.'],
    ['id' => 5, 'name' => 'Data Analysis', 'category' => 'Data', 'mentors' => 91, 'learners' => 256, 'description' => 'Turning raw data into insight using spreadsheets, Python and statistical thinking.'],
    ['id' => 6, 'name' => 'Public Speaking', 'category' => 'Communication', 'mentors' => 54, 'learners' => 161, 'description' => 'Structuring and delivering talks and presentations with confidence.'],
    ['id' => 7, 'name' => 'Database Design', 'category' => 'Data', 'mentors' => 63, 'learners' => 147, 'description' => 'Modeling data relationships and normalizing schemas for reliable, efficient systems.'],
    ['id' => 8, 'name' => 'Arduino', 'category' => 'Engineering', 'mentors' => 47, 'learners' => 128, 'description' => 'Building and programming microcontroller projects, from sensors to simple robotics.'],
    ['id' => 9, 'name' => 'Academic Writing', 'category' => 'Academic', 'mentors' => 39, 'learners' => 104, 'description' => 'Structuring essays, reports and citations for university-level coursework.'],
    ['id' => 10, 'name' => 'Digital Marketing', 'category' => 'Business', 'mentors' => 44, 'learners' => 121, 'description' => 'Reaching an audience through social media, content and basic campaign analytics.'],
];
foreach ($skills as &$skill) {
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
<div hidden data-skill-empty>
  <?php ukn_empty_state([
      'icon' => 'search_off',
      'title' => 'No skills found.',
      'message' => 'Try a different search term or category.',
      'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-skills-clear-filters'],
      'dashed' => true,
  ]); ?>
</div>