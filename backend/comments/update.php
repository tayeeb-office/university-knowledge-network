<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 36: the author edits their own visible comment or reply.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$commentId = uknPostId('comment_id');
if ($commentId === null || (array_key_exists('content', $_POST) && uknPostString('content') === null)) {
    uknFailAction('Comment not found.', 'home');
}
$content = sanitizeInput(uknPostString('content') ?? '');
if (!validateLength($content, 1, UKN_COMMENT_MAX)) {
    uknFailAction('Write a comment (up to ' . UKN_COMMENT_MAX . ' characters).', 'home');
}
try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    $own = $pdo->prepare("SELECT 1 FROM comments WHERE id = ? AND user_id = ? AND status = 'visible'");
    $own->execute([$commentId, $userId]);
    if ($own->fetchColumn() === false) {
        uknFailAction('Comment not found.', 'home');
    }
    $pdo->prepare('UPDATE comments SET content = ? WHERE id = ? AND user_id = ?')->execute([$content, $commentId, $userId]);
} catch (Throwable $e) {
    error_log('[UKN comments/update] ' . $e->getMessage());
    uknFailAction('Your comment could not be saved. Please try again.', 'home');
}
uknFlashToast('success', 'Comment updated.');
uknRedirectBack('home');
