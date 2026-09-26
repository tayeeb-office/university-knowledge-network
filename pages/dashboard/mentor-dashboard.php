<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/session-card.php';
require_once __DIR__ . '/../../components/learner-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/community.php';


$mentorStats = [];
$pendingRequests = [];
$mentorSessions = [];
$recentLearners = [];
$teachingSkills = [];
$availability = [];
$ratingSummary = ['overall' => 0.0, 'total' => 0];
$ratingBreakdown = [];
$sessionsChartLabels = [];
$sessionsChartValues = [];
$sessionsChartAriaLabel = '';
$mentorDashboardDbError = false;

$stars = static function (float $value): string {
    $rounded = (int) round($value);
    return str_repeat('★', max(0, min(5, $rounded))) . str_repeat('☆', 5 - max(0, min(5, $rounded)));
};

try {
    $pdo = getDatabaseConnection();

    $userStmt = $pdo->prepare("SELECT mentor_points, avg_rating, sessions_as_mentor FROM users WHERE id = ?");
    $userStmt->execute([UKN_CURRENT_USER_ID]);
    $user = $userStmt->fetch();

    $monthSumStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM point_transactions
         WHERE user_id = ? AND point_type = 'mentor' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    );
    $monthSumStmt->execute([UKN_CURRENT_USER_ID]);
    $mentorMonth = (int) $monthSumStmt->fetchColumn();

    $pendingCountStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM mentoring_sessions WHERE mentor_id = ? AND status = 'pending'"
    );
    $pendingCountStmt->execute([UKN_CURRENT_USER_ID]);
    $pendingCount = (int) $pendingCountStmt->fetchColumn();

    $mentorStats = [
        ['label' => 'Mentor Points', 'value' => (string) ($user['mentor_points'] ?? 0), 'icon' => 'military_tech',
            'trend' => ($mentorMonth >= 0 ? '+' : '') . $mentorMonth . ' this month'],
        ['label' => 'Average Rating', 'value' => $user && $user['avg_rating'] !== null ? (string) $user['avg_rating'] : '—', 'icon' => 'star'],
        ['label' => 'Pending Requests', 'value' => (string) $pendingCount, 'icon' => 'inbox'],
        ['label' => 'Completed Sessions', 'value' => (string) ($user['sessions_as_mentor'] ?? 0), 'icon' => 'event_available'],
    ];

    $requestsStmt = $pdo->prepare(
        "SELECT ms.id, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes, ms.request_message,
                l.full_name AS counterparty, l.initials AS counterpartyInitials, sk.name AS skill
         FROM mentoring_sessions ms
         JOIN users l ON l.id = ms.learner_id
         JOIN skills sk ON sk.id = ms.skill_id
         WHERE ms.mentor_id = ? AND ms.status = 'pending'
         ORDER BY ms.requested_at DESC
         LIMIT 2"
    );
    $requestsStmt->execute([UKN_CURRENT_USER_ID]);
    $pendingRequests = array_map(static function (array $row): array {
        $timestamp = strtotime($row['scheduled_date'] . ' ' . $row['scheduled_time']);
        return [
            'id' => (int) $row['id'],
            'viewer' => 'mentor',
            'counterparty' => $row['counterparty'],
            'counterpartyInitials' => $row['counterpartyInitials'],
            'skill' => $row['skill'],
            'day' => date('j', $timestamp),
            'month' => date('M', $timestamp),
            'time' => date('D g:ia', $timestamp),
            'duration' => $row['duration_minutes'] . ' min',
            'status' => 'pending',
            'message' => $row['request_message'],
            'detailsHref' => ukn_route_href('session-details') . '&id=' . $row['id'],
        ];
    }, $requestsStmt->fetchAll());

    $sessionsStmt = $pdo->prepare(
        "SELECT ms.id, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes,
                l.full_name AS counterparty, l.initials AS counterpartyInitials, sk.name AS skill
         FROM mentoring_sessions ms
         JOIN users l ON l.id = ms.learner_id
         JOIN skills sk ON sk.id = ms.skill_id
         WHERE ms.mentor_id = ? AND ms.status = 'accepted' AND ms.scheduled_date >= CURDATE()
         ORDER BY ms.scheduled_date, ms.scheduled_time
         LIMIT 2"
    );
    $sessionsStmt->execute([UKN_CURRENT_USER_ID]);
    $mentorSessions = array_map(static function (array $row): array {
        $timestamp = strtotime($row['scheduled_date'] . ' ' . $row['scheduled_time']);
        return [
            'id' => (int) $row['id'],
            'viewer' => 'mentor',
            'counterparty' => $row['counterparty'],
            'counterpartyInitials' => $row['counterpartyInitials'],
            'skill' => $row['skill'],
            'day' => date('j', $timestamp),
            'month' => date('M', $timestamp),
            'time' => date('D g:ia', $timestamp),
            'duration' => $row['duration_minutes'] . ' min',
            'status' => 'upcoming',
            'detailsHref' => ukn_route_href('session-details') . '&id=' . $row['id'],
        ];
    }, $sessionsStmt->fetchAll());

    // Most recently active learners of this mentor (dedup by learner, keep first/most recent
    // occurrence in PHP rather than a GROUP_CONCAT-style query, matching the simple-query +
    // PHP-post-processing style already used by pages/mentors/recommendations.php).
    $recentLearnersStmt = $pdo->prepare(
        "SELECT ms.created_at, l.id, l.full_name AS name, l.initials, l.avatar_path, l.learning_points AS points,
                l.sessions_as_learner AS sessions, d.name AS department, sk.name AS skill
         FROM mentoring_sessions ms
         JOIN users l ON l.id = ms.learner_id
         JOIN skills sk ON sk.id = ms.skill_id
         LEFT JOIN departments d ON d.id = l.department_id
         WHERE ms.mentor_id = ? AND ms.status IN ('accepted', 'completed')
         ORDER BY ms.created_at DESC"
    );
    $recentLearnersStmt->execute([UKN_CURRENT_USER_ID]);
    $seenLearnerIds = [];
    foreach ($recentLearnersStmt->fetchAll() as $row) {
        if (count($recentLearners) >= 2) {
            break;
        }
        if (isset($seenLearnerIds[$row['id']])) {
            continue;
        }
        $seenLearnerIds[$row['id']] = true;
        $recentLearners[] = [
            'name' => $row['name'],
            'initials' => $row['initials'],
            'avatar_path' => $row['avatar_path'],
            'department' => (string) ($row['department'] ?? ''),
            'skills' => [$row['skill']],
            'points' => (int) $row['points'],
            'sessions' => (int) $row['sessions'],
            'profileHref' => ukn_route_href('learner-profile') . '&id=' . $row['id'],
            'id' => (int) $row['id'],
            'following' => uknFollowStateFor((int) $row['id']),
        ];
    }

    $teachingSkillsStmt = $pdo->prepare(
        "SELECT s.name, us.sessions_count AS sessions
         FROM user_skills us JOIN skills s ON s.id = us.skill_id
         WHERE us.user_id = ? AND us.skill_type = 'teaching'
         ORDER BY us.sessions_count DESC
         LIMIT 3"
    );
    $teachingSkillsStmt->execute([UKN_CURRENT_USER_ID]);
    $teachingSkills = array_map(static function (array $row): array {
        $row['sessions'] = (int) $row['sessions'];
        return $row;
    }, $teachingSkillsStmt->fetchAll());

    $dayNames = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
    $slotsStmt = $pdo->prepare(
        "SELECT day_of_week, start_time, end_time FROM mentor_availability
         WHERE user_id = ? AND is_enabled = 1
         ORDER BY day_of_week, start_time"
    );
    $slotsStmt->execute([UKN_CURRENT_USER_ID]);
    $slotsByDay = [];
    foreach ($slotsStmt->fetchAll() as $row) {
        $dow = (int) $row['day_of_week'];
        $range = date('g:i A', strtotime($row['start_time'])) . ' – ' . date('g:i A', strtotime($row['end_time']));
        $slotsByDay[$dow][] = $range;
    }
    foreach ($slotsByDay as $dow => $ranges) {
        if (count($availability) >= 3) {
            break;
        }
        $availability[] = ['day' => $dayNames[$dow], 'time' => implode(', ', $ranges)];
    }

    $ratingStmt = $pdo->prepare(
        "SELECT AVG(overall) AS overall, COUNT(*) AS total, AVG(teaching) AS teaching,
                AVG(communication) AS communication, AVG(helpfulness) AS helpfulness
         FROM session_ratings WHERE mentor_id = ?"
    );
    $ratingStmt->execute([UKN_CURRENT_USER_ID]);
    $ratingRow = $ratingStmt->fetch();
    $ratingSummary['total'] = (int) $ratingRow['total'];
    $ratingSummary['overall'] = $ratingRow['overall'] !== null ? round((float) $ratingRow['overall'], 1) : 0.0;
    if ($ratingSummary['total'] > 0) {
        $ratingBreakdown = [
            'Teaching Quality' => round((float) $ratingRow['teaching'], 1),
            'Communication' => round((float) $ratingRow['communication'], 1),
            'Helpfulness' => round((float) $ratingRow['helpfulness'], 1),
        ];
    }

    // Sessions completed per month, last 6 calendar months. Depends on the seed data's
    // fictional 2026 dates vs. the real system clock, same caveat as My Profile/Points'
    // monthly trend (see DATABASE_READ_INTEGRATION_PLAN.md §2.7) — sparse/zero months are
    // the correct, expected result here, not a bug.
    $monthlyStmt = $pdo->prepare(
        "SELECT DATE_FORMAT(completed_at, '%Y-%m') AS ym, COUNT(*) AS c
         FROM mentoring_sessions
         WHERE mentor_id = ? AND status = 'completed' AND completed_at IS NOT NULL
         GROUP BY ym"
    );
    $monthlyStmt->execute([UKN_CURRENT_USER_ID]);
    $monthlyCounts = array_column($monthlyStmt->fetchAll(), 'c', 'ym');
    $ariaParts = [];
    for ($i = 5; $i >= 0; $i--) {
        $ts = strtotime("-{$i} months");
        $label = date('M', $ts);
        $count = (int) ($monthlyCounts[date('Y-m', $ts)] ?? 0);
        $sessionsChartLabels[] = $label;
        $sessionsChartValues[] = $count;
        $ariaParts[] = $label . ' ' . $count;
    }
    $sessionsChartAriaLabel = 'Sessions completed per month: ' . implode(', ', $ariaParts);
} catch (Throwable $e) {
    error_log('[UKN mentor-dashboard] ' . $e->getMessage());
    $mentorDashboardDbError = true;
}
?>
<div class="ukn-page-header">
  <div>
    <h1>Mentor Dashboard</h1>
    <p class="ukn-page-header__sub">Manage learner requests, sessions and your mentoring progress.</p>
  </div>
</div>
<?php if ($mentorDashboardDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your dashboard.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="row g-3 mb-4">
  <?php foreach ($mentorStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <h2 class="ukn-h4 mb-0">Sessions Completed</h2>
          <span class="ukn-body-sm">Monthly trend</span>
        </div>
        <div class="ukn-chart-wrap">
          <canvas
            data-chart="line"
            data-chart-label="Sessions"
            data-chart-labels="<?= htmlspecialchars(json_encode($sessionsChartLabels)) ?>"
            data-chart-values="<?= htmlspecialchars(json_encode($sessionsChartValues)) ?>"
            role="img"
            aria-label="<?= htmlspecialchars($sessionsChartAriaLabel) ?>"
          ></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0">Rating Summary</h2>
          <a href="<?= htmlspecialchars(ukn_route_href('ratings')) ?>" class="ukn-body-sm">View All Ratings</a>
        </div>
        <div class="d-flex align-items-baseline gap-2 mb-3">
          <span class="ukn-display"><?= htmlspecialchars((string) $ratingSummary['overall']) ?></span>
          <span class="ukn-body-sm">/ 5 &middot; <?= (int) $ratingSummary['total'] ?> reviews</span>
        </div>
        <?php foreach ($ratingBreakdown as $label => $value): ?>
          <div class="ukn-rating-item__breakdown">
            <span><?= htmlspecialchars($label) ?></span>
            <span class="ukn-stars" aria-label="<?= htmlspecialchars((string) $value) ?> out of 5"><?= $stars($value) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Pending Learner Requests</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('learner-requests')) ?>" class="ukn-body-sm">View All Requests</a>
  </div>
  <?php foreach ($pendingRequests as $session): ukn_session_card($session); endforeach; ?>
  <?php if (!$pendingRequests): ?>
    <?php ukn_empty_state([
        'icon' => 'inbox',
        'title' => 'No pending learner requests.',
        'message' => 'New session requests will appear here.',
        'dashed' => true,
    ]); ?>
  <?php endif; ?>
</div>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Upcoming Sessions</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('sessions')) ?>" class="ukn-body-sm">View All Sessions</a>
  </div>
  <?php foreach ($mentorSessions as $session): ukn_session_card($session); endforeach; ?>
  <?php if (!$mentorSessions): ?>
    <?php ukn_empty_state([
        'icon' => 'event',
        'title' => 'No upcoming sessions.',
        'message' => 'Accepted sessions will show up here.',
        'dashed' => true,
    ]); ?>
  <?php endif; ?>
</div>
<div class="mb-4">
  <h2 class="ukn-h4 mb-3">Recent Learners</h2>
  <?php if ($recentLearners): ?>
    <div class="row g-3">
      <?php foreach ($recentLearners as $learner): ?>
        <div class="col-md-6"><?php ukn_learner_card($learner); ?></div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <?php ukn_empty_state([
        'icon' => 'group',
        'title' => 'No learners yet.',
        'message' => 'Learners you mentor will show up here.',
        'dashed' => true,
    ]); ?>
  <?php endif; ?>
</div>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0">Teaching Skills</h2>
          <a href="<?= htmlspecialchars(ukn_route_href('teaching-skills')) ?>" class="ukn-body-sm">Manage</a>
        </div>
        <?php if ($teachingSkills): ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($teachingSkills as $skill): ?>
              <div class="ukn-row-between">
                <span class="ukn-tag-skill"><?= htmlspecialchars($skill['name']) ?></span>
                <span class="ukn-body-sm"><?= (int) $skill['sessions'] ?> sessions</span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <?php ukn_empty_state([
              'icon' => 'school',
              'title' => "You haven't added any teaching skills yet.",
              'message' => 'Explore the skills directory to find a skill you can teach.',
              'action' => ['label' => 'Explore Skills', 'href' => ukn_route_href('skills')],
              'dashed' => true,
          ]); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0">Availability</h2>
          <a href="<?= htmlspecialchars(ukn_route_href('availability')) ?>" class="ukn-body-sm">Manage</a>
        </div>
        <?php if ($availability): ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($availability as $slot): ?>
              <div class="ukn-row-between">
                <span class="fw-bold"><?= htmlspecialchars($slot['day']) ?></span>
                <span class="ukn-body-sm"><?= htmlspecialchars($slot['time']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <?php ukn_empty_state([
              'icon' => 'schedule',
              'title' => 'No availability set.',
              'message' => 'Set the days and times you can take mentoring sessions.',
              'action' => ['label' => 'Set Availability', 'href' => ukn_route_href('availability')],
              'dashed' => true,
          ]); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
