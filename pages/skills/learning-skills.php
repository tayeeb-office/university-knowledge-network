<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/skill-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';


$summaryStats = [];
$learningSkills = [];
$learningSkillsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $skillsStmt = $pdo->prepare(
        "SELECT s.id, s.name, us.proficiency AS level, us.progress, us.sessions_count,
                m.full_name AS mentor_name
         FROM user_skills us
         JOIN skills s ON s.id = us.skill_id
         LEFT JOIN users m ON m.id = us.primary_mentor_id
         WHERE us.user_id = ? AND us.skill_type = 'learning'
         ORDER BY s.name"
    );
    $skillsStmt->execute([UKN_CURRENT_USER_ID]);
    $learningSkills = array_map(static function (array $row): array {
        $sessions = (int) $row['sessions_count'];
        $sessionsLabel = $sessions . ' session' . ($sessions === 1 ? '' : 's') . ' completed';
        $row['meta'] = $row['mentor_name'] ? "Mentor: {$row['mentor_name']} · {$sessionsLabel}" : $sessionsLabel;
        $row['href'] = ukn_route_href('skill-details') . '&id=' . $row['id'];
        $row['primaryAction'] = 'Find Mentors';
        $row['primaryActionHref'] = ukn_route_href('find-mentors') . '&skill=' . urlencode($row['name']);
        return $row;
    }, $skillsStmt->fetchAll());

    $userStmt = $pdo->prepare(
        "SELECT sessions_as_learner, learning_points FROM users WHERE id = ?"
    );
    $userStmt->execute([UKN_CURRENT_USER_ID]);
    $user = $userStmt->fetch();

    $goalsCountStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM learning_goals WHERE user_id = ? AND status = 'in-progress'"
    );
    $goalsCountStmt->execute([UKN_CURRENT_USER_ID]);

    $summaryStats = [
        ['label' => 'Skills Learning', 'value' => (string) count($learningSkills), 'icon' => 'workspaces'],
        ['label' => 'Sessions Completed', 'value' => (string) ($user['sessions_as_learner'] ?? 0), 'icon' => 'event_available'],
        ['label' => 'Current Goals', 'value' => (string) $goalsCountStmt->fetchColumn(), 'icon' => 'flag'],
        ['label' => 'Learning Points', 'value' => (string) ($user['learning_points'] ?? 0), 'icon' => 'military_tech'],
    ];
} catch (Throwable $e) {
    error_log('[UKN learning-skills] ' . $e->getMessage());
    $learningSkillsDbError = true;
    $summaryStats = [];
    $learningSkills = [];
}
?>
<div class="ukn-page-header">
  <div>
    <h1>My Learning Skills</h1>
    <p class="ukn-page-header__sub">Manage the skills you are currently learning.</p>
  </div>
  <a href="<?= htmlspecialchars(ukn_route_href('skills')) ?>" class="btn btn-outline-secondary btn-sm">Explore Skills</a>
</div>
<?php if ($learningSkillsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your learning skills.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
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
<?php endif; ?>