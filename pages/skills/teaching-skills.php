<?php
/**
 * My Teaching Skills — main center content only. Routed via
 * index.php?page=teaching-skills (see index.php's $routes map). The
 * header, left sidebar, contextual right sidebar ($rightSidebarContext =
 * 'skills', set by index.php's $sidebarContextByPage map) and footer come
 * from the shell — not from here.
 *
 * Mentor-oriented, but frontend-only: opening this route in Learner mode
 * does not corrupt role state or break the shell (no real authorization
 * exists yet) — it just shows the same mentor-facing content regardless
 * of the currently active role.
 *
 * "Add Teaching Skill" deliberately reuses the Skills directory's own
 * Add to Teaching flow (Explore Skills below) instead of a second add-skill
 * form — see components/skill-card.php's directory variant. Remove reuses
 * the one shared modals/delete-confirmation-modal.php via skill-card.php's
 * 'teaching' variant; assets/js/pages/skills.js hides the specific card
 * and reveals the empty state below once none are left. Frontend-only
 * mock data throughout — no real skill persistence.
 */
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/skill-card.php';
require_once __DIR__ . '/../../components/empty-state.php';

$summaryStats = [
    ['label' => 'Teaching Skills', 'value' => '3', 'icon' => 'school'],
    ['label' => 'Completed Sessions', 'value' => '27', 'icon' => 'event_available'],
    ['label' => 'Mentor Points', 'value' => '520', 'icon' => 'military_tech'],
    ['label' => 'Average Rating', 'value' => '4.8', 'icon' => 'star'],
];

$teachingSkills = [
    ['name' => 'Python', 'level' => 'Advanced', 'sessions' => 12, 'rating' => 4.9],
    ['name' => 'Database Design', 'level' => 'Advanced', 'sessions' => 9, 'rating' => 4.8],
    ['name' => 'Data Analysis', 'level' => 'Intermediate', 'sessions' => 6, 'rating' => 4.7],
];

foreach ($teachingSkills as &$skill) {
    $skill['meta'] = $skill['sessions'] . ' sessions taught · ★ ' . $skill['rating'];
    $skill['href'] = ukn_route_href('skill-details') . '&id=1'; // demo context only
    $skill['primaryAction'] = 'Manage Availability';
    $skill['primaryActionHref'] = ukn_route_href('availability');
}
unset($skill);
?>
<div class="ukn-page-header">
  <div>
    <h1>My Teaching Skills</h1>
    <p class="ukn-page-header__sub">Manage the skills you can teach to other students.</p>
  </div>
  <a href="<?= htmlspecialchars(ukn_route_href('skills')) ?>" class="btn btn-outline-secondary btn-sm">Explore Skills</a>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($summaryStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>

<?php if ($teachingSkills): ?>
  <div class="row g-3" data-skill-list>
    <?php foreach ($teachingSkills as $skill): ?>
      <div class="col-md-6"><?php ukn_skill_card($skill, ['variant' => 'teaching']); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div<?= $teachingSkills ? ' hidden' : '' ?> data-skill-list-empty>
  <?php ukn_empty_state([
      'icon' => 'school',
      'title' => "You haven't added any teaching skills yet.",
      'message' => 'Explore the skills directory to find a skill you can teach.',
      'action' => ['label' => 'Explore Skills', 'href' => ukn_route_href('skills')],
  ]); ?>
</div>
