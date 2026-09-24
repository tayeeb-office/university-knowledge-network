<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/goals.php';

// Edits one of the current user's goals. Ownership is part of every query
// (id AND user_id), so another user's goal id behaves exactly like a missing one.
uknRequirePostMethod();
requireLearner();
uknRequireActionCsrf('learning-goals');

$goalId = uknPostId('goal_id');
if ($goalId === null) {
    uknFailAction('Goal not found.', 'learning-goals');
}
try {
    $pdo = getDatabaseConnection();
    $input = uknValidateGoalInput($pdo);
    if ($input['errors'] !== []) {
        uknFailAction($input['errors'][0], 'learning-goals');
    }
    $goal = $input['data'];
    $userId = (int) getCurrentUser()['id'];

    $pdo->beginTransaction();
    $current = $pdo->prepare('SELECT status FROM learning_goals WHERE id = ? AND user_id = ? FOR UPDATE');
    $current->execute([$goalId, $userId]);
    $status = $current->fetchColumn();
    if ($status === false) {
        $pdo->rollBack();
        uknFailAction('Goal not found.', 'learning-goals');
    }
    // A completed goal must stay at 100% (schema CHECK); lowering progress reopens it.
    $reopen = $status === 'completed' && $goal['progress'] < 100;
    $stmt = $pdo->prepare(
        'UPDATE learning_goals
         SET title = ?, skill_id = ?, description = ?, progress = ?, target_date = ?,
             status = IF(?, \'in-progress\', status), completed_at = IF(?, NULL, completed_at)
         WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([
        $goal['title'], $goal['skill_id'], $goal['description'], $goal['progress'], $goal['target_date'],
        (int) $reopen, (int) $reopen, $goalId, $userId,
    ]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN goals/update] ' . $e->getMessage());
    uknFailAction('The goal could not be updated. Please try again.', 'learning-goals');
}
uknFlashToast('success', 'Goal updated.');
uknRedirectBack('learning-goals');
