<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/skills.php';

// Updates the proficiency of one of the current user's learning/teaching skills.
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
$proficiency = uknPostString('proficiency');
if ($skillId === null || !in_array($proficiency, UKN_PROFICIENCY_LEVELS, true)) {
    uknFailAction('Choose a valid skill and level.', $listRoute);
}
try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    $owned = $pdo->prepare('SELECT 1 FROM user_skills WHERE user_id = ? AND skill_id = ? AND skill_type = ?');
    $owned->execute([$userId, $skillId, $type]);
    if ($owned->fetchColumn() === false) {
        uknFailAction("That skill is not in your {$type} skills.", $listRoute);
    }
    $stmt = $pdo->prepare('UPDATE user_skills SET proficiency = ? WHERE user_id = ? AND skill_id = ? AND skill_type = ?');
    $stmt->execute([$proficiency, $userId, $skillId, $type]);
} catch (Throwable $e) {
    error_log('[UKN skills/update] ' . $e->getMessage());
    uknFailAction('The skill could not be updated. Please try again.', $listRoute);
}
uknFlashToast('success', "Level updated to {$proficiency}.");
uknRedirectBack($listRoute);
