<?php
/**
 * My Learning Skills — main center content only. Routed via
 * index.php?page=learning-skills (see index.php's $routes map). The
 * header, left sidebar, contextual right sidebar ($rightSidebarContext =
 * 'skills', set by index.php's $sidebarContextByPage map) and footer come
 * from the shell — not from here.
 *
 * Learner-oriented, but frontend-only: opening this route in Mentor mode
 * does not corrupt role state or break the shell (brand guide / prompt
 * rule — no real authorization exists yet), it just shows the same
 * learner-facing content regardless of the currently active role.
 *
 * Remove reuses the one shared modals/delete-confirmation-modal.php via
 * components/skill-card.php's 'learning' variant; assets/js/pages/skills.js
 * hides the specific card and reveals the empty state below once none are
 * left. Frontend-only mock data throughout — no real skill persistence.
 */
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/skill-card.php';
require_once __DIR__ . '/../../components/empty-state.php';

$summaryStats = [
    ['label' => 'Skills Learning', 'value' => '4', 'icon' => 'workspaces'],
    ['label' => 'Sessions Completed', 'value' => '18', 'icon' => 'event_available'],
    ['label' => 'Current Goals', 'value' => '3', 'icon' => 'flag'],
    ['label' => 'Learning Points', 'value' => '412', 'icon' => 'military_tech'],
];

$learningSkills = [
    [
        'name' => 'Python', 'level' => 'Learning', 'progress' => 65,
        'meta' => 'Mentor: Rahim Ahmed · 6 sessions completed',
    ],
    [
        'name' => 'MySQL', 'level' => 'Learning', 'progress' => 45,
        'meta' => 'Mentor: Tanvir Hossain · 4 sessions completed',
    ],
    [
        'name' => 'Data Analysis', 'level' => 'Learning', 'progress' => 30,
        'meta' => 'Mentor: Tanvir Hossain · 2 sessions completed',
    ],
    [
        'name' => 'Public Speaking', 'level' => 'Learning', 'progress' => 55,
        'meta' => 'Mentor: Sara Khan · 5 sessions completed',
    ],
];

foreach ($learningSkills as &$skill) {
    $skill['href'] = ukn_route_href('skill-details') . '&id=1'; // demo context only
    $skill['primaryAction'] = 'Find Mentors';
    $skill['primaryActionHref'] = ukn_route_href('find-mentors') . '&skill=' . urlencode($skill['name']);
}
unset($skill);
?>
<div class="ukn-page-header">
  <div>
    <h1>My Learning Skills</h1>
    <p class="ukn-page-header__sub">Manage the skills you are currently learning.</p>
  </div>
  <a href="<?= htmlspecialchars(ukn_route_href('skills')) ?>" class="btn btn-outline-secondary btn-sm">Explore Skills</a>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($summaryStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>

<?php if ($learningSkills): ?>
  <div class="row g-3" data-skill-list>
    <?php foreach ($learningSkills as $skill): ?>
      <div class="col-md-6"><?php ukn_skill_card($skill, ['variant' => 'learning']); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div<?= $learningSkills ? ' hidden' : '' ?> data-skill-list-empty>
  <?php ukn_empty_state([
      'icon' => 'menu_book',
      'title' => "You haven't added any learning skills yet.",
      'message' => 'Explore the skills directory to find something to learn.',
      'action' => ['label' => 'Explore Skills', 'href' => ukn_route_href('skills')],
  ]); ?>
</div>

<div class="card mt-4">
  <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h2 class="ukn-h4 mb-1">Discover More Skills</h2>
      <p class="ukn-body-sm mb-0">Browse the full skills directory to find your next thing to learn.</p>
    </div>
    <a href="<?= htmlspecialchars(ukn_route_href('skills')) ?>" class="btn btn-primary btn-sm flex-shrink-0">Explore More Skills</a>
  </div>
</div>
