<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 36: the author deletes their own comment; every reply beneath it, at any depth, goes with
// it. The subtree is deleted level by level, deepest first, so this never depends on InnoDB's
// ON DELETE CASCADE, which stops at 15 nested levels. posts.comment_count is recounted in the
// same transaction.
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
    $subtree = $pdo->prepare(
        'WITH RECURSIVE subtree AS (
             SELECT id, 0 AS depth FROM comments WHERE id = ?
             UNION ALL
             SELECT c.id, s.depth + 1 FROM comments c JOIN subtree s ON c.parent_id = s.id
         )
         SELECT id, depth FROM subtree'
    );
    $subtree->execute([$commentId]);
    $idsByDepth = [];
    foreach ($subtree->fetchAll() as $row) {
        $idsByDepth[(int) $row['depth']][] = (int) $row['id'];
    }
    krsort($idsByDepth);
    unset($idsByDepth[0]);
    foreach ($idsByDepth as $ids) {
        $pdo->prepare('DELETE FROM comments WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')')
            ->execute($ids);
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
