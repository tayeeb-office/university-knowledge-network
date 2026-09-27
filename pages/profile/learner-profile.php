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
require_once __DIR__ . '/../../backend/helpers/learner-recommendations.php';
require_once __DIR__ . '/../../components/learner-recommendation-card.php';

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

        // Recommendations written for this learner (recommender fields joined in one query).
        // Any logged-in member except the learner may write recommendations (as many as they
        // like), in either mode.
        $learner['recommendations'] = uknLearnerRecommendations($pdo, $learnerId);
        $isOwnProfile = UKN_CURRENT_USER_ID > 0 && $learnerId === UKN_CURRENT_USER_ID;
        $canRecommend = UKN_CURRENT_USER_ID > 0 && !$isOwnProfile;
    }
} catch (Throwable $e) {
    error_log('[UKN learner-profile] ' . $e->getMessage());
    $learnerDbError = true;
}
$activeTab = ($_GET['tab'] ?? '') === 'recommendations' ? 'recommendations' : 'overview';
$openRecommendationForm = $activeTab === 'recommendations' && !empty($_GET['write']);
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
      if ($isFollowing !== null || $canRecommend): ?>
      <div class="d-flex flex-column gap-2 flex-shrink-0">
        <?php if ($isFollowing !== null): ?>
          <?php ukn_follow_form((int) $learner['id'], $isFollowing, 'btn btn-sm w-100 ' . ($isFollowing ? 'btn-outline-secondary' : 'btn-primary'), 'd-flex'); ?>
        <?php endif; ?>
        <?php if ($canRecommend): ?>
          <a href="<?= htmlspecialchars(ukn_route_href('learner-profile') . '&id=' . (int) $learner['id'] . '&tab=recommendations&write=1#writeRecommendation') ?>" class="btn btn-outline-primary btn-sm" data-write-recommendation>Write Recommendation</a>
        <?php endif; ?>
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
<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link<?= $activeTab === 'overview' ? ' active' : '' ?>" id="learnerTabOverview" data-bs-toggle="tab" data-bs-target="#learnerPaneOverview" type="button" role="tab" aria-controls="learnerPaneOverview" aria-selected="<?= $activeTab === 'overview' ? 'true' : 'false' ?>">Overview</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link<?= $activeTab === 'recommendations' ? ' active' : '' ?>" id="learnerTabRecommendations" data-bs-toggle="tab" data-bs-target="#learnerPaneRecommendations" type="button" role="tab" aria-controls="learnerPaneRecommendations" aria-selected="<?= $activeTab === 'recommendations' ? 'true' : 'false' ?>">Recommendations <span class="ukn-text-muted" data-recommendation-count><?= count($learner['recommendations']) ?></span></button>
  </li>
</ul>
<div class="tab-content">
<div class="tab-pane fade<?= $activeTab === 'overview' ? ' show active' : '' ?>" id="learnerPaneOverview" role="tabpanel" aria-labelledby="learnerTabOverview" tabindex="0">
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
</div>
<div class="tab-pane fade<?= $activeTab === 'recommendations' ? ' show active' : '' ?>" id="learnerPaneRecommendations" role="tabpanel" aria-labelledby="learnerTabRecommendations" tabindex="0">
  <?php if ($canRecommend): ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <h2 class="ukn-h4 mb-0">Write a Recommendation</h2>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#writeRecommendation" aria-expanded="<?= $openRecommendationForm ? 'true' : 'false' ?>" aria-controls="writeRecommendation">Write Recommendation</button>
      </div>
      <form action="backend/learner-recommendations/create.php" method="post" class="collapse<?= $openRecommendationForm ? ' show' : '' ?> mt-3" id="writeRecommendation">
        <?= csrfField() ?>
        <input type="hidden" name="return_to" value="<?= htmlspecialchars(uknBaseUrl() . '/' . ukn_route_href('learner-profile') . '&id=' . (int) $learner['id'] . '&tab=recommendations') ?>">
        <input type="hidden" name="learner_id" value="<?= (int) $learner['id'] ?>">
        <label for="recommendationContent" class="ukn-visually-hidden">Your recommendation for <?= htmlspecialchars($learner['name']) ?></label>
        <textarea class="form-control" id="recommendationContent" name="content" rows="4" maxlength="<?= UKN_RECOMMENDATION_MAX ?>" required placeholder="Share your experience learning with this student..."></textarea>
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mt-2">
          <span class="ukn-body-sm">Shown on <?= htmlspecialchars($learner['name']) ?>'s profile. Up to <?= UKN_RECOMMENDATION_MAX ?> characters.</span>
          <button type="submit" class="btn btn-primary btn-sm">Submit Recommendation</button>
        </div>
      </form>
    </div>
  </div>
  <?php elseif (UKN_CURRENT_USER_ID <= 0): ?>
  <p class="ukn-body-sm mb-3"><a href="<?= htmlspecialchars(ukn_route_href('login')) ?>">Log in</a> to write a recommendation for <?= htmlspecialchars($learner['name']) ?>.</p>
  <?php endif; ?>
  <?php if ($learner['recommendations']): ?>
    <?php foreach ($learner['recommendations'] as $rec):
        // Delete only for the recommendation's author, in either mode.
        ukn_learner_recommendation_card($rec, UKN_CURRENT_USER_ID > 0 && (int) $rec['recommender_id'] === UKN_CURRENT_USER_ID
            ? ['learnerId' => (int) $learner['id'], 'learnerName' => $learner['name']] : null);
    endforeach; ?>
  <?php else: ?>
    <?php ukn_empty_state([
        'icon' => 'recommend',
        'title' => 'No recommendations yet.',
        'message' => $isOwnProfile
            ? 'Recommendations other members write for you will appear here.'
            : 'Recommendations written for ' . $learner['name'] . ' will appear here.',
    ]); ?>
  <?php endif; ?>
</div>
</div>
<?php endif; ?>