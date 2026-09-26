<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/goal-card.php';
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';
require_once __DIR__ . '/../../backend/helpers/community.php';
require_once __DIR__ . '/../../components/follow-button.php';

$requestedId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;
$learner = false;
$learnerDbError = false;

try {
    $pdo = getDatabaseConnection();

    $selectBase = "SELECT u.id, u.full_name AS name, u.initials, u.avatar_path, u.year_of_study AS year, u.bio,
            u.learning_points, u.sessions_as_learner, d.name AS department
        FROM users u
        LEFT JOIN departments d ON d.id = u.department_id
        WHERE u.role IN ('learner', 'dual') AND u.status = 'active' ";

    $stmt = $pdo->prepare($selectBase . "AND u.id = ?");
    $stmt->execute([$requestedId]);
    $learner = $stmt->fetch();

    if ($learner === false) {
        // No matching/active learner for the requested id: fall back to the lowest-id
        // active learner, mirroring the page's previous "always show something" mock behaviour.
        $stmt = $pdo->prepare($selectBase . "ORDER BY u.id ASC LIMIT 1");
        $stmt->execute();
        $learner = $stmt->fetch();
    }

    if ($learner !== false) {
        $learnerId = (int) $learner['id'];
        $learner['year'] = (string) ($learner['year'] ?? '');
        $learner['bio'] = (string) ($learner['bio'] ?? '');
        $learner['department'] = (string) ($learner['department'] ?? '');

        $skillsStmt = $pdo->prepare(
            "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id = ? AND us.skill_type = 'learning'
             ORDER BY s.name"
        );
        $skillsStmt->execute([$learnerId]);
        $learner['skills'] = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

        $goalsCompletedStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM learning_goals WHERE user_id = ? AND status = 'completed'"
        );
        $goalsCompletedStmt->execute([$learnerId]);
        $goalsCompleted = (int) $goalsCompletedStmt->fetchColumn();

        $learner['stats'] = [
            ['label' => 'Learning Points', 'value' => (string) $learner['learning_points'], 'icon' => 'military_tech'],
            ['label' => 'Completed Sessions', 'value' => (string) $learner['sessions_as_learner'], 'icon' => 'event_available'],
            ['label' => 'Skills Learning', 'value' => (string) count($learner['skills']), 'icon' => 'workspaces'],
            ['label' => 'Goals Completed', 'value' => (string) $goalsCompleted, 'icon' => 'flag'],
        ];

        $goalsStmt = $pdo->prepare(
            "SELECT lg.title, s.name AS skill, lg.progress, lg.target_date
             FROM learning_goals lg LEFT JOIN skills s ON s.id = lg.skill_id
             WHERE lg.user_id = ? AND lg.status = 'in-progress'
             ORDER BY lg.target_date ASC"
        );
        $goalsStmt->execute([$learnerId]);
        $learner['goals'] = array_map(static function (array $row): array {
            $row['targetDate'] = $row['target_date'] ? date('F Y', strtotime($row['target_date'])) : '';
            return $row;
        }, $goalsStmt->fetchAll());

        $postStmt = $pdo->prepare(
            "SELECT id, title, content, vote_score AS score, comment_count AS comments, created_at
             FROM posts WHERE user_id = ? AND status = 'visible'
             ORDER BY created_at DESC LIMIT 1"
        );
        $postStmt->execute([$learnerId]);
        $postRow = $postStmt->fetch();
        if ($postRow !== false) {
            $tagsStmt = $pdo->prepare(
                "SELECT s.name FROM post_skills ps JOIN skills s ON s.id = ps.skill_id WHERE ps.post_id = ?"
            );
            $tagsStmt->execute([$postRow['id']]);
            $postRow['tags'] = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
            $postRow['excerpt'] = ukn_excerpt($postRow['content']);
            $postRow['time'] = ukn_time_ago($postRow['created_at']);
            $postRow['href'] = 'index.php?page=post-details&id=' . $postRow['id'];
        }
        $learner['post'] = $postRow === false ? null : uknDecoratePosts([$postRow + ['author_id' => $learnerId]])[0];
    }
} catch (Throwable $e) {
    error_log('[UKN learner-profile] ' . $e->getMessage());
    $learnerDbError = true;
}
?>
<?php if ($learnerDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load this profile.',
      'message' => 'Something went wrong while loading this learner profile. Please try again shortly.',
  ]); ?>
<?php elseif ($learner === false): ?>
  <?php ukn_empty_state([
      'icon' => 'person_off',
      'title' => 'Learner not found.',
      'message' => 'This profile may no longer be available.',
  ]); ?>
<?php else: ?>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start gap-3 flex-wrap">
      <?= uknAvatarHtml($learner['avatar_path'], $learner['initials'], 'ukn-avatar ukn-avatar-xl flex-shrink-0') ?>
      <div class="flex-fill ukn-min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h1 class="ukn-h3 mb-0"><?= htmlspecialchars($learner['name']) ?></h1>
          <span class="ukn-role-chip">Learner</span>
        </div>
        <div class="ukn-body-sm mt-1"><?= htmlspecialchars($learner['department']) ?> &middot; <?= htmlspecialchars($learner['year']) ?></div>
        <p class="ukn-body-sm mt-2 mb-0"><?= htmlspecialchars($learner['bio']) ?></p>
      </div>
      <?php $isFollowing = uknFollowStateFor((int) $learner['id']);
      if ($isFollowing !== null): ?>
      <div class="flex-shrink-0">
        <?php ukn_follow_form((int) $learner['id'], $isFollowing, 'btn btn-sm ' . ($isFollowing ? 'btn-outline-secondary' : 'btn-primary')); ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="row g-3 mb-4">
  <?php foreach ($learner['stats'] as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="ukn-h4">Learning Skills</h2>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($learner['skills'] as $skill): ?>
        <span class="ukn-tag-skill"><?= htmlspecialchars($skill) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php if ($learner['goals']): ?>
<div class="mb-4">
  <h2 class="ukn-h4 mb-3">Learning Goals</h2>
  <?php foreach ($learner['goals'] as $goal): $goal['showActions'] = false; ukn_goal_card($goal); endforeach; ?>
</div>
<?php endif; ?>
<?php if ($learner['post']): ?>
<div>
  <h2 class="ukn-h4 mb-3">Recent Posts</h2>
  <?php ukn_post_card($learner['post'] + [
      'author' => $learner['name'], 'initials' => $learner['initials'], 'avatar_path' => $learner['avatar_path'], 'role' => 'Learner',
      'department' => $learner['department'], 'isOwner' => false,
  ]); ?>
</div>
<?php endif; ?>
<?php endif; ?>