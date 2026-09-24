<?php
require_once __DIR__ . '/../../helpers/admin-moderation.php';

// Step 50: hide or restore any comment using the existing comments.status. Nothing is
// deleted: replies, reports and notifications keep pointing at the row. The status change
// and the posts.comment_count recount commit together (see uknAdminSetCommentStatusTx()).
uknAdminBeginAction('comments.php');
$commentId = uknPostId('comment_id');
$status = uknAdminEnum('status', ['visible', 'hidden']);
if ($commentId === null || $status === null) {
    uknAdminFail('Comment not found.', 'comments.php');
}
$outcome = uknAdminRunTx(
    static fn (PDO $pdo): array => uknAdminSetCommentStatusTx($pdo, $commentId, $status),
    'admin/comments/status'
);
if ($outcome === null) {
    uknAdminFail('Could not update the comment. Please try again.', 'comments.php');
}
$outcome['result'] === 'done'
    ? uknAdminDone($outcome['message'], 'comments.php')
    : uknAdminFail($outcome['message'], 'comments.php');
