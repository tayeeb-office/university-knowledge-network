<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 36: the author deletes their own comment (its replies go with it, ON DELETE CASCADE).
// posts.comment_count is recounted in the same transaction.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$commentId = uknPostId('comment_id');
if ($commentId === null) {
    uknFailAction('Comment not found.', 'home');
}
try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    $pdo->beginTransaction();
    $own = $pdo->prepare('SELECT post_id FROM comments WHERE id = ? AND user_id = ? FOR UPDATE');
    $own->execute([$commentId, $userId]);
    $postId = $own->fetchColumn();
    if ($postId === false) {
        $pdo->rollBack();
        uknFailAction('Comment not found.', 'home');
    }
    $pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?')->execute([$commentId, $userId]);
    // Recounted rather than decremented so admin-hidden comments (Step 50) never skew it.
    uknRecountPostComments($pdo, (int) $postId);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN comments/delete] ' . $e->getMessage());
    uknFailAction('The comment could not be deleted. Please try again.', 'home');
}
uknFlashToast('success', 'Comment deleted.');
uknRedirectBack('home');
