<?php
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';
require_once __DIR__ . '/../../backend/helpers/community.php';

// Edit / delete controls for your own comments and replies (backend/comments/*.php).
$commentOwnerControls = static function (array $item): void {
    if (empty($item['isOwner'])) {
        return;
    }
    $id = (int) $item['id'];
    ?>
    <button type="button" class="btn-ghost" data-comment-edit-toggle>Edit</button>
    <button
      type="button" class="btn-ghost ukn-text-danger"
      data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
      data-delete-title="Delete this comment?"
      data-delete-message="Your comment and any replies to it will be removed."
      data-delete-confirm-label="Delete"
      data-delete-form="deleteComment-<?= $id ?>"
    >Delete</button>
    <?php
};
$commentOwnerForms = static function (array $item): void {
    if (empty($item['isOwner'])) {
        return;
    }
    $id = (int) $item['id'];
    ?>
    <form data-comment-edit-form action="backend/comments/update.php" method="post" hidden class="mt-3" novalidate>
      <?= csrfField() ?>
      <?= uknReturnToField() ?>
      <input type="hidden" name="comment_id" value="<?= $id ?>">
      <label class="ukn-visually-hidden" for="editComment-<?= $id ?>">Edit your comment</label>
      <textarea class="form-control form-control-sm" id="editComment-<?= $id ?>" name="content" maxlength="<?= UKN_COMMENT_MAX ?>"><?= htmlspecialchars($item['text']) ?></textarea>
      <div class="ukn-field-message is-invalid mt-1" data-reply-error hidden>
        <span class="ms" aria-hidden="true">error</span>Write something before saving.
      </div>
      <div class="d-flex gap-2 mt-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-reply-cancel>Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save</button>
      </div>
    </form>
    <form id="deleteComment-<?= $id ?>" action="backend/comments/delete.php" method="post" hidden>
      <?= csrfField() ?>
      <?= uknReturnToField() ?>
      <input type="hidden" name="comment_id" value="<?= $id ?>">
    </form>
    <?php
};
// Report a comment or reply someone else wrote (modals/report-modal.php → backend/reports/create.php).
$commentReportButton = static function (array $item): void {
    if (UKN_CURRENT_USER_ID <= 0 || !empty($item['isOwner'])) {
        return;
    }
    ?>
    <button
      type="button" class="btn-ghost"
      data-bs-toggle="modal" data-bs-target="#reportModal"
      data-report-target-type="comment" data-report-target-id="<?= (int) $item['id'] ?>"
    >Report</button>
    <?php
};

$requestedId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;
$post = false;
$comments = [];
$postDbError = false;

try {
    $pdo = getDatabaseConnection();

    $selectBase = "SELECT p.id, p.title, p.content AS excerpt, p.vote_score AS score, p.comment_count AS comments,
            p.created_at, u.id AS author_id, u.full_name AS author, u.initials, u.avatar_path, u.role, d.name AS department
        FROM posts p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN departments d ON d.id = u.department_id
        WHERE p.status = 'visible' ";

    $stmt = $pdo->prepare($selectBase . "AND p.id = ?");
    $stmt->execute([$requestedId]);
    // A missing, deleted or hidden post shows "Post not found" (no fallback to another post).
    $post = $stmt->fetch();

    if ($post !== false) {
        $postId = (int) $post['id'];
        $post['score'] = (int) $post['score'];
        $post['comments'] = (int) $post['comments'];
        $post['department'] = (string) ($post['department'] ?? '');
        $post['role'] = ukn_role_label($post['role']);
        $post['time'] = ukn_time_ago($post['created_at']);
        $post['authorHref'] = ukn_route_href($post['role'] === 'Mentor' ? 'mentor-profile' : 'learner-profile') . '&id=' . $post['author_id'];
        $post['content'] = $post['excerpt'];
        // Viewer's own vote / saved / owner state.
        $post = uknDecoratePosts([$post])[0];

        $tagsStmt = $pdo->prepare(
            "SELECT s.name FROM post_skills ps JOIN skills s ON s.id = ps.skill_id WHERE ps.post_id = ?"
        );
        $tagsStmt->execute([$postId]);
        $post['tags'] = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);

        $commentsStmt = $pdo->prepare(
            "SELECT c.id, c.parent_id, c.content AS text, c.created_at, u.id AS author_id,
                    u.full_name AS author, u.initials, u.avatar_path, u.role
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
            $isMentorAuthor = $row['role'] === 'dual';
            $replies = array_map(static function (array $reply) {
                return [
                    'id' => (int) $reply['id'],
                    'isOwner' => UKN_CURRENT_USER_ID > 0 && (int) $reply['author_id'] === UKN_CURRENT_USER_ID,
                    'author' => $reply['author'],
                    'initials' => $reply['initials'],
                    'avatar_path' => $reply['avatar_path'],
                    'role' => ukn_role_label($reply['role']),
                    'time' => ukn_time_ago($reply['created_at']),
                    'text' => $reply['text'],
                ];
            }, $byParent[$row['id']] ?? []);
            return [
                'id' => (int) $row['id'],
                'isOwner' => UKN_CURRENT_USER_ID > 0 && (int) $row['author_id'] === UKN_CURRENT_USER_ID,
                'author' => $row['author'],
                'initials' => $row['initials'],
                'avatar_path' => $row['avatar_path'],
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
      <?php if (empty($currentUser['loggedIn'])): ?>
        <p class="ukn-body-sm mb-0"><a href="<?= htmlspecialchars(ukn_route_href('login')) ?>">Log in</a> to join the discussion.</p>
      <?php else: ?>
      <form data-comment-form action="backend/comments/create.php" method="post" novalidate>
        <?= csrfField() ?>
        <?= uknReturnToField() ?>
        <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
        <div class="ukn-cluster align-items-start" data-comment-composer data-current-user-name="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" data-current-user-initials="<?= htmlspecialchars($currentUser['initials'] ?? '') ?>">
          <?= uknAvatarHtml($currentUser['avatarPath'] ?? null, (string) ($currentUser['initials'] ?? ''), 'ukn-avatar flex-shrink-0') ?>
          <div class="flex-fill ukn-min-w-0">
            <label for="newCommentText" class="ukn-visually-hidden">Add to the discussion</label>
            <textarea class="form-control" id="newCommentText" name="content" maxlength="<?= UKN_COMMENT_MAX ?>" placeholder="Add to the discussion..."></textarea>
            <div class="ukn-field-message is-invalid mt-1" data-comment-error hidden>
              <span class="ms" aria-hidden="true">error</span>Write a comment before posting.
            </div>
            <div class="d-flex justify-content-end mt-2">
              <button type="submit" class="btn btn-primary btn-sm">Comment</button>
            </div>
          </div>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <div data-comments-list>
    <?php foreach ($comments as $comment): ?>
      <div data-comment class="mb-2">
        <div class="card">
          <div class="card-body">
            <div class="ukn-cluster mb-2">
              <?= uknAvatarHtml($comment['avatar_path'], (string) $comment['initials'], 'ukn-avatar ukn-avatar-sm') ?>
              <div>
                <a href="<?= htmlspecialchars($comment['authorHref']) ?>" class="fw-bold text-body"><?= htmlspecialchars($comment['author']) ?></a>
                <span class="ukn-role-chip"><?= htmlspecialchars($comment['role']) ?></span>
                <div class="ukn-body-sm"><?= htmlspecialchars($comment['time']) ?></div>
              </div>
            </div>
            <p class="ukn-body-sm mb-2"><?= htmlspecialchars($comment['text']) ?></p>
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <?php if (!empty($currentUser['loggedIn'])): ?>
                <button type="button" class="btn-ghost" data-comment-reply-toggle>Reply</button>
              <?php endif; ?>
              <?php $commentReportButton($comment); ?>
              <?php $commentOwnerControls($comment); ?>
            </div>
            <?php $commentOwnerForms($comment); ?>
            <?php if (!empty($currentUser['loggedIn'])): ?>
            <form data-reply-form action="backend/comments/create.php" method="post" hidden class="mt-3" novalidate>
              <?= csrfField() ?>
              <?= uknReturnToField() ?>
              <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
              <input type="hidden" name="parent_id" value="<?= (int) $comment['id'] ?>">
              <label class="ukn-visually-hidden" for="replyTo-<?= (int) $comment['id'] ?>">Reply to <?= htmlspecialchars($comment['author']) ?></label>
              <textarea class="form-control form-control-sm" id="replyTo-<?= (int) $comment['id'] ?>" name="content" maxlength="<?= UKN_COMMENT_MAX ?>" placeholder="Write a reply..."></textarea>
              <div class="ukn-field-message is-invalid mt-1" data-reply-error hidden>
                <span class="ms" aria-hidden="true">error</span>Write a reply before posting.
              </div>
              <div class="d-flex gap-2 mt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-reply-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Reply</button>
              </div>
            </form>
            <?php endif; ?>
          </div>
        </div>
        <div data-replies-list class="mt-2">
          <?php foreach ($comment['replies'] as $reply): ?>
            <div class="card mt-2 ms-2 ms-md-4" data-reply>
              <div class="card-body">
                <div class="ukn-cluster mb-2">
                  <?= uknAvatarHtml($reply['avatar_path'], (string) $reply['initials'], 'ukn-avatar ukn-avatar-sm') ?>
                  <div>
                    <strong class="text-body"><?= htmlspecialchars($reply['author']) ?></strong>
                    <span class="ukn-role-chip"><?= htmlspecialchars($reply['role']) ?></span>
                    <div class="ukn-body-sm"><?= htmlspecialchars($reply['time']) ?></div>
                  </div>
                </div>
                <p class="ukn-body-sm mb-0"><?= htmlspecialchars($reply['text']) ?></p>
                <?php if (!empty($reply['isOwner'])): ?>
                  <div class="d-flex align-items-center gap-3 flex-wrap mt-2"><?php $commentOwnerControls($reply); ?></div>
                  <?php $commentOwnerForms($reply); ?>
                <?php elseif (!empty($currentUser['loggedIn'])): ?>
                  <div class="d-flex align-items-center gap-3 flex-wrap mt-2"><?php $commentReportButton($reply); ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php if (!empty($currentUser['loggedIn'])): ?>
  <?php include __DIR__ . '/../../modals/report-modal.php'; ?>
<?php endif; ?>
<?php endif; ?>