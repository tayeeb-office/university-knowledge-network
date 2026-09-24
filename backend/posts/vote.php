<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 37: up/down vote a visible post. One vote per user per post (post_votes primary key):
// voting the same way again removes the vote, voting the other way switches it. posts.vote_score
// moves by the difference in the same transaction, so it stays in step with post_votes.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$postId = uknPostId('post_id');
$raw = uknPostString('value');
if ($postId === null || !in_array($raw, ['1', '-1'], true)) {
    uknFailAction('Post not found.', 'home');
}
$value = (int) $raw;
try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    $pdo->beginTransaction();
    $post = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND status = 'visible' FOR UPDATE");
    $post->execute([$postId]);
    if ($post->fetchColumn() === false) {
        $pdo->rollBack();
        uknFailAction('Post not found.', 'home');
    }
    $current = $pdo->prepare('SELECT value FROM post_votes WHERE user_id = ? AND post_id = ?');
    $current->execute([$userId, $postId]);
    $old = (int) ($current->fetchColumn() ?: 0);
    $new = $old === $value ? 0 : $value;
    if ($new === 0) {
        $pdo->prepare('DELETE FROM post_votes WHERE user_id = ? AND post_id = ?')->execute([$userId, $postId]);
    } else {
        $pdo->prepare(
            'INSERT INTO post_votes (user_id, post_id, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)'
        )->execute([$userId, $postId, $new]);
    }
    $pdo->prepare('UPDATE posts SET vote_score = vote_score + ? WHERE id = ?')->execute([$new - $old, $postId]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN posts/vote] ' . $e->getMessage());
    uknFailAction('Your vote could not be saved. Please try again.', 'home');
}
uknRedirectBack('home');
