<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/goals.php';

// Creates a learning goal owned by the current user (active learner).
uknRequirePostMethod();
requireLearner();
uknRequireActionCsrf('learning-goals');

try {
    $pdo = getDatabaseConnection();
    $input = uknValidateGoalInput($pdo);
    if ($input['errors'] !== []) {
        uknFailAction($input['errors'][0], 'learning-goals');
    }
    $goal = $input['data'];
    $stmt = $pdo->prepare(
        'INSERT INTO learning_goals (user_id, skill_id, title, description, progress, target_date)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int) getCurrentUser()['id'], $goal['skill_id'], $goal['title'],
        $goal['description'], $goal['progress'], $goal['target_date'],
    ]);
} catch (Throwable $e) {
    error_log('[UKN goals/create] ' . $e->getMessage());
    uknFailAction('The goal could not be created. Please try again.', 'learning-goals');
}
uknFlashToast('success', 'Goal created.');
uknRedirectBack('learning-goals');
