<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/skills.php';

// Adds a skill to the current user's learning (active learner) or teaching (active mentor) list.
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
    $skillName = uknFindActiveSkillName($pdo, $skillId);
    if ($skillName === null) {
        uknFailAction('That skill is not available.', $listRoute);
    }
    $stmt = $pdo->prepare('INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES (?, ?, ?)');
    $stmt->execute([(int) getCurrentUser()['id'], $skillId, $type]);
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        uknFailAction("{$skillName} is already in your {$type} skills.", $listRoute);
    }
    error_log('[UKN skills/add] ' . $e->getMessage());
    uknFailAction('The skill could not be added. Please try again.', $listRoute);
} catch (Throwable $e) {
    error_log('[UKN skills/add] ' . $e->getMessage());
    uknFailAction('The skill could not be added. Please try again.', $listRoute);
}
uknFlashToast('success', "{$skillName} added to your {$type} skills.");
uknRedirectBack($listRoute);
