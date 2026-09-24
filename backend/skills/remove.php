<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/skills.php';

// Removes a skill from the current user's learning/teaching list. The row is matched on
// (current user, skill, type), so another user's rows can never be targeted.
uknRequirePostMethod();
requireLogin();
$type = uknPostString('type');
if (!in_array($type, UKN_SKILL_TYPES, true)) {
    uknFailAction('Invalid skill request.', 'skills');
}
$type === 'teaching' ? requireMentor() : requireLearner();
uknRequireActionCsrf('skills');

$listRoute = $type === 'teaching' ? 'teaching-skills' : 'learning-skills';
$skillId = uknPostId('skill_id');
if ($skillId === null) {
    uknFailAction('Choose a valid skill.', $listRoute);
}
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('DELETE FROM user_skills WHERE user_id = ? AND skill_id = ? AND skill_type = ?');
    $stmt->execute([(int) getCurrentUser()['id'], $skillId, $type]);
    $removed = $stmt->rowCount() === 1;
} catch (Throwable $e) {
    error_log('[UKN skills/remove] ' . $e->getMessage());
    uknFailAction('The skill could not be removed. Please try again.', $listRoute);
}
if (!$removed) {
    uknFailAction("That skill is not in your {$type} skills.", $listRoute);
}
uknFlashToast('success', "Skill removed from your {$type} skills.");
uknRedirectBack($listRoute);
