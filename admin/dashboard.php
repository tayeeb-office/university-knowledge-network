<?php
require_once __DIR__ . '/../backend/helpers/auth.php';
requireAdmin();
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../backend/helpers/format.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'dashboard';
$adminPageTitle = 'Admin Dashboard';
$adminPageSub = 'Monitor users, skills, sessions and community activity across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/dashboard.css'];
$adminPageScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',
    '../assets/js/admin/dashboard.js',
];

$stats = [];
$roleBreakdown = ['labels' => ['Learner only', 'Learner & Mentor'], 'values' => [0, 0]];
$sessionActivity = ['labels' => [], 'values' => []];
$sessionStatus = [];
$recentUsers = [];
$recentSessions = [];
$popularSkills = [];
$pendingReports = [];
$recentActivity = [];
$statusClass = [
    'Active' => 'ukn-status-accent',
    'Upcoming' => 'ukn-status-accent',
    'Pending' => 'ukn-status-neutral',
    'Completed' => 'ukn-status-accent',
    'Cancelled' => 'ukn-status-neutral',
    'Rejected' => 'ukn-status-neutral',
];
$dashboardDbError = false;

$reasonLabels = [
    'academic-integrity' => 'Academic Integrity Concern',
    'off-topic' => 'Off-topic',
    'spam' => 'Spam',
    'inappropriate' => 'Inappropriate Content',
    'harassment' => 'Harassment / Conduct',
    'other' => 'Other',
];
$sessionStatusLabels = ['pending' => 'Pending', 'accepted' => 'Upcoming', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected'];

try {
    $pdo = getDatabaseConnection();

    // --- Stat cards -------------------------------------------------------
    $roleCountsStmt = $pdo->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
    $roleCounts = ['learner' => 0, 'dual' => 0];
    foreach ($roleCountsStmt->fetchAll() as $row) {
        if (isset($roleCounts[$row['role']])) {
            $roleCounts[$row['role']] = (int) $row['c'];
        }
    }
    $totalUsers = array_sum($roleCounts);
    $roleBreakdown['values'] = [$roleCounts['learner'], $roleCounts['dual']];

    $totalSkills = (int) $pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();
    $skillsThisMonthStmt = $pdo->query(
        "SELECT COUNT(*) FROM skills WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    );
    $skillsThisMonth = (int) $skillsThisMonthStmt->fetchColumn();

    $totalSessions = (int) $pdo->query("SELECT COUNT(*) FROM mentoring_sessions")->fetchColumn();
    $totalPosts = (int) $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

    $thisMonthStart = date('Y-m-01');
    $nextMonthStart = date('Y-m-d', strtotime('+1 month', strtotime($thisMonthStart)));
    $lastMonthStart = date('Y-m-01', strtotime('-1 month'));

    $growthLabel = static function (PDO $pdo, string $table, string $dateColumn, string $thisMonthStart, string $nextMonthStart, string $lastMonthStart): string {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$dateColumn} >= ? AND {$dateColumn} < ?");
        $stmt->execute([$thisMonthStart, $nextMonthStart]);
        $thisMonth = (int) $stmt->fetchColumn();
        $stmt->execute([$lastMonthStart, $thisMonthStart]);
        $lastMonth = (int) $stmt->fetchColumn();
        if ($lastMonth !== 0) {
            $pct = round((($thisMonth - $lastMonth) / abs($lastMonth)) * 100, 1);
            return ($pct >= 0 ? 'Up ' : 'Down ') . abs($pct) . '% this month';
        }
        return $thisMonth > 0 ? 'Up 100% this month' : 'No change this month';
    };
    $sessionsTrend = $growthLabel($pdo, 'mentoring_sessions', 'requested_at', $thisMonthStart, $nextMonthStart, $lastMonthStart);
    $postsTrend = $growthLabel($pdo, 'posts', 'created_at', $thisMonthStart, $nextMonthStart, $lastMonthStart);

    $pendingReportsCount = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $newReportsStmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE status = 'pending' AND created_at >= ?");
    $newReportsStmt->execute([$weekStart]);
    $newReportsThisWeek = (int) $newReportsStmt->fetchColumn();

    $stats = [
        ['label' => 'Total Users', 'value' => number_format($totalUsers), 'icon' => 'group',
            'helper' => number_format($roleCounts['learner']) . ' Learners · ' . number_format($roleCounts['dual']) . ' Learners & Mentors'],
        ['label' => 'Total Skills', 'value' => number_format($totalSkills), 'icon' => 'workspaces',
            'trend' => $skillsThisMonth > 0 ? 'Up ' . $skillsThisMonth . ' this month' : 'No change this month'],
        ['label' => 'Total Sessions', 'value' => number_format($totalSessions), 'icon' => 'event', 'trend' => $sessionsTrend],
        ['label' => 'Community Posts', 'value' => number_format($totalPosts), 'icon' => 'article', 'trend' => $postsTrend],
        ['label' => 'Pending Reports', 'value' => (string) $pendingReportsCount, 'icon' => 'flag',
            'trend' => $newReportsThisWeek . ' new this week'],
    ];

    // --- Sessions Over Time (Mon-Sun of the current week) -----------------
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +7 days'));
    $activityStmt = $pdo->prepare(
        "SELECT DATE(requested_at) AS d, COUNT(*) AS c FROM mentoring_sessions
         WHERE requested_at >= ? AND requested_at < ? GROUP BY DATE(requested_at)"
    );
    $activityStmt->execute([$weekStart, $weekEnd]);
    $countsByDate = array_column($activityStmt->fetchAll(), 'c', 'd');
    for ($i = 0; $i < 7; $i++) {
        $d = date('Y-m-d', strtotime($weekStart . " +{$i} days"));
        $sessionActivity['labels'][] = date('D', strtotime($d));
        $sessionActivity['values'][] = (int) ($countsByDate[$d] ?? 0);
    }

    // --- Session status breakdown -------------------------------------
    $statusStmt = $pdo->query("SELECT status, COUNT(*) AS c FROM mentoring_sessions GROUP BY status");
    $byStatus = array_column($statusStmt->fetchAll(), 'c', 'status');
    $sessionStatus = [
        ['label' => 'Upcoming', 'value' => (int) ($byStatus['accepted'] ?? 0)],
        ['label' => 'Completed', 'value' => (int) ($byStatus['completed'] ?? 0)],
        ['label' => 'Cancelled', 'value' => (int) ($byStatus['cancelled'] ?? 0)],
        ['label' => 'Pending', 'value' => (int) ($byStatus['pending'] ?? 0)],
    ];

    // --- Recent Users ---------------------------------------------------
    $roleLabels = ['learner' => 'Learner', 'dual' => 'Learner & Mentor'];
    $userStatusLabels = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'];
    $recentUsersStmt = $pdo->query(
        "SELECT u.id, u.full_name AS name, u.initials, u.avatar_path, u.role, u.status, u.created_at, d.name AS department
         FROM users u LEFT JOIN departments d ON d.id = u.department_id
         ORDER BY u.created_at DESC LIMIT 5"
    );
    $recentUsers = array_map(static function (array $row) use ($roleLabels, $userStatusLabels): array {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'initials' => $row['initials'],
            'avatar_path' => $row['avatar_path'],
            'role' => $roleLabels[$row['role']] ?? ucfirst($row['role']),
            'department' => (string) ($row['department'] ?? ''),
            'joined' => date('M j, Y', strtotime($row['created_at'])),
            'status' => $userStatusLabels[$row['status']] ?? ucfirst($row['status']),
        ];
    }, $recentUsersStmt->fetchAll());

    // --- Recent & Upcoming Sessions --------------------------------------
    $recentSessionsStmt = $pdo->query(
        "SELECT ms.status, ms.requested_at, l.full_name AS learner, m.full_name AS mentor, sk.name AS skill
         FROM mentoring_sessions ms
         JOIN users l ON l.id = ms.learner_id
         JOIN users m ON m.id = ms.mentor_id
         JOIN skills sk ON sk.id = ms.skill_id
         ORDER BY ms.requested_at DESC LIMIT 4"
    );
    $recentSessions = array_map(static function (array $row) use ($sessionStatusLabels): array {
        return [
            'learner' => $row['learner'],
            'mentor' => $row['mentor'],
            'skill' => $row['skill'],
            'date' => date('M j, Y', strtotime($row['requested_at'])),
            'status' => $sessionStatusLabels[$row['status']] ?? ucfirst($row['status']),
        ];
    }, $recentSessionsStmt->fetchAll());

    // --- Popular Skills (by combined learner + mentor engagement) --------
    $popularSkillsStmt = $pdo->query(
        "SELECT s.name,
                (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'learning') AS learners,
                (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'teaching') AS mentors
         FROM skills s
         ORDER BY (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'learning')
                + (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'teaching') DESC
         LIMIT 4"
    );
    $popularSkills = array_map(static function (array $row): array {
        return ['name' => $row['name'], 'learners' => (int) $row['learners'], 'mentors' => (int) $row['mentors']];
    }, $popularSkillsStmt->fetchAll());

    // --- Pending Reports (most recent, with real target info) -----------
    $pendingReportsStmt = $pdo->query(
        "SELECT target_type, target_id, reason, created_at FROM reports
         WHERE status = 'pending' ORDER BY created_at DESC LIMIT 3"
    );
    foreach ($pendingReportsStmt->fetchAll() as $row) {
        if ($row['target_type'] === 'post') {
            $type = 'Post';
            $stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ?");
            $stmt->execute([$row['target_id']]);
            $content = (string) $stmt->fetchColumn();
        } elseif ($row['target_type'] === 'comment') {
            $type = 'Comment';
            $stmt = $pdo->prepare("SELECT content FROM comments WHERE id = ?");
            $stmt->execute([$row['target_id']]);
            $text = (string) $stmt->fetchColumn();
            $content = $text !== '' ? ukn_excerpt($text, 60) : '';
        } else {
            $type = 'User Conduct';
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$row['target_id']]);
            $content = 'Reported user: ' . (string) $stmt->fetchColumn();
        }
        $pendingReports[] = [
            'type' => $type,
            'reason' => $reasonLabels[$row['reason']] ?? ucfirst($row['reason']),
            'content' => $content,
            'time' => ukn_time_ago($row['created_at']),
        ];
    }

    // --- Recent Activity: real events from across the platform, newest
    // first. No activity-log table exists in the schema, so this combines
    // the same real signals already used elsewhere (registrations, completed
    // sessions, reports, new skill categories, 5-star ratings) instead of a
    // single per-user proxy — see DATABASE_READ_INTEGRATION_PLAN.md §2.2.
    $activityStmt = $pdo->query(
        "(SELECT 'person_add' AS icon,
                 CONCAT(u.full_name, ' registered as a new ',
                        CASE u.role WHEN 'dual' THEN 'Learner & Mentor' ELSE 'Learner' END, '.') AS text,
                 u.created_at AS ts
          FROM users u ORDER BY u.created_at DESC LIMIT 5)
         UNION ALL
         (SELECT 'event_available',
                 CONCAT(mu.full_name, ' completed a ', sk.name, ' mentoring session.'),
                 ms.completed_at
          FROM mentoring_sessions ms
          JOIN users mu ON mu.id = ms.mentor_id
          JOIN skills sk ON sk.id = ms.skill_id
          WHERE ms.status = 'completed' AND ms.completed_at IS NOT NULL
          ORDER BY ms.completed_at DESC LIMIT 5)
         UNION ALL
         (SELECT 'flag',
                 CONCAT('A ', r.target_type, ' was reported for review.'),
                 r.created_at
          FROM reports r ORDER BY r.created_at DESC LIMIT 5)
         UNION ALL
         (SELECT 'category',
                 CONCAT('New skill category added: ', sc.name, '.'),
                 sc.created_at
          FROM skill_categories sc ORDER BY sc.created_at DESC LIMIT 5)
         UNION ALL
         (SELECT 'star',
                 CONCAT(mu.full_name, ' received a 5-star mentoring rating.'),
                 sr.created_at
          FROM session_ratings sr
          JOIN users mu ON mu.id = sr.mentor_id
          WHERE sr.overall = 5 ORDER BY sr.created_at DESC LIMIT 5)
         ORDER BY ts DESC LIMIT 5"
    );
    $recentActivity = array_map(static function (array $row): array {
        return ['icon' => $row['icon'], 'text' => $row['text'], 'time' => ukn_time_ago($row['ts'])];
    }, $activityStmt->fetchAll());
} catch (Throwable $e) {
    error_log('[UKN admin/dashboard] ' . $e->getMessage());
    $dashboardDbError = true;
}
require __DIR__ . '/includes/header.php';
?>
<?php if ($dashboardDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load the dashboard.',
      'message' => 'Something went wrong while loading admin dashboard data. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="ukn-admin-stat-grid mb-4">
  <?php foreach ($stats as $stat): ukn_stat_card($stat); endforeach; ?>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><h2 class="ukn-h4 mb-0">Users by Role</h2></div>
      <div class="card-body">
        <div class="ukn-admin-chart-wrap ukn-admin-chart-wrap--sm">
          <canvas
            data-admin-chart="doughnut"
            data-chart-labels="<?= htmlspecialchars(json_encode($roleBreakdown['labels'])) ?>"
            data-chart-values="<?= htmlspecialchars(json_encode($roleBreakdown['values'])) ?>"
            role="img"
            aria-label="User role breakdown: <?= (int) $roleBreakdown['values'][0] ?> Learner only, <?= (int) $roleBreakdown['values'][1] ?> Learner &amp; Mentor"
          ></canvas>
        </div>
        <p class="ukn-body-sm ukn-text-muted mb-0">Each user counted once — <?= number_format(array_sum($roleBreakdown['values'])) ?> total. Learner &amp; Mentor accounts (approved mentors) keep learner access and are not double-counted.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header"><h2 class="ukn-h4 mb-0">Sessions Over Time</h2></div>
      <div class="card-body">
        <div class="ukn-admin-chart-wrap">
          <canvas
            data-admin-chart="bar"
            data-chart-labels="<?= htmlspecialchars(json_encode($sessionActivity['labels'])) ?>"
            data-chart-values="<?= htmlspecialchars(json_encode($sessionActivity['values'])) ?>"
            data-chart-dataset-label="Sessions"
            role="img"
            aria-label="Sessions requested per day this week"
          ></canvas>
        </div>
        <div class="ukn-admin-inline-stats mt-3">
          <?php foreach ($sessionStatus as $row): ?>
            <div><span class="ukn-eyebrow d-block"><?= htmlspecialchars($row['label']) ?></span><span class="fw-bold"><?= number_format($row['value']) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h2 class="ukn-h4 mb-0">Recent Users</h2>
    <a href="users.php" class="ukn-body-sm">View All Users</a>
  </div>
  <div class="table-responsive ukn-admin-scroller">
    <table class="table ukn-admin-table ukn-admin-table--scroll">
      <thead>
        <tr><th scope="col">User</th><th scope="col">Role</th><th scope="col">Department</th><th scope="col">Joined</th><th scope="col">Status</th><th scope="col">Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentUsers as $user): ?>
          <tr>
            <td data-label="User">
              <div class="d-flex align-items-center gap-2">
                <?= uknAvatarHtml($user['avatar_path'] ?? null, (string) $user['initials'], 'ukn-avatar flex-shrink-0') ?>
                <span><?= htmlspecialchars($user['name']) ?></span>
              </div>
            </td>
            <td data-label="Role"><?= htmlspecialchars($user['role']) ?></td>
            <td data-label="Department"><?= htmlspecialchars($user['department']) ?></td>
            <td data-label="Joined"><?= htmlspecialchars($user['joined']) ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$user['status']] ?? 'ukn-status-neutral' ?>"><?= htmlspecialchars($user['status']) ?></span></td>
            <td data-label="Action"><a href="user-details.php?id=<?= $user['id'] ?>" class="btn btn-outline-secondary btn-sm">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="ukn-h4 mb-0">Recent &amp; Upcoming Sessions</h2>
        <a href="sessions.php" class="ukn-body-sm">View All Sessions</a>
      </div>
      <div class="table-responsive ukn-admin-scroller">
        <table class="table ukn-admin-table ukn-admin-table--scroll">
          <thead>
            <tr><th scope="col">Learner</th><th scope="col">Mentor</th><th scope="col">Skill</th><th scope="col">Date</th><th scope="col">Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentSessions as $session): ?>
              <tr>
                <td data-label="Learner"><?= htmlspecialchars($session['learner']) ?></td>
                <td data-label="Mentor"><?= htmlspecialchars($session['mentor']) ?></td>
                <td data-label="Skill"><?= htmlspecialchars($session['skill']) ?></td>
                <td data-label="Date"><?= htmlspecialchars($session['date']) ?></td>
                <td data-label="Status"><span class="ukn-status <?= $statusClass[$session['status']] ?? 'ukn-status-neutral' ?>"><?= htmlspecialchars($session['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="ukn-h4 mb-0">Popular Skills</h2>
        <a href="skills.php" class="ukn-body-sm">Manage Skills</a>
      </div>
      <div class="card-body">
        <?php foreach ($popularSkills as $skill): ?>
          <div class="ukn-admin-row">
            <span class="fw-bold flex-fill ukn-min-w-0 ukn-truncate"><?= htmlspecialchars($skill['name']) ?></span>
            <span class="ukn-body-sm ukn-text-muted flex-shrink-0"><?= number_format($skill['learners']) ?> Learners</span>
            <span class="ukn-body-sm ukn-text-muted flex-shrink-0"><?= number_format($skill['mentors']) ?> Mentors</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="ukn-h4 mb-0">Pending Reports</h2>
        <a href="reports.php" class="ukn-body-sm">View All Reports</a>
      </div>
      <div class="card-body">
        <?php foreach ($pendingReports as $report): ?>
          <div class="ukn-admin-row ukn-admin-row--wrap">
            <div class="flex-fill ukn-min-w-0">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="ukn-role-chip"><?= htmlspecialchars($report['type']) ?></span>
                <span class="fw-bold"><?= htmlspecialchars($report['reason']) ?></span>
              </div>
              <div class="ukn-body-sm ukn-text-muted ukn-truncate"><?= htmlspecialchars($report['content']) ?></div>
              <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($report['time']) ?> &middot; <span class="ukn-status ukn-status-neutral">Pending</span></div>
            </div>
            <a href="reports.php" class="btn btn-outline-secondary btn-sm flex-shrink-0">Review</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h2 class="ukn-h4 mb-0">Recent Activity</h2></div>
      <div class="card-body">
        <?php foreach ($recentActivity as $activity): ?>
          <div class="ukn-admin-row">
            <span class="ms ukn-text-accent flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($activity['icon']) ?></span>
            <span class="flex-fill ukn-min-w-0"><?= htmlspecialchars($activity['text']) ?></span>
            <span class="ukn-body-sm ukn-text-muted flex-shrink-0"><?= htmlspecialchars($activity['time']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-header"><h2 class="ukn-h4 mb-0">Quick Actions</h2></div>
  <div class="card-body d-flex flex-wrap gap-2">
    <a href="users.php" class="btn btn-outline-secondary btn-sm"><span class="ms" aria-hidden="true">group</span> Manage Users</a>
    <a href="skills.php" class="btn btn-outline-secondary btn-sm"><span class="ms" aria-hidden="true">workspaces</span> Manage Skills</a>
    <a href="reports.php" class="btn btn-outline-secondary btn-sm"><span class="ms" aria-hidden="true">flag</span> Review Reports</a>
    <a href="sessions.php" class="btn btn-outline-secondary btn-sm"><span class="ms" aria-hidden="true">event</span> View Sessions</a>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
