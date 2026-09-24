<?php
// Phase H (Steps 48–51): moderation state changes shared by the direct admin actions
// (users/suspend, posts/status, comments/status) and report review (reports/review), so a
// report can apply exactly the same rules inside its own transaction.
// Every function must run inside the caller's transaction; it locks what it changes and
// returns ['result' => 'done'|'already'|'missing'|'refused', 'message' => string, 'label' => string].
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/community.php';

if (!function_exists('uknAdminSuspendUserTx')) {
    /**
     * users.status = 'suspended' with the required suspend_reason. Refused for the acting
     * admin's own account and for the last usable admin (active, verified, is_admin); the
     * admin rows are locked while that is checked. Posts, sessions, ratings and points stay.
     */
    function uknAdminSuspendUserTx(PDO $pdo, int $adminId, int $userId, string $reason): array
    {
        if ($userId === $adminId) {
            return ['result' => 'refused', 'message' => 'You cannot suspend your own account.', 'label' => ''];
        }
        $stmt = $pdo->prepare('SELECT full_name, status, is_admin FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$userId]);
        $target = $stmt->fetch();
        if ($target === false) {
            return ['result' => 'missing', 'message' => 'User not found.', 'label' => ''];
        }
        if ($target['status'] === 'suspended') {
            return ['result' => 'already', 'message' => $target['full_name'] . ' is already suspended.', 'label' => $target['full_name']];
        }
        if ((int) $target['is_admin'] === 1) {
            $others = $pdo->prepare(
                "SELECT COUNT(*) FROM users
                 WHERE is_admin = 1 AND status = 'active' AND email_verified_at IS NOT NULL AND id <> ?
                 FOR UPDATE"
            );
            $others->execute([$userId]);
            if ((int) $others->fetchColumn() === 0) {
                return ['result' => 'refused', 'message' => 'This is the last active administrator and cannot be suspended.', 'label' => $target['full_name']];
            }
        }
        $pdo->prepare("UPDATE users SET status = 'suspended', suspend_reason = ? WHERE id = ?")
            ->execute([mb_substr($reason, 0, 255, 'UTF-8'), $userId]);
        return ['result' => 'done', 'message' => $target['full_name'] . ' suspended.', 'label' => $target['full_name']];
    }
}
if (!function_exists('uknAdminSetPostStatusTx')) {
    /** posts.status = visible|hidden. Nothing attached to the post is removed. */
    function uknAdminSetPostStatusTx(PDO $pdo, int $postId, string $status): array
    {
        $stmt = $pdo->prepare('SELECT title, status FROM posts WHERE id = ? FOR UPDATE');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        if ($post === false) {
            return ['result' => 'missing', 'message' => 'Post not found.', 'label' => ''];
        }
        if ($post['status'] === $status) {
            return ['result' => 'already', 'message' => 'This post is already ' . $status . '.', 'label' => $post['title']];
        }
        $pdo->prepare('UPDATE posts SET status = ? WHERE id = ?')->execute([$status, $postId]);
        return ['result' => 'done', 'message' => $status === 'hidden' ? 'Post hidden from the community.' : 'Post restored.', 'label' => $post['title']];
    }
}
if (!function_exists('uknAdminSetCommentStatusTx')) {
    /**
     * comments.status = visible|hidden, then posts.comment_count is recounted to what the
     * community sees (replies under a hidden comment are not shown). The post row is locked
     * before the comment so this orders the same way as the Step 36 comment writers.
     */
    function uknAdminSetCommentStatusTx(PDO $pdo, int $commentId, string $status): array
    {
        $find = $pdo->prepare('SELECT post_id FROM comments WHERE id = ?');
        $find->execute([$commentId]);
        $postId = $find->fetchColumn();
        if ($postId === false) {
            return ['result' => 'missing', 'message' => 'Comment not found.', 'label' => ''];
        }
        $pdo->prepare('SELECT id FROM posts WHERE id = ? FOR UPDATE')->execute([(int) $postId]);
        $lock = $pdo->prepare('SELECT status FROM comments WHERE id = ? FOR UPDATE');
        $lock->execute([$commentId]);
        $current = $lock->fetchColumn();
        if ($current === false) {
            return ['result' => 'missing', 'message' => 'Comment not found.', 'label' => ''];
        }
        if ($current === $status) {
            return ['result' => 'already', 'message' => 'This comment is already ' . $status . '.', 'label' => ''];
        }
        $pdo->prepare('UPDATE comments SET status = ? WHERE id = ?')->execute([$status, $commentId]);
        uknRecountPostComments($pdo, (int) $postId);
        return ['result' => 'done', 'message' => $status === 'hidden' ? 'Comment hidden from the community.' : 'Comment restored.', 'label' => ''];
    }
}
if (!function_exists('uknAdminRunTx')) {
    /**
     * Runs $work (PDO → result array) in one transaction: commits on 'done', rolls back
     * otherwise, and on an exception rolls back and returns null (the caller shows a generic
     * error; the exception is logged under $logTag).
     */
    function uknAdminRunTx(callable $work, string $logTag): ?array
    {
        try {
            $pdo = getDatabaseConnection();
            $pdo->beginTransaction();
            $outcome = $work($pdo);
            if ($outcome['result'] === 'done') {
                $pdo->commit();
            } else {
                $pdo->rollBack();
            }
            return $outcome;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[UKN ' . $logTag . '] ' . $e->getMessage());
            return null;
        }
    }
}
