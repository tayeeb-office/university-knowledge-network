<?php
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';
require_once __DIR__ . '/../../backend/helpers/community.php';


$myPosts = [];
$skillOptions = [];
$myPostsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $postsStmt = $pdo->prepare(
        "SELECT p.id, p.title, p.content, p.vote_score AS score, p.comment_count AS comments,
                p.created_at, u.full_name AS author, u.initials, u.role, d.name AS department
         FROM posts p
         JOIN users u ON u.id = p.user_id
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE p.user_id = ? AND p.status = 'visible'
         ORDER BY p.created_at DESC"
    );
    $postsStmt->execute([UKN_CURRENT_USER_ID]);
    $myPosts = $postsStmt->fetchAll();

    if ($myPosts) {
        $postIds = array_column($myPosts, 'id');
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

        foreach ($myPosts as &$post) {
            $post['score'] = (int) $post['score'];
            $post['comments'] = (int) $post['comments'];
            $post['tags'] = $tagsByPost[$post['id']] ?? [];
            $post['role'] = ukn_role_label($post['role']);
            $post['department'] = (string) ($post['department'] ?? '');
            $post['time'] = ukn_time_ago($post['created_at']);
            $post['excerpt'] = ukn_excerpt($post['content']);
            $post['href'] = 'index.php?page=post-details&id=' . $post['id'];
            $post['authorHref'] = ukn_route_href($post['role'] === 'Mentor' ? 'mentor-profile' : 'learner-profile') . '&id=' . UKN_CURRENT_USER_ID;
            $post['isOwner'] = true;

            foreach ($post['tags'] as $tag) {
                $skillOptions[$tag] = true;
            }
        }
        unset($post);
        $myPosts = uknDecoratePosts($myPosts);
        $skillOptions = array_keys($skillOptions);
        sort($skillOptions);
    }
} catch (Throwable $e) {
    error_log('[UKN my-posts] ' . $e->getMessage());
    $myPostsDbError = true;
    $myPosts = [];
    $skillOptions = [];
}
?>
<div class="ukn-page-header">
  <div>
    <h1>My Posts</h1>
    <p class="ukn-page-header__sub">Manage the discussions and questions you've shared.</p>
  </div>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPostModal">
    <span class="ms" aria-hidden="true">add</span>Create Post
  </button>
</div>
<div class="d-flex flex-wrap gap-2 mb-3" data-post-filters="my">
  <div class="ukn-search">
    <span class="ms" aria-hidden="true">search</span>
    <label for="myPostsSearch" class="ukn-visually-hidden">Search your posts</label>
    <input type="search" id="myPostsSearch" class="form-control" placeholder="Search your posts..." data-post-search-input>
  </div>
  <select class="form-select w-auto" aria-label="Filter by skill" data-post-skill-filter>
    <option value="">All Skills</option>
    <?php foreach ($skillOptions as $skill): ?>
      <option value="<?= htmlspecialchars(strtolower($skill)) ?>"><?= htmlspecialchars($skill) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php if ($myPostsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your posts.',
      'message' => 'Something went wrong while loading your posts. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div data-post-list="my">
  <?php foreach ($myPosts as $post): ukn_post_card($post); endforeach; ?>
</div>
<div<?= $myPosts ? ' hidden' : '' ?> data-post-list-empty>
  <?php ukn_empty_state([
      'icon' => 'forum',
      'title' => "You haven't created any posts yet.",
      'message' => 'Start a discussion or ask the community a question.',
      'action' => ['label' => 'Create Post', 'href' => '#', 'attrs' => 'data-bs-toggle="modal" data-bs-target="#createPostModal"'],
  ]); ?>
</div>
<?php endif; ?>