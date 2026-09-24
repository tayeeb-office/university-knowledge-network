<?php
require_once __DIR__ . '/../helpers/actions.php';

// Deletes one of the current user's goals (ownership enforced in the WHERE clause).
uknRequirePostMethod();
requireLearner();
uknRequireActionCsrf('learning-goals');

$goalId = uknPostId('goal_id');
if ($goalId === null) {
    uknFailAction('Goal not found.', 'learning-goals');
}
try {
    $stmt = getDatabaseConnection()->prepare('DELETE FROM learning_goals WHERE id = ? AND user_id = ?');
    $stmt->execute([$goalId, (int) getCurrentUser()['id']]);
    $deleted = $stmt->rowCount() === 1;
} catch (Throwable $e) {
    error_log('[UKN goals/delete] ' . $e->getMessage());
    uknFailAction('The goal could not be deleted. Please try again.', 'learning-goals');
}
if (!$deleted) {
    uknFailAction('Goal not found.', 'learning-goals');
}
uknFlashToast('success', 'Goal deleted.');
uknRedirectBack('learning-goals');
