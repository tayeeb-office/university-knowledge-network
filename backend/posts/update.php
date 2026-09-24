<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 35: the author edits their own post (title, content, skill tags) in one transaction.
// Someone else's post id behaves exactly like a missing one.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('my-posts');

$postId = uknPostId('post_id');
if ($postId === null) {
    uknFailAction('Post not found.', 'my-posts');
}
try {
    $pdo = getDatabaseConnection();
    [$post, $error] = uknValidatePostInput($pdo);
    if ($error !== null) {
        uknFailAction($error, 'my-posts');
    }
    $userId = (int) getCurrentUser()['id'];
    $pdo->beginTransaction();
    $own = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ? AND status = 'visible' FOR UPDATE");
    $own->execute([$postId, $userId]);
    if ($own->fetchColumn() === false) {
        $pdo->rollBack();
        uknFailAction('Post not found.', 'my-posts');
    }
    $pdo->prepare('UPDATE posts SET title = ?, content = ? WHERE id = ? AND user_id = ?')
        ->execute([$post['title'], $post['content'], $postId, $userId]);
    $pdo->prepare('DELETE FROM post_skills WHERE post_id = ?')->execute([$postId]);
    $tag = $pdo->prepare('INSERT INTO post_skills (post_id, skill_id) VALUES (?, ?)');
    foreach ($post['skill_ids'] as $skillId) {
        $tag->execute([$postId, $skillId]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN posts/update] ' . $e->getMessage());
    uknFailAction('Your post could not be saved. Please try again.', 'my-posts');
}
uknFlashToast('success', 'Post updated.');
uknRedirectBack('my-posts');
