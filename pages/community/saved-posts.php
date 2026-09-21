<?php
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';

// TODO(auth): replace with the real session user id; mirrors index.php's own hardcoded
// demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

$savedPosts = [];
$savedPostsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $postsStmt = $pdo->prepare(
        "SELECT p.id, p.title, p.content, p.vote_score AS score, p.comment_count AS comments,
                p.created_at, u.id AS author_id, u.full_name AS author, u.initials, u.role,
                d.name AS department
         FROM saved_posts sp
         JOIN posts p ON p.id = sp.post_id
         JOIN users u ON u.id = p.user_id
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE sp.user_id = ? AND p.status = 'visible'
         ORDER BY sp.created_at DESC"
    );
    $postsStmt->execute([UKN_DEMO_USER_ID]);
    $savedPosts = $postsStmt->fetchAll();

    if ($savedPosts) {
        $postIds = array_column($savedPosts, 'id');
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $tagsStmt = $pdo->prepare(
            "SELECT ps.post_id, s.name FROM post_skills ps JOIN skills s ON s.id = ps.skill_id
             WHERE ps.post_id IN ($placeholders)"
        );
        $tagsStmt->execute($postIds);
        $tagsByPost = [];
        foreach ($tagsStmt->fetchAll() as $row) {
            $tagsByPost[$row['post_id']][] = $row['name'];
        }

        foreach ($savedPosts as &$post) {
            $post['score'] = (int) $post['score'];
            $post['comments'] = (int) $post['comments'];
            $post['tags'] = $tagsByPost[$post['id']] ?? [];
            $post['role'] = ukn_role_label($post['role']);
            $post['department'] = (string) ($post['department'] ?? '');
            $post['time'] = ukn_time_ago($post['created_at']);
            $post['excerpt'] = ukn_excerpt($post['content']);
            $post['href'] = 'index.php?page=post-details&id=' . $post['id'];
            $post['authorHref'] = ukn_route_href($post['role'] === 'Mentor' ? 'mentor-profile' : 'learner-profile') . '&id=' . $post['author_id'];
            $post['saved'] = true;
            $post['isOwner'] = ((int) $post['author_id'] === UKN_DEMO_USER_ID);
        }
        unset($post);
    }
} catch (Throwable $e) {
    error_log('[UKN saved-posts] ' . $e->getMessage());
    $savedPostsDbError = true;
    $savedPosts = [];
}
?>
<div class="ukn-page-header">
  <div>
    <h1>Saved Posts</h1>
    <p class="ukn-page-header__sub">Posts you've saved to read or revisit later.</p>
  </div>
</div>
<?php if ($savedPostsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load saved posts.',
      'message' => 'Something went wrong while loading your saved posts. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div data-post-list="saved">
  <?php foreach ($savedPosts as $post): ukn_post_card($post); endforeach; ?>
</div>
<div<?= $savedPosts ? ' hidden' : '' ?> data-post-list-empty>
  <?php ukn_empty_state([
      'icon' => 'bookmark_border',
      'title' => 'No saved posts yet.',
      'message' => "Save useful discussions and they'll appear here.",
      'action' => ['label' => 'Browse Community', 'href' => ukn_route_href('home')],
      'dashed' => true,
  ]); ?>
</div>
<?php endif; ?>