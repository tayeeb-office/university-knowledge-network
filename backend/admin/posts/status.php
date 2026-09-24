<?php
require_once __DIR__ . '/../../helpers/admin-moderation.php';

// Step 49: hide or restore a post using the existing posts.status ('visible' | 'hidden').
// Nothing is deleted: comments, votes, saves, skill tags and reports stay attached, and every
// community read already filters on status = 'visible', so a hidden post disappears from
// feeds, search, profiles, skill pages and its own page until it is restored.
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
