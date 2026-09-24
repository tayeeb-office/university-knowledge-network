<?php
require_once __DIR__ . '/../helpers/actions.php';

// Marks one of the current user's in-progress goals as completed (progress 100).
uknRequirePostMethod();
requireLearner();
uknRequireActionCsrf('learning-goals');

$goalId = uknPostId('goal_id');
if ($goalId === null) {
    uknFailAction('Goal not found.', 'learning-goals');
}
try {
    $stmt = getDatabaseConnection()->prepare(
        "UPDATE learning_goals SET status = 'completed', progress = 100, completed_at = NOW()
         WHERE id = ? AND user_id = ? AND status = 'in-progress'"
    );
    $stmt->execute([$goalId, (int) getCurrentUser()['id']]);
    $completed = $stmt->rowCount() === 1;
} catch (Throwable $e) {
    error_log('[UKN goals/complete] ' . $e->getMessage());
    uknFailAction('The goal could not be updated. Please try again.', 'learning-goals');
}
if (!$completed) {
    uknFailAction('Goal not found or already completed.', 'learning-goals');
}
uknFlashToast('success', 'Goal marked as complete!');
uknRedirectBack('learning-goals');
