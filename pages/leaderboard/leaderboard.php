<?php
require_once __DIR__ . '/../../components/leaderboard-row.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$defaultView = $isMentor ? 'mentors' : 'learners';
// TODO(auth): "you" should be the real session user id; this mirrors index.php's own
// hardcoded demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

$learners = [];
$mentors = [];
$contributors = [];
$leaderboardDbError = false;

try {
    $pdo = getDatabaseConnection();

    $learnersStmt = $pdo->query(
        "SELECT u.id, u.full_name AS name, u.initials, u.learning_points AS points,
                u.sessions_as_learner AS sessions, d.name AS category
         FROM users u LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.role IN ('learner', 'dual') AND u.status = 'active'
         ORDER BY u.learning_points DESC
         LIMIT 20"
    );
    $learners = $learnersStmt->fetchAll();

    $mentorsStmt = $pdo->query(
        "SELECT u.id, u.full_name AS name, u.initials, u.mentor_points AS points, u.avg_rating AS rating,
                u.sessions_as_mentor AS sessions, d.name AS category
         FROM users u LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.role IN ('mentor', 'dual') AND u.status = 'active'
         ORDER BY u.mentor_points DESC, u.avg_rating DESC
         LIMIT 20"
    );
    $mentors = $mentorsStmt->fetchAll();

    $contributorsStmt = $pdo->query(
        "SELECT u.id, u.full_name AS name, u.initials, u.role, d.name AS category,
                SUM(pt.amount) AS points, COUNT(*) AS sessions
         FROM point_transactions pt
         JOIN users u ON u.id = pt.user_id
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE pt.point_type = 'community'
         GROUP BY u.id, u.full_name, u.initials, u.role, d.name
         ORDER BY points DESC
         LIMIT 20"
    );
    $contributors = $contributorsStmt->fetchAll();

    // $defaultProfileRoute is used only for rows where the query itself didn't fetch a
    // role (learners/mentors tabs are already role-filtered); contributors carries its
    // own per-row role since it can mix both.
    $rankAndDecorate = static function (array $rows, string $defaultProfileRoute, string $pointType) {
        $rank = 0;
        foreach ($rows as &$row) {
            $rank++;
            $row['rank'] = $rank;
            $row['points'] = (int) $row['points'];
            $row['category'] = (string) ($row['category'] ?? '');
            $row['pointType'] = $pointType;
            $row['isCurrentUser'] = ((int) $row['id'] === UKN_DEMO_USER_ID);
            $profileRoute = isset($row['role'])
                ? (in_array($row['role'], ['mentor', 'dual'], true) ? 'mentor-profile' : 'learner-profile')
                : $defaultProfileRoute;
            $row['href'] = $row['isCurrentUser']
                ? ukn_route_href('my-profile')
                : ukn_route_href($profileRoute) . '&id=' . $row['id'];
            if (array_key_exists('rating', $row) && $row['rating'] !== null) {
                $row['rating'] = (float) $row['rating'];
            }
            if (array_key_exists('sessions', $row) && $row['sessions'] !== null) {
                $row['sessions'] = (int) $row['sessions'];
            }
            unset($row['id'], $row['role']);
        }
        unset($row);
        return $rows;
    };
    $learners = $rankAndDecorate($learners, 'learner-profile', 'Learning Points');
    $mentors = $rankAndDecorate($mentors, 'mentor-profile', 'Mentor Points');
    $contributors = $rankAndDecorate($contributors, 'learner-profile', 'Community Points');
    foreach ($contributors as &$row) {
        $row['activityLabel'] = 'posts';
    }
    unset($row);
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
<?php endif; ?>