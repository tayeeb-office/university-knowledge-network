<?php
require_once __DIR__ . '/../components/post-card.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/format.php';
require_once __DIR__ . '/../backend/helpers/community.php';

$communityPosts = [];
$homeDbError = false;

try {
    $pdo = getDatabaseConnection();

    $postsStmt = $pdo->query(
        "SELECT p.id, p.title, p.content, p.vote_score AS score, p.comment_count AS comments,
                p.created_at, u.id AS author_id, u.full_name AS author, u.initials, u.role,
                d.name AS department
         FROM posts p
         JOIN users u ON u.id = p.user_id
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE p.status = 'visible'
         ORDER BY p.created_at DESC
         LIMIT 20"
    );
    $communityPosts = $postsStmt->fetchAll();

    if ($communityPosts) {
        $postIds = array_column($communityPosts, 'id');
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

        foreach ($communityPosts as &$post) {
            $post['score'] = (int) $post['score'];
            $post['comments'] = (int) $post['comments'];
            $post['tags'] = $tagsByPost[$post['id']] ?? [];
            $post['role'] = ukn_role_label($post['role']);
            $post['department'] = (string) ($post['department'] ?? '');
            $post['time'] = ukn_time_ago($post['created_at']);
            $post['excerpt'] = ukn_excerpt($post['content']);
            $post['href'] = 'index.php?page=post-details&id=' . $post['id'];
            $post['authorHref'] = ukn_route_href($post['role'] === 'Mentor' ? 'mentor-profile' : 'learner-profile') . '&id=' . $post['author_id'];
        }
        unset($post);
        // Viewer's own vote / saved / owner state (two batched queries).
        $communityPosts = uknDecoratePosts($communityPosts);
    }
} catch (Throwable $e) {
    error_log('[UKN home] ' . $e->getMessage());
    $homeDbError = true;
    $communityPosts = [];
}

$firstBatch = array_slice($communityPosts, 0, 5);
$remainingBatch = array_slice($communityPosts, 5);
?>
<div class="ukn-page-header">
  <div>
    <h1>Home</h1>
    <p class="ukn-page-header__sub">Ask questions, share what you know, and connect with learners and mentors across campus.</p>
  </div>
</div>
<?php if ($homeDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load the feed.',
      'message' => 'Something went wrong while loading community posts. Please try again shortly.',
  ]); ?>
<?php elseif (empty($communityPosts)): ?>

  <?php
  ukn_empty_state([
      'icon' => 'forum',
      'title' => 'No posts to show yet.',
      'message' => 'Start a discussion or share something with the community.',
      'action' => ['label' => 'Create Post', 'href' => '#'],
      'dashed' => true,
  ]);
  ?>
<?php else: ?>
  <div class="card mb-3">
    <div class="card-body ukn-composer__row">
      <span class="ukn-avatar ukn-avatar-sm" aria-hidden="true"><?= htmlspecialchars($currentUser['initials'] ?? '?') ?></span>
      <button type="button" class="form-control ukn-composer__field" data-bs-toggle="modal" data-bs-target="#createPostModal">
        Share something with the community&hellip;
      </button>
      <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createPostModal">
        <span class="ms" aria-hidden="true">add</span>Post
      </button>
    </div>
  </div>
  <div class="ukn-feed-controls" data-feed>
    <ul class="nav nav-tabs mb-3" aria-label="Sort community feed">
      <li class="nav-item">
        <button type="button" class="nav-link active" data-feed-tab="latest" aria-pressed="true">Latest</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" data-feed-tab="popular" aria-pressed="false">Popular</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" data-feed-tab="following" aria-pressed="false">Following</button>
      </li>
    </ul>
    <div data-feed-list>
      <?php foreach ($firstBatch as $post): ukn_post_card($post); ?>
      <?php endforeach; ?>
      <?php if ($remainingBatch): ?>
        <div data-feed-more hidden>
          <?php foreach ($remainingBatch as $post): ukn_post_card($post); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="ukn-feed-end">
      <?php if ($remainingBatch): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-load-more>Load More</button>
      <?php endif; ?>
      <p class="ukn-body-sm mb-0" data-feed-end<?= $remainingBatch ? ' hidden' : '' ?>>You&rsquo;re all caught up.</p>
    </div>
  </div>

<?php endif; ?>