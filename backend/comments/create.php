<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Steps 36 + 40: comment on a visible post, or reply to a top-level comment on it (the page
// shows two levels). One transaction: the comment, posts.comment_count, and a notification to
// the post author (comment) or the parent comment's author (reply).
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$postId = uknPostId('post_id');
$hasParent = array_key_exists('parent_id', $_POST) && uknPostString('parent_id') !== '';
$parentId = $hasParent ? uknPostId('parent_id') : null;
if ($postId === null || ($hasParent && $parentId === null)) {
    uknFailAction('Post not found.', 'home');
}
if (array_key_exists('content', $_POST) && uknPostString('content') === null) {
    uknFailAction('Invalid comment.', 'home');
}
$content = sanitizeInput(uknPostString('content') ?? '');
if (!validateLength($content, 1, UKN_COMMENT_MAX)) {
    uknFailAction('Write a comment (up to ' . UKN_COMMENT_MAX . ' characters).', 'home');
}
try {
    $pdo = getDatabaseConnection();
    $user = getCurrentUser();
    $userId = (int) $user['id'];
    $pdo->beginTransaction();
    $post = $pdo->prepare("SELECT id, user_id, title FROM posts WHERE id = ? AND status = 'visible' FOR UPDATE");
    $post->execute([$postId]);
    $post = $post->fetch();
    if ($post === false) {
        $pdo->rollBack();
        uknFailAction('Post not found.', 'home');
    }
    $recipientId = (int) $post['user_id'];
    if ($parentId !== null) {
        $parent = $pdo->prepare(
            "SELECT user_id FROM comments WHERE id = ? AND post_id = ? AND parent_id IS NULL AND status = 'visible'"
        );
        $parent->execute([$parentId, $postId]);
        $parentAuthor = $parent->fetchColumn();
        if ($parentAuthor === false) {
            $pdo->rollBack();
            uknFailAction('That comment is no longer available.', 'home');
        }
        $recipientId = (int) $parentAuthor;
    }
    $pdo->prepare('INSERT INTO comments (post_id, user_id, parent_id, content) VALUES (?, ?, ?, ?)')
        ->execute([$postId, $userId, $parentId, $content]);
    $pdo->prepare('UPDATE posts SET comment_count = comment_count + 1 WHERE id = ?')->execute([$postId]);
    uknNotify(
        $pdo, $recipientId, $userId, 'chat_bubble',
        $parentId === null
            ? "{$user['full_name']} commented on your post " . uknQuoteTitle($post['title']) . '.'
            : "{$user['full_name']} replied to your comment on " . uknQuoteTitle($post['title']) . '.',
        'index.php?page=post-details&id=' . $postId,
        'notify_community_replies'
    );
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN comments/create] ' . $e->getMessage());
    uknFailAction('Your comment could not be posted. Please try again.', 'home');
}
uknFlashToast('success', $parentId === null ? 'Comment added.' : 'Reply added.');
uknRedirectBack('home');
