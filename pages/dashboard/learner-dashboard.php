<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/goal-card.php';
require_once __DIR__ . '/../../components/session-card.php';
require_once __DIR__ . '/../../components/mentor-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/community.php';


$learnerStats = [];
$learnerGoals = [];
$learnerSessions = [];
$recommendedMentors = [];
$learningSkills = [];
$sessionsChartLabels = [];
$sessionsChartValues = [];
$sessionsChartAriaLabel = '';
$learnerDashboardDbError = false;

try {
    $pdo = getDatabaseConnection();

    $userStmt = $pdo->prepare("SELECT learning_points, sessions_as_learner FROM users WHERE id = ?");
    $userStmt->execute([UKN_CURRENT_USER_ID]);
    $user = $userStmt->fetch();

    $monthSumStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM point_transactions
         WHERE user_id = ? AND point_type = 'learning' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    );
    $monthSumStmt->execute([UKN_CURRENT_USER_ID]);
    $learningMonth = (int) $monthSumStmt->fetchColumn();

    $upcomingCountStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM mentoring_sessions
         WHERE learner_id = ? AND status = 'accepted' AND scheduled_date >= CURDATE()"
    );
    $upcomingCountStmt->execute([UKN_CURRENT_USER_ID]);
    $upcomingCount = (int) $upcomingCountStmt->fetchColumn();

    $learningSkillCountStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND skill_type = 'learning'"
    );
    $learningSkillCountStmt->execute([UKN_CURRENT_USER_ID]);

    $learnerStats = [
        ['label' => 'Learning Points', 'value' => (string) ($user['learning_points'] ?? 0), 'icon' => 'military_tech',
            'trend' => ($learningMonth >= 0 ? '+' : '') . $learningMonth . ' this month'],
        ['label' => 'Completed Sessions', 'value' => (string) ($user['sessions_as_learner'] ?? 0), 'icon' => 'event_available'],
        ['label' => 'Upcoming Sessions', 'value' => (string) $upcomingCount, 'icon' => 'event_upcoming'],
        ['label' => 'Skills Learning', 'value' => (string) $learningSkillCountStmt->fetchColumn(), 'icon' => 'workspaces'],
    ];

    $goalsStmt = $pdo->prepare(
        "SELECT lg.id, lg.title, s.name AS skill, lg.progress, lg.target_date
         FROM learning_goals lg LEFT JOIN skills s ON s.id = lg.skill_id
         WHERE lg.user_id = ? AND lg.status = 'in-progress'
         ORDER BY lg.target_date ASC
         LIMIT 2"
    );
    $goalsStmt->execute([UKN_CURRENT_USER_ID]);
    $learnerGoals = array_map(static function (array $row): array {
        $row['targetDate'] = $row['target_date'] ? date('F Y', strtotime($row['target_date'])) : '';
        return $row;
    }, $goalsStmt->fetchAll());

    $sessionsStmt = $pdo->prepare(
        "SELECT ms.id, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes,
                m.full_name AS counterparty, m.initials AS counterpartyInitials, sk.name AS skill
         FROM mentoring_sessions ms
         JOIN users m ON m.id = ms.mentor_id
         JOIN skills sk ON sk.id = ms.skill_id
         WHERE ms.learner_id = ? AND ms.status = 'accepted' AND ms.scheduled_date >= CURDATE()
         ORDER BY ms.scheduled_date, ms.scheduled_time
         LIMIT 2"
    );
    $sessionsStmt->execute([UKN_CURRENT_USER_ID]);
    $learnerSessions = array_map(static function (array $row): array {
        $timestamp = strtotime($row['scheduled_date'] . ' ' . $row['scheduled_time']);
        return [
            'id' => (int) $row['id'],
            'viewer' => 'learner',
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

    // Top-rated available mentors (excluding this user), same defensible stand-in used by
    // pages/mentors/recommendations.php — no scoring/match engine exists in the schema
    // (see DATABASE_READ_INTEGRATION_PLAN.md §2.4), so 'match'/'matchLabel' stay null.
    $mentorStmt = $pdo->prepare(
        "SELECT DISTINCT u.id, u.full_name AS name, u.initials, u.avatar_path, u.avg_rating AS rating,
                u.mentor_points AS points, u.sessions_as_mentor AS sessions, d.name AS department
         FROM users u
         JOIN user_skills us ON us.user_id = u.id AND us.skill_type = 'teaching'
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.role = 'dual' AND u.status = 'active' AND u.id != ?
         ORDER BY u.avg_rating DESC, u.sessions_as_mentor DESC
         LIMIT 3"
    );
    $mentorStmt->execute([UKN_CURRENT_USER_ID]);
    $recommendedMentors = $mentorStmt->fetchAll();

    if ($recommendedMentors) {
        $mentorIds = array_column($recommendedMentors, 'id');
        $placeholders = implode(',', array_fill(0, count($mentorIds), '?'));
        $skillsStmt = $pdo->prepare(
            "SELECT us.user_id, s.name
             FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id IN ($placeholders) AND us.skill_type = 'teaching'
             ORDER BY us.user_id, us.sessions_count DESC, us.proficiency DESC"
        );
        $skillsStmt->execute($mentorIds);
        $skillsByMentor = [];
        foreach ($skillsStmt->fetchAll() as $row) {
            $skillsByMentor[$row['user_id']][] = $row['name'];
        }
        foreach ($recommendedMentors as &$mentor) {
            $skillNames = $skillsByMentor[$mentor['id']] ?? [];
            $mentor['primarySkill'] = $skillNames[0] ?? '';
            $mentor['otherSkills'] = array_slice($skillNames, 1);
            $mentor['profileHref'] = ukn_route_href('mentor-profile') . '&id=' . $mentor['id'];
            $mentor['following'] = uknFollowStateFor((int) $mentor['id']);
        }
        unset($mentor);
    }

    $learningSkillsStmt = $pdo->prepare(
        "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
         WHERE us.user_id = ? AND us.skill_type = 'learning'
         ORDER BY s.name"
    );
    $learningSkillsStmt->execute([UKN_CURRENT_USER_ID]);
    $learningSkills = $learningSkillsStmt->fetchAll(PDO::FETCH_COLUMN);

    // Sessions completed per month, last 6 calendar months. Depends on the seed data's
    // fictional 2026 dates vs. the real system clock, same caveat as My Profile/Points'
    // monthly trend (see DATABASE_READ_INTEGRATION_PLAN.md §2.7) — sparse/zero months are
    // the correct, expected result here, not a bug.
    $monthlyStmt = $pdo->prepare(
        "SELECT DATE_FORMAT(completed_at, '%Y-%m') AS ym, COUNT(*) AS c
         FROM mentoring_sessions
         WHERE learner_id = ? AND status = 'completed' AND completed_at IS NOT NULL
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
    error_log('[UKN learner-dashboard] ' . $e->getMessage());
    $learnerDashboardDbError = true;
}
?>
<div class="ukn-page-header">
  <div>
    <h1>Learner Dashboard</h1>
    <p class="ukn-page-header__sub">Track your learning progress, goals and upcoming sessions.</p>
  </div>
</div>
<?php if ($learnerDashboardDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your dashboard.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
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
            aria-label="<?= htmlspecialchars($sessionsChartAriaLabel) ?>"
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
    <?php if (!$learnerGoals): ?>
      <?php ukn_empty_state([
          'icon' => 'flag',
          'title' => 'No active learning goals.',
          'message' => 'Set a goal to track your learning progress.',
          'action' => ['label' => 'Set a Goal', 'href' => ukn_route_href('learning-goals')],
          'dashed' => true,
      ]); ?>
    <?php endif; ?>
  </div>
</div>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Upcoming Sessions</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('sessions')) ?>" class="ukn-body-sm">View All Sessions</a>
  </div>
  <?php foreach ($learnerSessions as $session): ukn_session_card($session); endforeach; ?>
  <?php if (!$learnerSessions): ?>
    <?php ukn_empty_state([
        'icon' => 'event',
        'title' => 'No upcoming sessions.',
        'message' => 'Sessions you book with a mentor will show up here once accepted.',
        'action' => ['label' => 'Find a Mentor', 'href' => ukn_route_href('find-mentors')],
        'dashed' => true,
    ]); ?>
  <?php endif; ?>
</div>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Recommended Mentors</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('recommendations')) ?>" class="ukn-body-sm">View Recommendations</a>
  </div>
  <?php if ($recommendedMentors): ?>
    <div class="row g-3">
      <?php foreach ($recommendedMentors as $mentor): ?>
        <div class="col-md-6 col-lg-4"><?php ukn_mentor_card($mentor, ['variant' => 'recommendation']); ?></div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <?php ukn_empty_state([
        'icon' => 'person_search',
        'title' => 'No mentors available yet.',
        'message' => 'Check back once mentors have added their teaching skills.',
        'dashed' => true,
    ]); ?>
  <?php endif; ?>
</div>
<div class="card">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="ukn-h4 mb-0">Learning Skills</h2>
      <a href="<?= htmlspecialchars(ukn_route_href('learning-skills')) ?>" class="ukn-body-sm">Manage Learning Skills</a>
    </div>
    <?php if ($learningSkills): ?>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($learningSkills as $skill): ?>
          <span class="ukn-tag-skill"><?= htmlspecialchars($skill) ?></span>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <?php ukn_empty_state([
          'icon' => 'menu_book',
          'title' => "You haven't added any learning skills yet.",
          'message' => 'Explore the skills directory to find something to learn.',
          'action' => ['label' => 'Explore Skills', 'href' => ukn_route_href('skills')],
          'dashed' => true,
      ]); ?>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
