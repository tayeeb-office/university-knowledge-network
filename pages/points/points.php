<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/point-transaction.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';


$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$heading = $isMentor ? 'Mentor Points' : 'Points';
$subheading = $isMentor
    ? 'Track the points you earn through mentoring and teaching activity.'
    : 'Track the points you earn through learning and community activity.';
$statOrder = $isMentor ? ['mentor', 'learning', 'total', 'growth'] : ['learning', 'mentor', 'total', 'growth'];
$howYouEarn = $isMentor
    ? ['Complete mentoring sessions', 'Receive positive ratings from learners', 'Help learners reach their learning goals']
    : ['Complete learning sessions', 'Complete learning goals', 'Participate helpfully in the community'];
$filterOptions = $isMentor
    ? ['all' => 'All', 'session' => 'Sessions', 'rating' => 'Ratings']
    : ['all' => 'All', 'session' => 'Sessions', 'goal' => 'Goals', 'community' => 'Community'];

$pointStats = [];
$transactions = [];
$pointsDbError = false;
$activityIcons = [
    'session' => 'event_available', 'rating' => 'star', 'goal' => 'flag',
    'community' => 'forum', 'penalty' => 'cancel',
];

try {
    $pdo = getDatabaseConnection();

    $userStmt = $pdo->prepare("SELECT learning_points, mentor_points FROM users WHERE id = ?");
    $userStmt->execute([UKN_CURRENT_USER_ID]);
    $user = $userStmt->fetch();
    $learningPoints = (int) ($user['learning_points'] ?? 0);
    $mentorPoints = (int) ($user['mentor_points'] ?? 0);

    $monthSumStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM point_transactions
         WHERE user_id = ? AND point_type = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    );
    $monthSumStmt->execute([UKN_CURRENT_USER_ID, 'learning']);
    $learningMonth = (int) $monthSumStmt->fetchColumn();
    $monthSumStmt->execute([UKN_CURRENT_USER_ID, 'mentor']);
    $mentorMonth = (int) $monthSumStmt->fetchColumn();

    $rankStmt = $pdo->prepare(
        "SELECT COUNT(*) + 1 FROM users
         WHERE status = 'active' AND (learning_points + mentor_points) >
               (SELECT learning_points + mentor_points FROM users WHERE id = ?)"
    );
    $rankStmt->execute([UKN_CURRENT_USER_ID]);
    $rank = (int) $rankStmt->fetchColumn();

    $totalUsersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $totalUsers = (int) $totalUsersStmt->fetchColumn();

    // "vs. last month" growth: compares this month's total earned points to last month's.
    // Depends on the seed data's fictional 2026 dates vs. the real system clock, same
    // caveat as My Profile's monthly trend (see DATABASE_READ_INTEGRATION_PLAN.md §2.7).
    $rangeStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM point_transactions
         WHERE user_id = ? AND created_at >= ? AND created_at < ?"
    );
    $thisMonthStart = date('Y-m-01');
    $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
    $rangeStmt->execute([UKN_CURRENT_USER_ID, $thisMonthStart, date('Y-m-d', strtotime('+1 month', strtotime($thisMonthStart)))]);
    $thisMonthTotal = (int) $rangeStmt->fetchColumn();
    $rangeStmt->execute([UKN_CURRENT_USER_ID, $lastMonthStart, $thisMonthStart]);
    $lastMonthTotal = (int) $rangeStmt->fetchColumn();
    if ($lastMonthTotal !== 0) {
        $growthPct = (int) round((($thisMonthTotal - $lastMonthTotal) / abs($lastMonthTotal)) * 100);
        $growthLabel = ($growthPct >= 0 ? '+' : '') . $growthPct . '%';
    } else {
        $growthLabel = $thisMonthTotal > 0 ? '+100%' : '0%';
    }

    $pointStats = [
        'learning' => ['label' => 'Learning Points', 'value' => (string) $learningPoints, 'icon' => 'military_tech',
            'trend' => ($learningMonth >= 0 ? '+' : '') . $learningMonth . ' this month'],
        'mentor' => ['label' => 'Mentor Points', 'value' => (string) $mentorPoints, 'icon' => 'military_tech',
            'trend' => ($mentorMonth >= 0 ? '+' : '') . $mentorMonth . ' this month'],
        'total' => ['label' => 'Total Points', 'value' => (string) ($learningPoints + $mentorPoints), 'icon' => 'military_tech',
            'trend' => 'Rank ' . $rank . ' of ' . number_format($totalUsers)],
        'growth' => ['label' => 'Point Growth', 'value' => $growthLabel, 'icon' => 'trending_up', 'trend' => 'vs. last month'],
    ];

    $transactionsStmt = $pdo->prepare(
        "SELECT pt.amount, pt.point_type, pt.category, pt.reason, pt.created_at,
                COALESCE(
                    CASE WHEN pt.related_session_id IS NOT NULL
                         THEN IF(ms.learner_id = pt.user_id, mu.full_name, lu.full_name) END,
                    p.title,
                    lg.title
                ) AS session_label
         FROM point_transactions pt
         LEFT JOIN mentoring_sessions ms ON ms.id = pt.related_session_id
         LEFT JOIN users lu ON lu.id = ms.learner_id
         LEFT JOIN users mu ON mu.id = ms.mentor_id
         LEFT JOIN posts p ON p.id = pt.related_post_id
         LEFT JOIN learning_goals lg ON lg.id = pt.related_goal_id
         WHERE pt.user_id = ? AND pt.point_type = ?
         ORDER BY pt.created_at DESC"
    );
    $transactionsStmt->execute([UKN_CURRENT_USER_ID, $isMentor ? 'mentor' : 'learning']);
    $transactions = array_map(static function (array $row) use ($activityIcons, $isMentor): array {
        return [
            'amount' => (int) $row['amount'],
            'type' => $isMentor ? 'Mentor Points' : 'Learning Points',
            'reason' => $row['reason'],
            'session' => $row['session_label'],
            'date' => date('M j, Y', strtotime($row['created_at'])),
            'icon' => $activityIcons[$row['category']] ?? 'military_tech',
            'category' => $row['category'],
        ];
    }, $transactionsStmt->fetchAll());
} catch (Throwable $e) {
    error_log('[UKN points] ' . $e->getMessage());
    $pointsDbError = true;
    $pointStats = [];
    $transactions = [];
}
?>
<div class="ukn-page-header">
  <div>
    <h1><?= htmlspecialchars($heading) ?></h1>
    <p class="ukn-page-header__sub"><?= htmlspecialchars($subheading) ?></p>
  </div>
</div>
<?php if ($pointsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your points.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="row g-3 mb-4">
  <?php foreach ($statOrder as $key): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($pointStats[$key]); ?></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3 mb-lg-0">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <h2 class="ukn-h4 mb-0">Transaction History</h2>
          <select class="form-select form-select-sm w-auto" id="pointsTransactionFilter" aria-label="Filter transactions">
            <?php foreach ($filterOptions as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div data-points-list>
          <?php foreach ($transactions as $transaction): ?>
            <div data-points-item data-points-category="<?= htmlspecialchars($transaction['category']) ?>">
              <?php ukn_point_transaction($transaction); ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div<?= $transactions ? ' hidden' : '' ?> data-points-empty>
          <?php ukn_empty_state([
              'icon' => 'military_tech',
              'title' => $isMentor ? 'No mentor point activity yet.' : 'No point activity yet.',
              'message' => $isMentor ? 'Completed mentoring sessions will appear here.' : 'Complete learning activities to start earning points.',
          ]); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h2 class="ukn-h4">How You Earn Points</h2>
        <ul class="ukn-body-sm mb-0 ps-3">
          <?php foreach ($howYouEarn as $source): ?>
            <li class="mb-2"><?= htmlspecialchars($source) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>