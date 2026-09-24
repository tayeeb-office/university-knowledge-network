<?php
require_once __DIR__ . '/../../components/mentor-card.php';
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';
require_once __DIR__ . '/../../backend/helpers/community.php';
require_once __DIR__ . '/../../backend/helpers/skills.php';
require_once __DIR__ . '/../../components/skill-toggle.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$mySkills = uknCurrentUserSkills();
$myLearningSkills = array_values($mySkills['learning']);
$myTeachingSkills = array_values($mySkills['teaching']);

$requestedId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;
$skill = false;
$topMentors = [];
$skillDbError = false;

try {
    $pdo = getDatabaseConnection();

    $selectBase = "SELECT s.id, s.name, sc.name AS category, s.description, s.about,
            (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'teaching') AS mentors,
            (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'learning') AS learners,
            (SELECT COUNT(*) FROM mentoring_sessions WHERE skill_id = s.id AND status = 'completed') AS sessionsHeld,
            (SELECT COUNT(*) FROM post_skills ps JOIN posts p ON p.id = ps.post_id WHERE ps.skill_id = s.id AND p.status = 'visible') AS discussions
        FROM skills s
        JOIN skill_categories sc ON sc.id = s.category_id ";

    $stmt = $pdo->prepare($selectBase . "WHERE s.id = ? AND s.status = 'active'");
    $stmt->execute([$requestedId]);
    $skill = $stmt->fetch();

    if ($skill === false) {
        // No matching/active skill for the requested id: fall back to the lowest-id active
        // skill, mirroring the page's previous "always show something" mock behaviour.
        $stmt = $pdo->prepare($selectBase . "WHERE s.status = 'active' ORDER BY s.id ASC LIMIT 1");
        $stmt->execute();
        $skill = $stmt->fetch();
    }

    if ($skill !== false) {
        $skillId = (int) $skill['id'];
        $skill['mentors'] = (int) $skill['mentors'];
        $skill['learners'] = (int) $skill['learners'];
        $skill['sessionsHeld'] = (int) $skill['sessionsHeld'];
        $skill['discussions'] = (int) $skill['discussions'];

        $topicsStmt = $pdo->prepare(
            "SELECT sk.name FROM skill_relations sr JOIN skills sk ON sk.id = sr.target_skill_id
             WHERE sr.source_skill_id = ?
             ORDER BY FIELD(sr.strength, 'Strong', 'Medium', 'Related')
             LIMIT 6"
        );
        $topicsStmt->execute([$skillId]);
        $skill['relatedTopics'] = $topicsStmt->fetchAll(PDO::FETCH_COLUMN);

        $postsStmt = $pdo->prepare(
            "SELECT p.id, p.title, p.content, p.vote_score AS score, p.comment_count AS comments,
                    p.created_at, u.id AS author_id, u.full_name AS author, u.initials, u.role, d.name AS department
             FROM post_skills ps
             JOIN posts p ON p.id = ps.post_id
             JOIN users u ON u.id = p.user_id
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE ps.skill_id = ? AND p.status = 'visible'
             ORDER BY p.created_at DESC
             LIMIT 3"
        );
        $postsStmt->execute([$skillId]);
        $skill['discussionPosts'] = array_map(static function (array $row) {
            $row['role'] = ukn_role_label($row['role']);
            $row['time'] = ukn_time_ago($row['created_at']);
            $row['excerpt'] = ukn_excerpt($row['content']);
            $row['tags'] = [];
            $row['href'] = 'index.php?page=post-details&id=' . $row['id'];
            return $row;
        }, $postsStmt->fetchAll());
        $skill['discussionPosts'] = uknDecoratePosts($skill['discussionPosts']);

        $mentorsStmt = $pdo->prepare(
            "SELECT u.id, u.full_name AS name, u.initials, u.avg_rating AS rating, u.mentor_points AS points,
                    u.sessions_as_mentor AS sessions, d.name AS department
             FROM user_skills us
             JOIN users u ON u.id = us.user_id
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE us.skill_id = ? AND us.skill_type = 'teaching'
             ORDER BY u.avg_rating DESC, u.sessions_as_mentor DESC
             LIMIT 4"
        );
        $mentorsStmt->execute([$skillId]);
        $topMentors = array_map(static function (array $row) use ($skill) {
            $row['primarySkill'] = $skill['name'];
            $row['profileHref'] = ukn_route_href('mentor-profile') . '&id=' . $row['id'];
            return $row;
        }, $mentorsStmt->fetchAll());
    }
} catch (Throwable $e) {
    error_log('[UKN skill-details] ' . $e->getMessage());
    $skillDbError = true;
}

$skill = $skill === false ? [] : ($skill + ['relatedTopics' => [], 'discussionPosts' => []]);
$kind = $isMentor ? 'teaching' : 'learning';
$kindLabel = $isMentor ? 'Teaching' : 'Learning';
$mySkillNames = $isMentor ? $myTeachingSkills : $myLearningSkills;
$isAdded = $skill !== [] && in_array($skill['name'], $mySkillNames, true);
?>
<?php if ($skillDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load this skill.',
      'message' => 'Something went wrong while loading skill details. Please try again shortly.',
  ]); ?>
<?php elseif ($skill === []): ?>
  <?php ukn_empty_state([
      'icon' => 'search_off',
      'title' => 'Skill not found.',
      'message' => 'This skill may have been removed or is no longer active.',
      'action' => ['label' => 'Browse Skills', 'href' => htmlspecialchars(ukn_route_href('skills'))],
  ]); ?>
<?php else: ?>
<div class="card mb-4">
  <div class="card-body" data-skill-name="<?= htmlspecialchars($skill['name']) ?>">
    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
      <h1 class="ukn-h3 mb-0"><?= htmlspecialchars($skill['name']) ?></h1>
      <span class="ukn-eyebrow"><?= htmlspecialchars($skill['category']) ?></span>
    </div>
    <p class="ukn-body mt-2 mb-0"><?= htmlspecialchars($skill['description']) ?></p>
    <div class="d-flex align-items-center gap-4 flex-wrap mt-3 pt-3 ukn-border-top">
      <div>
        <div class="ukn-eyebrow">Mentors</div>
        <div class="ukn-display"><?= (int) $skill['mentors'] ?></div>
      </div>
      <div>
        <div class="ukn-eyebrow">Learners</div>
        <div class="ukn-display"><?= (int) $skill['learners'] ?></div>
      </div>
      <div>
        <div class="ukn-eyebrow">Sessions Held</div>
        <div class="ukn-display"><?= (int) $skill['sessionsHeld'] ?></div>
      </div>
      <div>
        <div class="ukn-eyebrow">Discussions</div>
        <div class="ukn-display"><?= (int) $skill['discussions'] ?></div>
      </div>
      <?php if (!empty($currentUser['loggedIn'])): ?>
        <?php ukn_skill_toggle_form((int) $skill['id'], $kind, $isAdded, '', 'ms-auto'); ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php if ($skill['about']): ?>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="ukn-h4">About This Skill</h2>
    <p class="ukn-body mb-0"><?= htmlspecialchars($skill['about']) ?></p>
    <?php if ($skill['relatedTopics']): ?>
      <div class="d-flex flex-wrap gap-2 mt-3">
        <?php foreach ($skill['relatedTopics'] as $topic): ?>
          <span class="ukn-tag-neutral"><?= htmlspecialchars($topic) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Top Mentors for <?= htmlspecialchars($skill['name']) ?></h2>
    <a href="<?= htmlspecialchars(ukn_route_href('find-mentors') . '&skill=' . urlencode(strtolower($skill['name']))) ?>" class="ukn-body-sm">View All Mentors</a>
  </div>
  <div class="row g-3">
    <?php foreach ($topMentors as $mentor): ?>
      <div class="col-md-6"><?php ukn_mentor_card($mentor); ?></div>
    <?php endforeach; ?>
  </div>
</div>
<?php if ($skill['discussionPosts']): ?>
<div>
  <h2 class="ukn-h4 mb-3">Related Community Discussions</h2>
  <?php foreach ($skill['discussionPosts'] as $post):
      $post += ['isOwner' => false, 'href' => 'index.php?page=post-details&id=' . $post['id']];
      ukn_post_card($post);
  endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>