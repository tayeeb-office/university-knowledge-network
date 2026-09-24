<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 34: the logged-in user publishes a post (post + its skill tags in one transaction).
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

try {
    $pdo = getDatabaseConnection();
    [$post, $error] = uknValidatePostInput($pdo);
    if ($error !== null) {
        uknFailAction($error, 'home');
    }
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)')
        ->execute([(int) getCurrentUser()['id'], $post['title'], $post['content']]);
    $postId = (int) $pdo->lastInsertId();
    $tag = $pdo->prepare('INSERT INTO post_skills (post_id, skill_id) VALUES (?, ?)');
    foreach ($post['skill_ids'] as $skillId) {
        $tag->execute([$postId, $skillId]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN posts/create] ' . $e->getMessage());
    uknFailAction('Your post could not be published. Please try again.', 'home');
}
uknFlashToast('success', 'Post published.');
header('Location: ' . uknRouteUrl('post-details') . '&id=' . $postId, true, 302);
exit;
