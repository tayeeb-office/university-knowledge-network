<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 47: activate / deactivate. An inactive skill leaves the directory, search, network and
// the add-skill/post-tag pickers (all of which already filter on skills.status = 'active');
// members' existing user_skills rows, goals, posts and sessions are kept as they are.
uknAdminBeginAction('skills.php');
$skillId = uknPostId('skill_id');
$status = uknAdminEnum('status', ['active', 'inactive']);
if ($skillId === null || $status === null) {
    uknAdminFail('Skill not found.', 'skills.php');
}
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT name FROM skills WHERE id = ?');
    $stmt->execute([$skillId]);
    $name = $stmt->fetchColumn();
    if ($name === false) {
        uknAdminFail('Skill not found.', 'skills.php');
    }
    $pdo->prepare('UPDATE skills SET status = ? WHERE id = ?')->execute([$status, $skillId]);
} catch (Throwable $e) {
    error_log('[UKN admin/skills/status] ' . $e->getMessage());
    uknAdminFail('Could not update the skill. Please try again.', 'skills.php');
}
uknAdminDone('"' . $name . '" ' . ($status === 'active' ? 'activated.' : 'deactivated.'), 'skills.php');
