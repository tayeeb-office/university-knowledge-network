<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/skill-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';


$summaryStats = [];
$teachingSkills = [];
$teachingSkillsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $skillsStmt = $pdo->prepare(
        "SELECT s.id, s.name, us.proficiency AS level, us.sessions_count AS sessions, us.avg_rating AS rating
         FROM user_skills us
         JOIN skills s ON s.id = us.skill_id
         WHERE us.user_id = ? AND us.skill_type = 'teaching'
         ORDER BY s.name"
    );
    $skillsStmt->execute([UKN_CURRENT_USER_ID]);
    $teachingSkills = array_map(static function (array $row): array {
        $row['sessions'] = (int) $row['sessions'];
        $row['rating'] = $row['rating'] !== null ? (float) $row['rating'] : null;
        $ratingLabel = $row['rating'] !== null ? ' · ★ ' . $row['rating'] : '';
        $row['meta'] = $row['sessions'] . ' sessions taught' . $ratingLabel;
        $row['href'] = ukn_route_href('skill-details') . '&id=' . $row['id'];
        $row['primaryAction'] = 'Manage Availability';
        $row['primaryActionHref'] = ukn_route_href('availability');
        return $row;
    }, $skillsStmt->fetchAll());

    $userStmt = $pdo->prepare(
        "SELECT sessions_as_mentor, mentor_points, avg_rating FROM users WHERE id = ?"
    );
    $userStmt->execute([UKN_CURRENT_USER_ID]);
    $user = $userStmt->fetch();

    $summaryStats = [
        ['label' => 'Teaching Skills', 'value' => (string) count($teachingSkills), 'icon' => 'school'],
        ['label' => 'Completed Sessions', 'value' => (string) ($user['sessions_as_mentor'] ?? 0), 'icon' => 'event_available'],
        ['label' => 'Mentor Points', 'value' => (string) ($user['mentor_points'] ?? 0), 'icon' => 'military_tech'],
        ['label' => 'Average Rating', 'value' => $user && $user['avg_rating'] !== null ? (string) $user['avg_rating'] : '—', 'icon' => 'star'],
    ];
} catch (Throwable $e) {
    error_log('[UKN teaching-skills] ' . $e->getMessage());
    $teachingSkillsDbError = true;
    $summaryStats = [];
    $teachingSkills = [];
}
?>
<div class="ukn-page-header">
  <div>
    <h1>My Teaching Skills</h1>
    <p class="ukn-page-header__sub">Manage the skills you can teach to other students.</p>
  </div>
  <a href="<?= htmlspecialchars(ukn_route_href('skills')) ?>" class="btn btn-outline-secondary btn-sm">Explore Skills</a>
</div>
<?php if ($teachingSkillsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your teaching skills.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
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
<?php endif; ?>