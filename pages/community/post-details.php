<?php
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';

$requestedId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;
$post = false;
$comments = [];
$postDbError = false;

try {
    $pdo = getDatabaseConnection();

    $selectBase = "SELECT p.id, p.title, p.content AS excerpt, p.vote_score AS score, p.comment_count AS comments,
            p.created_at, u.id AS author_id, u.full_name AS author, u.initials, u.role, d.name AS department
        FROM posts p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN departments d ON d.id = u.department_id
        WHERE p.status = 'visible' ";

    $stmt = $pdo->prepare($selectBase . "AND p.id = ?");
    $stmt->execute([$requestedId]);
    $post = $stmt->fetch();

    if ($post === false) {
        // No matching/visible post for the requested id: fall back to the most recent
        // visible post, mirroring the page's previous "always show something" mock behaviour.
        $stmt = $pdo->prepare($selectBase . "ORDER BY p.created_at DESC LIMIT 1");
        $stmt->execute();
        $post = $stmt->fetch();
    }

    if ($post !== false) {
        $postId = (int) $post['id'];
        $post['score'] = (int) $post['score'];
        $post['comments'] = (int) $post['comments'];
        $post['department'] = (string) ($post['department'] ?? '');
        $post['role'] = ukn_role_label($post['role']);
        $post['time'] = ukn_time_ago($post['created_at']);
        $post['authorHref'] = ukn_route_href($post['role'] === 'Mentor' ? 'mentor-profile' : 'learner-profile') . '&id=' . $post['author_id'];
        // TODO(auth): ownership/saved state need the current session user; not determinable yet.
        $post['isOwner'] = false;
        $post['saved'] = false;

        $tagsStmt = $pdo->prepare(
            "SELECT s.name FROM post_skills ps JOIN skills s ON s.id = ps.skill_id WHERE ps.post_id = ?"
        );
        $tagsStmt->execute([$postId]);
        $post['tags'] = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);

        $commentsStmt = $pdo->prepare(
            "SELECT c.id, c.parent_id, c.content AS text, c.created_at, u.id AS author_id,
                    u.full_name AS author, u.initials, u.role
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.post_id = ? AND c.status = 'visible'
             ORDER BY c.created_at ASC"
        );
        $commentsStmt->execute([$postId]);
        $rows = $commentsStmt->fetchAll();

        $byParent = [];
        foreach ($rows as $row) {
            $byParent[$row['parent_id'] ?? 0][] = $row;
        }
        $comments = array_map(static function (array $row) use ($byParent) {
            $isMentorAuthor = in_array($row['role'], ['mentor', 'dual'], true);
            $replies = array_map(static function (array $reply) {
                return [
                    'author' => $reply['author'],
                    'initials' => $reply['initials'],
                    'role' => ukn_role_label($reply['role']),
                    'time' => ukn_time_ago($reply['created_at']),
                    'text' => $reply['text'],
                ];
            }, $byParent[$row['id']] ?? []);
            return [
                'author' => $row['author'],
                'initials' => $row['initials'],
                'role' => ukn_role_label($row['role']),
                'authorHref' => ukn_route_href($isMentorAuthor ? 'mentor-profile' : 'learner-profile') . '&id=' . $row['author_id'],
                'time' => ukn_time_ago($row['created_at']),
                'text' => $row['text'],
                'replies' => $replies,
            ];
        }, $byParent[0] ?? []);
    }
} catch (Throwable $e) {
    error_log('[UKN post-details] ' . $e->getMessage());
    $postDbError = true;
}
?>
<?php if ($postDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load this post.',
      'message' => 'Something went wrong while loading this discussion. Please try again shortly.',
  ]); ?>
<?php elseif ($post === false): ?>
  <?php ukn_empty_state([
      'icon' => 'forum',
      'title' => 'Post not found.',
      'message' => 'This post may have been removed.',
      'action' => ['label' => 'Back to Community', 'href' => htmlspecialchars(ukn_route_href('home'))],
  ]); ?>
<?php else: ?>
<div data-post-detail>
  <a href="<?= htmlspecialchars(ukn_route_href('home')) ?>" class="ukn-body-sm d-inline-flex align-items-center gap-1 mb-3">
    <span class="ms" aria-hidden="true">arrow_back</span>Back to Community
  </a>
  <?php ukn_post_card($post, ['variant' => 'detail']); ?>
  <div class="card mb-3" id="comments">
    <div class="card-body">
      <h2 class="ukn-h4 mb-3"><span data-comments-heading-count><?= (int) $post['comments'] ?></span> Comments</h2>
      <form data-comment-form novalidate>
        <div class="ukn-cluster align-items-start" data-comment-composer data-current-user-name="<?= htmlspecialchars($currentUser['name'] ?? 'Nabila Rahman') ?>" data-current-user-initials="<?= htmlspecialchars($currentUser['initials'] ?? 'NR') ?>">
          <span class="ukn-avatar flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($currentUser['initials'] ?? 'NR') ?></span>
          <div class="flex-fill ukn-min-w-0">
            <label for="newCommentText" class="ukn-visually-hidden">Add to the discussion</label>
            <textarea class="form-control" id="newCommentText" placeholder="Add to the discussion..."></textarea>
            <div class="ukn-field-message is-invalid mt-1" data-comment-error hidden>
              <span class="ms" aria-hidden="true">error</span>Write a comment before posting.
            </div>
            <div class="d-flex justify-content-end mt-2">
              <button type="submit" class="btn btn-primary btn-sm">Comment</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
  <div data-comments-list>
    <?php foreach ($comments as $comment): ?>
      <div data-comment class="mb-2">
        <div class="card">
          <div class="card-body">
            <div class="ukn-cluster mb-2">
              <span class="ukn-avatar ukn-avatar-sm" aria-hidden="true"><?= htmlspecialchars($comment['initials']) ?></span>
              <div>
                <a href="<?= htmlspecialchars($comment['authorHref']) ?>" class="fw-bold text-body"><?= htmlspecialchars($comment['author']) ?></a>
                <span class="ukn-role-chip"><?= htmlspecialchars($comment['role']) ?></span>
                <div class="ukn-body-sm"><?= htmlspecialchars($comment['time']) ?></div>
              </div>
            </div>
            <p class="ukn-body-sm mb-2"><?= htmlspecialchars($comment['text']) ?></p>
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <button type="button" class="btn-ghost" data-comment-reply-toggle>Reply</button>
              <button type="button" class="btn-ghost" data-comment-report>Report</button>
            </div>
            <form data-reply-form hidden class="mt-3" novalidate>
              <label class="ukn-visually-hidden">Reply to <?= htmlspecialchars($comment['author']) ?></label>
              <textarea class="form-control form-control-sm" placeholder="Write a reply..."></textarea>
              <div class="ukn-field-message is-invalid mt-1" data-reply-error hidden>
                <span class="ms" aria-hidden="true">error</span>Write a reply before posting.
              </div>
              <div class="d-flex gap-2 mt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-reply-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Reply</button>
              </div>
            </form>
          </div>
        </div>
        <div data-replies-list class="mt-2">
          <?php foreach ($comment['replies'] as $reply): ?>
            <div class="card mt-2 ms-2 ms-md-4" data-reply>
              <div class="card-body">
                <div class="ukn-cluster mb-2">
                  <span class="ukn-avatar ukn-avatar-sm" aria-hidden="true"><?= htmlspecialchars($reply['initials']) ?></span>
                  <div>
                    <strong class="text-body"><?= htmlspecialchars($reply['author']) ?></strong>
                    <span class="ukn-role-chip"><?= htmlspecialchars($reply['role']) ?></span>
                    <div class="ukn-body-sm"><?= htmlspecialchars($reply['time']) ?></div>
                  </div>
                </div>
                <p class="ukn-body-sm mb-0"><?= htmlspecialchars($reply['text']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>