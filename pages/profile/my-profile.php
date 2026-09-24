<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/goal-card.php';
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';
require_once __DIR__ . '/../../backend/helpers/community.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$isDualRoleUser = !empty($currentUser['loggedIn']) && !empty($currentUser['dualRole']);

$department = '';
$yearOfStudy = '';
$shortBio = '';
$aboutBio = '';
$pointsStatByRole = [];
$profileStats = [];
$skills = [];
$goals = [];
$activity = [];
$myPost = null;
$myProfileDbError = false;

try {
    $pdo = getDatabaseConnection();

    $userStmt = $pdo->prepare(
        "SELECT u.headline, u.bio, u.year_of_study, u.learning_points, u.mentor_points,
                u.avg_rating, u.sessions_as_learner, u.sessions_as_mentor, d.name AS department
         FROM users u LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.id = ?"
    );
    $userStmt->execute([UKN_CURRENT_USER_ID]);
    $user = $userStmt->fetch();

    if ($user !== false) {
        $department = (string) ($user['department'] ?? '');
        $yearOfStudy = (string) ($user['year_of_study'] ?? '');
        $shortBio = (string) ($user['headline'] ?? '');
        $aboutBio = (string) ($user['bio'] ?? '');

        $learningMonthStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM point_transactions
             WHERE user_id = ? AND point_type = 'learning' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $learningMonthStmt->execute([UKN_CURRENT_USER_ID]);
        $learningMonth = (int) $learningMonthStmt->fetchColumn();

        $mentorMonthStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM point_transactions
             WHERE user_id = ? AND point_type = 'mentor' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $mentorMonthStmt->execute([UKN_CURRENT_USER_ID]);
        $mentorMonth = (int) $mentorMonthStmt->fetchColumn();

        $pointsStatByRole = [
            'learner' => ['label' => 'Learning Points', 'value' => (string) $user['learning_points'], 'icon' => 'military_tech',
                'trend' => ($learningMonth >= 0 ? '+' : '') . $learningMonth . ' this month'],
            'mentor' => ['label' => 'Mentor Points', 'value' => (string) $user['mentor_points'], 'icon' => 'military_tech',
                'trend' => ($mentorMonth >= 0 ? '+' : '') . $mentorMonth . ' this month'],
        ];

        $teachingCountStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND skill_type = 'teaching'"
        );
        $teachingCountStmt->execute([UKN_CURRENT_USER_ID]);

        $learningCountStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND skill_type = 'learning'"
        );
        $learningCountStmt->execute([UKN_CURRENT_USER_ID]);

        $goalsCountStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM learning_goals WHERE user_id = ? AND status = 'in-progress'"
        );
        $goalsCountStmt->execute([UKN_CURRENT_USER_ID]);

        $profileStats = $isMentor
            ? [
                ['label' => 'Average Rating', 'value' => $user['avg_rating'] !== null ? (string) $user['avg_rating'] : '—', 'icon' => 'star'],
                ['label' => 'Completed Sessions', 'value' => (string) $user['sessions_as_mentor'], 'icon' => 'event_available'],
                ['label' => 'Teaching Skills', 'value' => (string) $teachingCountStmt->fetchColumn(), 'icon' => 'school'],
            ]
            : [
                ['label' => 'Completed Sessions', 'value' => (string) $user['sessions_as_learner'], 'icon' => 'event_available'],
                ['label' => 'Skills Learning', 'value' => (string) $learningCountStmt->fetchColumn(), 'icon' => 'workspaces'],
                ['label' => 'Current Goals', 'value' => (string) $goalsCountStmt->fetchColumn(), 'icon' => 'flag'],
            ];

        $skillsStmt = $pdo->prepare(
            "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id = ? AND us.skill_type = ?
             ORDER BY s.name"
        );
        $skillsStmt->execute([UKN_CURRENT_USER_ID, $isMentor ? 'teaching' : 'learning']);
        $skills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!$isMentor) {
            $goalsStmt = $pdo->prepare(
                "SELECT lg.id, lg.title, s.name AS skill, lg.progress, lg.target_date
                 FROM learning_goals lg LEFT JOIN skills s ON s.id = lg.skill_id
                 WHERE lg.user_id = ? AND lg.status = 'in-progress'
                 ORDER BY lg.target_date ASC"
            );
            $goalsStmt->execute([UKN_CURRENT_USER_ID]);
            $goals = array_map(static function (array $row): array {
                $row['targetDate'] = $row['target_date'] ? date('F Y', strtotime($row['target_date'])) : '';
                return $row;
            }, $goalsStmt->fetchAll());
        }

        // No activity/audit-log table exists in the schema (see DATABASE_READ_INTEGRATION_PLAN.md
        // §2.2). As a defensible simplification, this reuses the real points ledger — its `reason`
        // text is already human-readable — instead of a full activity feed.
        $activityIcons = [
            'session' => 'event_available', 'rating' => 'star', 'goal' => 'flag',
            'community' => 'forum', 'penalty' => 'warning',
        ];
        $activityStmt = $pdo->prepare(
            "SELECT category, reason FROM point_transactions
             WHERE user_id = ? ORDER BY created_at DESC LIMIT 4"
        );
        $activityStmt->execute([UKN_CURRENT_USER_ID]);
        $activity = array_map(static function (array $row) use ($activityIcons): array {
            return ['icon' => $activityIcons[$row['category']] ?? 'inbox', 'text' => $row['reason']];
        }, $activityStmt->fetchAll());

        $postStmt = $pdo->prepare(
            "SELECT id, title, content, vote_score AS score, comment_count AS comments, created_at
             FROM posts WHERE user_id = ? AND status = 'visible'
             ORDER BY created_at DESC LIMIT 1"
        );
        $postStmt->execute([UKN_CURRENT_USER_ID]);
        $postRow = $postStmt->fetch();
        if ($postRow !== false) {
            $tagsStmt = $pdo->prepare(
                "SELECT s.name FROM post_skills ps JOIN skills s ON s.id = ps.skill_id WHERE ps.post_id = ?"
            );
            $tagsStmt->execute([$postRow['id']]);
            $postRow['tags'] = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
            $postRow['excerpt'] = ukn_excerpt($postRow['content']);
            $postRow['href'] = 'index.php?page=post-details&id=' . $postRow['id'];
            $postRow['author'] = $currentUser['name'] ?? 'Member';
            $postRow['initials'] = $currentUser['initials'] ?? '?';
            $postRow['role'] = $isMentor ? 'Mentor' : 'Learner';
            $postRow['department'] = $department;
            $postRow['isOwner'] = true;
            $myPost = uknDecoratePosts([$postRow])[0];
        }
    }
} catch (Throwable $e) {
    error_log('[UKN my-profile] ' . $e->getMessage());
    $myProfileDbError = true;
}

$pointsRolesToRender = $isDualRoleUser ? ['learner', 'mentor'] : [$isMentor ? 'mentor' : 'learner'];
$skillsSectionTitle = $isMentor ? 'Teaching Skills' : 'Learning Skills';
$skillsSectionCta = $isMentor ? 'Manage Teaching Skills' : 'Manage Learning Skills';
$skillsSectionHref = ukn_route_href($isMentor ? 'teaching-skills' : 'learning-skills');
?>
<?php if ($myProfileDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your profile.',
      'message' => 'Something went wrong while loading your profile. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start gap-3 flex-wrap">
      <span class="ukn-avatar ukn-avatar-xl flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($currentUser['initials'] ?? '') ?></span>
      <div class="flex-fill ukn-min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h1 class="ukn-h3 mb-0"><?= htmlspecialchars($currentUser['name'] ?? '') ?></h1>
          <span class="ukn-status ukn-status-accent"><?= htmlspecialchars(ucfirst($activeRole)) ?></span>
        </div>
        <div class="ukn-body-sm mt-1"><?= htmlspecialchars($department) ?> &middot; <?= htmlspecialchars($yearOfStudy) ?></div>
        <?php if ($shortBio): ?>
          <p class="ukn-body-sm mt-2 mb-0"><?= htmlspecialchars($shortBio) ?></p>
        <?php endif; ?>
      </div>
      <div class="flex-shrink-0">
        <a href="<?= htmlspecialchars(ukn_route_href('edit-profile')) ?>" class="btn btn-outline-secondary btn-sm">Edit Profile</a>
      </div>
    </div>
  </div>
</div>
<div class="row g-3 mb-4">
  <?php foreach ($pointsRolesToRender as $pointsRole): if (!isset($pointsStatByRole[$pointsRole])) { continue; } ?>
    <div
      class="col-6 col-lg-3"
      data-profile-points-stat="<?= htmlspecialchars($pointsRole) ?>"
      <?= $isDualRoleUser ? 'data-role="' . htmlspecialchars($pointsRole) . '"' . ($pointsRole === $activeRole ? '' : ' hidden') : '' ?>
    ><?php ukn_stat_card($pointsStatByRole[$pointsRole]); ?></div>
  <?php endforeach; ?>
  <?php foreach ($profileStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <h2 class="ukn-h4">About</h2>
        <p class="ukn-body mb-0"><?= htmlspecialchars($aboutBio) ?></p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0"><?= htmlspecialchars($skillsSectionTitle) ?></h2>
          <a href="<?= htmlspecialchars($skillsSectionHref) ?>" class="ukn-body-sm"><?= htmlspecialchars($skillsSectionCta) ?></a>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($skills as $skill): ?>
            <span class="ukn-tag-skill"><?= htmlspecialchars($skill) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php if (!$isMentor): ?>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Current Goals</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('learning-goals')) ?>" class="ukn-body-sm">View All Goals</a>
  </div>
  <?php foreach ($goals as $goal): ukn_goal_card($goal); endforeach; ?>
</div>
<?php endif; ?>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="ukn-h4">Recent Activity</h2>
    <?php foreach ($activity as $item): ?>
      <div class="ukn-transaction-row">
        <span class="ms ukn-text-accent" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
        <div class="flex-fill ukn-min-w-0 ukn-nav-text"><?= htmlspecialchars($item['text']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php if ($myPost !== null): ?>
<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">My Posts</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('my-posts')) ?>" class="ukn-body-sm">View My Posts</a>
  </div>
  <?php ukn_post_card($myPost); ?>
</div>
<?php endif; ?>
<?php endif; ?>