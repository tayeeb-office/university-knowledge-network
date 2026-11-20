<?php
require_once __DIR__ . '/../../helpers/admin-moderation.php';

uknAdminBeginAction('posts.php');
$postId = uknPostId('post_id');
$status = uknAdminEnum('status', ['visible', 'hidden']);
if ($postId === null || $status === null) {
    uknAdminFail('Post not found.', 'posts.php');
}
$outcome = uknAdminRunTx(
    static fn (PDO $pdo): array => uknAdminSetPostStatusTx($pdo, $postId, $status),
    'admin/posts/status'
);
if ($outcome === null) {
    uknAdminFail('Could not update the post. Please try again.', 'posts.php');
}
$outcome['result'] === 'done'
    ? uknAdminDone($outcome['message'], 'posts.php')
    : uknAdminFail($outcome['message'], 'posts.php');
