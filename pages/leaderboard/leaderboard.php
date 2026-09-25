<?php
require_once __DIR__ . '/../../components/leaderboard-row.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/leaderboard.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$viewKeys = ['mentors', 'learners', 'contributors'];
$requestedView = $_GET['view'] ?? '';
$defaultView = is_string($requestedView) && in_array($requestedView, $viewKeys, true)
    ? $requestedView
    : ($isMentor ? 'mentors' : 'learners');
$period = uknLeaderboardPeriodFromRequest();
$periods = uknLeaderboardPeriods();

$learners = [];
$mentors = [];
$contributors = [];
$leaderboardDbError = false;

try {
    // Step 43: every board is a SUM over the point_transactions ledger (see backend/helpers/leaderboard.php).
    $pdo = getDatabaseConnection();
    $learners = uknLeaderboardRows($pdo, 'learning', $period);
    $mentors = uknLeaderboardRows($pdo, 'mentor', $period);
    $contributors = uknLeaderboardRows($pdo, 'community', $period);

    $rankAndDecorate = static function (array $rows, string $pointType, string $activityLabel) {
        $rank = 0;
        foreach ($rows as &$row) {
            $rank++;
            $row['rank'] = $rank;
            $row['points'] = (int) $row['points'];
            $row['category'] = (string) ($row['category'] ?? '');
            $row['pointType'] = $pointType;
            $row['sessions'] = (int) $row['activity'];
            $row['activityLabel'] = $activityLabel;
            $row['isCurrentUser'] = ((int) $row['id'] === UKN_CURRENT_USER_ID);
            $profileRoute = $row['role'] === 'dual' ? 'mentor-profile' : 'learner-profile';
            $row['href'] = $row['isCurrentUser']
                ? ukn_route_href('my-profile')
                : ukn_route_href($profileRoute) . '&id=' . $row['id'];
            $row['rating'] = isset($row['rating']) ? (float) $row['rating'] : null;
            unset($row['id'], $row['role'], $row['activity']);
        }
        unset($row);
        return $rows;
    };
    $learners = $rankAndDecorate($learners, 'Learning Points', 'sessions');
    $mentors = $rankAndDecorate($mentors, 'Mentor Points', 'sessions');
    $contributors = $rankAndDecorate($contributors, 'Community Points', 'contributions');
} catch (Throwable $e) {
    error_log('[UKN leaderboard] ' . $e->getMessage());
    $leaderboardDbError = true;
    $learners = [];
    $mentors = [];
    $contributors = [];
}

$views = [
    'mentors'      => ['label' => 'Top Mentors', 'rows' => $mentors],
    'learners'     => ['label' => 'Top Learners', 'rows' => $learners],
    'contributors' => ['label' => 'Community Contributors', 'rows' => $contributors],
];
$periodHref = static function (string $periodKey) use ($defaultView): string {
    return ukn_route_href('leaderboard') . '&period=' . $periodKey . '&view=' . $defaultView;
};
?>
<div class="ukn-page-header">
  <div>
    <h1>Leaderboard</h1>
    <p class="ukn-page-header__sub">See the most active learners and mentors across the University Knowledge Network.</p>
  </div>
</div>
<?php if ($leaderboardDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load the leaderboard.',
      'message' => 'Something went wrong while loading rankings. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <div class="ukn-tabs-pill" data-leaderboard-views role="group" aria-label="Leaderboard ranking view">
    <?php foreach ($views as $key => $view): ?>
      <button type="button" class="ukn-tab-pill<?= $key === $defaultView ? ' is-active' : '' ?>" data-leaderboard-view-tab="<?= $key ?>" aria-pressed="<?= $key === $defaultView ? 'true' : 'false' ?>"><?= htmlspecialchars($view['label']) ?></button>
    <?php endforeach; ?>
  </div>
  <nav class="ukn-tabs-pill" data-leaderboard-periods aria-label="Leaderboard time period">
    <?php foreach ($periods as $periodKey => $periodLabel): ?>
      <a href="<?= htmlspecialchars($periodHref($periodKey)) ?>" class="ukn-tab-pill<?= $periodKey === $period ? ' is-active' : '' ?>" data-leaderboard-period="<?= $periodKey ?>"<?= $periodKey === $period ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($periodLabel) ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<?php foreach ($views as $key => $view): ?>
  <div<?= $key === $defaultView ? '' : ' hidden' ?> data-leaderboard-view="<?= $key ?>">
    <p class="ukn-body-sm ukn-text-muted mb-3"><?= count($view['rows']) ?> ranked member<?= count($view['rows']) === 1 ? '' : 's' ?> &middot; <?= htmlspecialchars($periods[$period]) ?></p>
    <?php if ($view['rows'] === []): ?>
      <?php ukn_empty_state([
          'icon' => 'leaderboard',
          'title' => $period === 'all' ? 'No points earned yet.' : 'No points earned ' . strtolower($periods[$period]) . ' yet.',
          'message' => 'Rankings appear as members earn points from sessions and community activity.',
          'dashed' => true,
      ]); ?>
    <?php endif; ?>
    <div class="ukn-leaderboard-podium">
      <?php foreach (array_slice($view['rows'], 0, 3) as $row): ukn_leaderboard_row($row, ['variant' => 'podium']); endforeach; ?>
    </div>
    <div>
      <?php foreach ($view['rows'] as $row): ukn_leaderboard_row($row); endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
<?php endif; ?>