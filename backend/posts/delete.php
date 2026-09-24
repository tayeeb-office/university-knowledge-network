<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 35: the author deletes their own post. Comments, votes, saves and skill tags go with it
// (ON DELETE CASCADE); ledger rows keep their history (related_post_id is set to NULL).
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('my-posts');

$postId = uknPostId('post_id');
if ($postId === null) {
    uknFailAction('Post not found.', 'my-posts');
}
try {
    $stmt = getDatabaseConnection()->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$postId, (int) getCurrentUser()['id']]);
    $deleted = $stmt->rowCount() === 1;
} catch (Throwable $e) {
    error_log('[UKN posts/delete] ' . $e->getMessage());
    uknFailAction('The post could not be deleted. Please try again.', 'my-posts');
}
if (!$deleted) {
    uknFailAction('Post not found.', 'my-posts');
}
uknFlashToast('success', 'Post deleted.');
// The post's own page no longer exists, so never go "back" to it.
if (strpos((string) uknPostString('return_to'), 'page=post-details') !== false) {
    uknRedirectToRoute('my-posts');
}
uknRedirectBack('my-posts');
