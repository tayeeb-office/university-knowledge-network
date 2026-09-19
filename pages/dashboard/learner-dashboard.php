<?php
/**
 * Learner Dashboard — main center content only. Routed via
 * index.php?page=learner-dashboard (see index.php's $routes map). The
 * header, left sidebar, contextual right sidebar ($rightSidebarContext =
 * 'dashboard-learner', set by index.php's $sidebarContextByPage map) and
 * footer come from the shell — not from here.
 *
 * The right sidebar already covers the compact Learning Summary /
 * Learning Points glance (includes/right-sidebar.php), so this file
 * deliberately does NOT repeat a Point Activity list or a full Recent
 * Activity feed here — that would just be the same numbers twice. What
 * it adds instead is what the sidebar can't fit: a fuller goals/sessions/
 * recommendations view and the one chart worth having.
 *
 * Frontend-only mock data throughout — no real stats calculation, no
 * database, no backend of any kind.
 */
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/goal-card.php';
require_once __DIR__ . '/../../components/session-card.php';
require_once __DIR__ . '/../../components/mentor-card.php';

$learnerStats = [
    ['label' => 'Learning Points', 'value' => '412', 'icon' => 'military_tech', 'trend' => '+64 this month'],
    ['label' => 'Completed Sessions', 'value' => '18', 'icon' => 'event_available'],
    ['label' => 'Upcoming Sessions', 'value' => '2', 'icon' => 'event_upcoming'],
    ['label' => 'Skills Learning', 'value' => '4', 'icon' => 'workspaces'],
];

$learnerGoals = [
    ['title' => 'Learn Python for Data Analysis', 'skill' => 'Python', 'progress' => 65, 'targetDate' => 'December 2026'],
    ['title' => 'Improve Database Design Skills', 'skill' => 'DBMS', 'progress' => 40, 'targetDate' => 'November 2026'],
];

$learnerSessions = [
    [
        'counterparty' => 'Rahim Ahmed', 'counterpartyInitials' => 'RA', 'skill' => 'Python',
        'day' => '16', 'month' => 'Sep', 'time' => 'Tue 7:30pm', 'duration' => '60 min',
        'status' => 'upcoming', 'detailsHref' => ukn_route_href('session-details'),
    ],
    [
        'counterparty' => 'Sara Khan', 'counterpartyInitials' => 'SK', 'skill' => 'Public Speaking',
        'day' => '20', 'month' => 'Sep', 'time' => 'Sat 11:00am', 'duration' => '45 min',
        'status' => 'upcoming', 'detailsHref' => ukn_route_href('session-details'),
    ],
];

$recommendedMentors = [
    [
        'name' => 'Rahim Ahmed', 'initials' => 'RA', 'department' => 'Computer Science', 'primarySkill' => 'Python',
        'rating' => 4.9, 'sessions' => 127, 'points' => 520, 'match' => 98, 'matchLabel' => 'Best Match',
        'profileHref' => ukn_route_href('mentor-profile'),
    ],
    [
        'name' => 'Hasan Mahmud', 'initials' => 'HM', 'department' => 'Electrical Engineering', 'primarySkill' => 'Arduino',
        'rating' => 4.7, 'sessions' => 52, 'points' => 365, 'match' => 92, 'matchLabel' => 'Good Match',
        'profileHref' => ukn_route_href('mentor-profile'),
    ],
    [
        'name' => 'Sara Khan', 'initials' => 'SK', 'department' => 'Business Administration', 'primarySkill' => 'Public Speaking',
        'rating' => 4.8, 'sessions' => 47, 'points' => 410, 'match' => 89, 'matchLabel' => 'Good Match',
        'profileHref' => ukn_route_href('mentor-profile'),
    ],
];

$learningSkills = ['Python', 'MySQL', 'Data Analysis', 'Public Speaking'];

$sessionsChartLabels = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
$sessionsChartValues = [4, 6, 5, 8, 7, 10];
?>
<div class="ukn-page-header">
  <div>
    <h1>Learner Dashboard</h1>
    <p class="ukn-page-header__sub">Track your learning progress, goals and upcoming sessions.</p>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($learnerStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <h2 class="ukn-h4 mb-0">Learning Progress</h2>
          <span class="ukn-body-sm">Sessions completed per month</span>
        </div>
        <div class="ukn-chart-wrap">
          <canvas
            data-chart="line"
            data-chart-label="Sessions"
            data-chart-labels="<?= htmlspecialchars(json_encode($sessionsChartLabels)) ?>"
            data-chart-values="<?= htmlspecialchars(json_encode($sessionsChartValues)) ?>"
            role="img"
            aria-label="Sessions completed per month: Apr 4, May 6, Jun 5, Jul 8, Aug 7, Sep 10"
          ></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="ukn-h4 mb-0">Current Goals</h2>
      <a href="<?= htmlspecialchars(ukn_route_href('learning-goals')) ?>" class="ukn-body-sm">View All Goals</a>
    </div>
    <?php foreach ($learnerGoals as $goal): ukn_goal_card($goal); endforeach; ?>
  </div>
</div>

<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Upcoming Sessions</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('sessions')) ?>" class="ukn-body-sm">View All Sessions</a>
  </div>
  <?php foreach ($learnerSessions as $session): ukn_session_card($session); endforeach; ?>
</div>

<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Recommended Mentors</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('recommendations')) ?>" class="ukn-body-sm">View Recommendations</a>
  </div>
  <div class="row g-3">
    <?php foreach ($recommendedMentors as $mentor): ?>
      <div class="col-md-6 col-lg-4"><?php ukn_mentor_card($mentor, ['variant' => 'recommendation']); ?></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="ukn-h4 mb-0">Learning Skills</h2>
      <a href="<?= htmlspecialchars(ukn_route_href('learning-skills')) ?>" class="ukn-body-sm">Manage Learning Skills</a>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($learningSkills as $skill): ?>
        <span class="ukn-tag-skill"><?= htmlspecialchars($skill) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
