<?php
/**
 * Leaderboard — main center content only. Routed via
 * index.php?page=leaderboard (see index.php's $routes map). The header,
 * left sidebar, contextual right sidebar (includes/right-sidebar.php's
 * existing 'leaderboard' case) and footer come from the shell.
 *
 * docs/ui's r.leaderboard block shows THREE ranking views — Top Mentors /
 * Top Learners / Community Contributors — each with its own Top 3 podium
 * and a full ranked list, plus a (purely cosmetic, per this prompt's own
 * "do not pretend real historical calculations" instruction) This Week /
 * This Month / All Time pill row. All three views are rendered once below
 * and switched client-side by assets/js/pages/leaderboard.js — no
 * separate page/route per view.
 *
 * Every ranked row/card reuses the ONE components/leaderboard-row.php
 * component (its default 'row' variant for the list, its 'podium' variant
 * for the Top 3 cards) — no separate podium/ranking component files.
 *
 * Frontend-only mock ranking throughout — no real point aggregation, no
 * database, no live recalculation. Point values are kept consistent with
 * the already-established Points/Profile/Find Mentors/Ratings pages
 * (Rahim Ahmed 520 pts/4.9/127 sessions, Nabila Rahman 412 Learning
 * Points and 520 Mentor Points/4.8, Farhan Kabir/Sara Khan/Ayesha Rahman/
 * Hasan Mahmud/Nusrat Jahan matching their find-mentors.php and
 * mentor-profile.php figures, Imran Chowdhury's 318 Learning Points
 * matching learner-profile.php) rather than inventing new contradicting
 * numbers for the same people.
 */
require_once __DIR__ . '/../../components/leaderboard-row.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$defaultView = $isMentor ? 'mentors' : 'learners';

$learners = [
    ['rank' => 1, 'name' => 'Nabila Rahman', 'initials' => 'NR', 'href' => ukn_route_href('my-profile'), 'category' => 'Computer Science', 'points' => 412, 'pointType' => 'Learning Points', 'sessions' => 18, 'rankChange' => ['direction' => 'up', 'amount' => 1], 'isCurrentUser' => true],
    ['rank' => 2, 'name' => 'Mahi Noor', 'initials' => 'MN', 'href' => ukn_route_href('learner-profile'), 'category' => 'Computer Science', 'points' => 356, 'pointType' => 'Learning Points', 'sessions' => 16, 'rankChange' => ['direction' => 'up', 'amount' => 2]],
    ['rank' => 3, 'name' => 'Tanvir Hossain', 'initials' => 'TH', 'href' => ukn_route_href('learner-profile'), 'category' => 'Electrical Engineering', 'points' => 331, 'pointType' => 'Learning Points', 'sessions' => 15, 'rankChange' => ['direction' => 'none']],
    ['rank' => 4, 'name' => 'Imran Chowdhury', 'initials' => 'IC', 'href' => ukn_route_href('learner-profile') . '&id=1', 'category' => 'English', 'points' => 318, 'pointType' => 'Learning Points', 'sessions' => 14, 'rankChange' => ['direction' => 'down', 'amount' => 1]],
    ['rank' => 5, 'name' => 'Maliha Islam', 'initials' => 'MI', 'href' => ukn_route_href('learner-profile'), 'category' => 'Business Administration', 'points' => 309, 'pointType' => 'Learning Points', 'sessions' => 14, 'rankChange' => ['direction' => 'up', 'amount' => 1]],
    ['rank' => 6, 'name' => 'Sabrina Ali', 'initials' => 'SA', 'href' => ukn_route_href('learner-profile'), 'category' => 'English', 'points' => 264, 'pointType' => 'Learning Points', 'sessions' => 12, 'rankChange' => ['direction' => 'none']],
    ['rank' => 7, 'name' => 'Adil Hasan', 'initials' => 'AH', 'href' => ukn_route_href('learner-profile'), 'category' => 'Electrical Engineering', 'points' => 241, 'pointType' => 'Learning Points', 'sessions' => 10, 'rankChange' => ['direction' => 'down', 'amount' => 2]],
];

$mentors = [
    ['rank' => 1, 'name' => 'Rahim Ahmed', 'initials' => 'RA', 'href' => ukn_route_href('mentor-profile') . '&id=2', 'category' => 'Computer Science', 'points' => 520, 'pointType' => 'Mentor Points', 'rating' => 4.9, 'sessions' => 127, 'rankChange' => ['direction' => 'none']],
    ['rank' => 2, 'name' => 'Nabila Rahman', 'initials' => 'NR', 'href' => ukn_route_href('my-profile'), 'category' => 'Computer Science', 'points' => 520, 'pointType' => 'Mentor Points', 'rating' => 4.8, 'sessions' => 27, 'rankChange' => ['direction' => 'up', 'amount' => 1], 'isCurrentUser' => true],
    ['rank' => 3, 'name' => 'Farhan Kabir', 'initials' => 'FK', 'href' => ukn_route_href('mentor-profile'), 'category' => 'Computer Science', 'points' => 462, 'pointType' => 'Mentor Points', 'rating' => 4.9, 'sessions' => 103, 'rankChange' => ['direction' => 'down', 'amount' => 1]],
    ['rank' => 4, 'name' => 'Sara Khan', 'initials' => 'SK', 'href' => ukn_route_href('mentor-profile'), 'category' => 'Business Administration', 'points' => 410, 'pointType' => 'Mentor Points', 'rating' => 4.8, 'sessions' => 84, 'rankChange' => ['direction' => 'up', 'amount' => 2]],
    ['rank' => 5, 'name' => 'Ayesha Rahman', 'initials' => 'AR', 'href' => ukn_route_href('mentor-profile'), 'category' => 'Computer Science', 'points' => 368, 'pointType' => 'Mentor Points', 'rating' => 4.7, 'sessions' => 71, 'rankChange' => ['direction' => 'none']],
    ['rank' => 6, 'name' => 'Hasan Mahmud', 'initials' => 'HM', 'href' => ukn_route_href('mentor-profile') . '&id=3', 'category' => 'Electrical Engineering', 'points' => 365, 'pointType' => 'Mentor Points', 'rating' => 4.7, 'sessions' => 52, 'rankChange' => ['direction' => 'down', 'amount' => 1]],
    ['rank' => 7, 'name' => 'Nusrat Jahan', 'initials' => 'NJ', 'href' => ukn_route_href('mentor-profile'), 'category' => 'English', 'points' => 240, 'pointType' => 'Mentor Points', 'rating' => 4.6, 'sessions' => 28, 'rankChange' => ['direction' => 'up', 'amount' => 1]],
];

$contributors = [
    ['rank' => 1, 'name' => 'Rahim Ahmed', 'initials' => 'RA', 'href' => ukn_route_href('mentor-profile') . '&id=2', 'category' => 'Computer Science', 'points' => 890, 'pointType' => 'Community Points', 'sessions' => 46, 'activityLabel' => 'posts', 'rankChange' => ['direction' => 'up', 'amount' => 2]],
    ['rank' => 2, 'name' => 'Nabila Rahman', 'initials' => 'NR', 'href' => ukn_route_href('my-profile'), 'category' => 'Computer Science', 'points' => 745, 'pointType' => 'Community Points', 'sessions' => 38, 'activityLabel' => 'posts', 'rankChange' => ['direction' => 'up', 'amount' => 1], 'isCurrentUser' => true],
    ['rank' => 3, 'name' => 'Hasan Mahmud', 'initials' => 'HM', 'href' => ukn_route_href('mentor-profile') . '&id=3', 'category' => 'Electrical Engineering', 'points' => 612, 'pointType' => 'Community Points', 'sessions' => 31, 'activityLabel' => 'posts', 'rankChange' => ['direction' => 'none']],
    ['rank' => 4, 'name' => 'Sara Khan', 'initials' => 'SK', 'href' => ukn_route_href('mentor-profile'), 'category' => 'Business Administration', 'points' => 480, 'pointType' => 'Community Points', 'sessions' => 24, 'activityLabel' => 'posts', 'rankChange' => ['direction' => 'down', 'amount' => 1]],
    ['rank' => 5, 'name' => 'Ayesha Rahman', 'initials' => 'AR', 'href' => ukn_route_href('mentor-profile'), 'category' => 'Computer Science', 'points' => 410, 'pointType' => 'Community Points', 'sessions' => 19, 'activityLabel' => 'posts', 'rankChange' => ['direction' => 'up', 'amount' => 1]],
    ['rank' => 6, 'name' => 'Imran Chowdhury', 'initials' => 'IC', 'href' => ukn_route_href('learner-profile') . '&id=1', 'category' => 'English', 'points' => 320, 'pointType' => 'Community Points', 'sessions' => 15, 'activityLabel' => 'posts', 'rankChange' => ['direction' => 'none']],
    ['rank' => 7, 'name' => 'Tanvir Hossain', 'initials' => 'TH', 'href' => ukn_route_href('learner-profile'), 'category' => 'Electrical Engineering', 'points' => 265, 'pointType' => 'Community Points', 'sessions' => 12, 'activityLabel' => 'posts', 'rankChange' => ['direction' => 'down', 'amount' => 2]],
];

$views = [
    'mentors'      => ['label' => 'Top Mentors', 'rows' => $mentors],
    'learners'     => ['label' => 'Top Learners', 'rows' => $learners],
    'contributors' => ['label' => 'Community Contributors', 'rows' => $contributors],
];
?>
<div class="ukn-page-header">
  <div>
    <h1>Leaderboard</h1>
    <p class="ukn-page-header__sub">See the most active learners and mentors across the University Knowledge Network.</p>
  </div>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <div class="ukn-tabs-pill" data-leaderboard-views role="group" aria-label="Leaderboard ranking view">
    <?php foreach ($views as $key => $view): ?>
      <button type="button" class="ukn-tab-pill<?= $key === $defaultView ? ' is-active' : '' ?>" data-leaderboard-view-tab="<?= $key ?>" aria-pressed="<?= $key === $defaultView ? 'true' : 'false' ?>"><?= htmlspecialchars($view['label']) ?></button>
    <?php endforeach; ?>
  </div>

  <div class="ukn-tabs-pill" data-leaderboard-periods role="group" aria-label="Leaderboard time period">
    <button type="button" class="ukn-tab-pill" data-leaderboard-period="week" aria-pressed="false">This Week</button>
    <button type="button" class="ukn-tab-pill" data-leaderboard-period="month" aria-pressed="false">This Month</button>
    <button type="button" class="ukn-tab-pill is-active" data-leaderboard-period="all" aria-pressed="true">All Time</button>
  </div>
</div>

<?php foreach ($views as $key => $view): ?>
  <div<?= $key === $defaultView ? '' : ' hidden' ?> data-leaderboard-view="<?= $key ?>">
    <p class="ukn-body-sm ukn-text-muted mb-3"><?= count($view['rows']) ?> ranked members</p>

    <div class="ukn-leaderboard-podium">
      <?php foreach (array_slice($view['rows'], 0, 3) as $row): ukn_leaderboard_row($row, ['variant' => 'podium']); endforeach; ?>
    </div>

    <div>
      <?php foreach ($view['rows'] as $row): ukn_leaderboard_row($row); endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
