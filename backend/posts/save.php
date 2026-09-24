<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 38: save / unsave a post for the logged-in user. Both are idempotent and only ever touch
// the current user's own saved_posts row.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('saved-posts');

$postId = uknPostId('post_id');
$action = uknPostString('action');
if ($postId === null || !in_array($action, ['save', 'unsave'], true)) {
    uknFailAction('Post not found.', 'saved-posts');
}
try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    if ($action === 'save') {
        $post = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND status = 'visible'");
        $post->execute([$postId]);
        if ($post->fetchColumn() === false) {
            uknFailAction('Post not found.', 'saved-posts');
        }
        $pdo->prepare('INSERT IGNORE INTO saved_posts (user_id, post_id) VALUES (?, ?)')->execute([$userId, $postId]);
    } else {
        $pdo->prepare('DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?')->execute([$userId, $postId]);
    }
} catch (Throwable $e) {
    error_log('[UKN posts/save] ' . $e->getMessage());
    uknFailAction('Your saved posts could not be updated. Please try again.', 'saved-posts');
}
uknFlashToast('success', $action === 'save' ? 'Post saved.' : 'Post removed from saved items.');
uknRedirectBack('saved-posts');
