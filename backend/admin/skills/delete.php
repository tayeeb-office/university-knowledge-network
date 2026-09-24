<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 47: permanent delete only for a skill nothing refers to. Most references cascade or
// set NULL (member skills, post tags, relations, goals, ratings) and sessions RESTRICT, so a
// referenced skill would silently take member data with it; it must be deactivated
// instead. The skill row is locked for the reference check and the delete; a concurrent
// insert that references it waits on that lock for its foreign-key check.
uknAdminBeginAction('skills.php');
$skillId = uknPostId('skill_id');
if ($skillId === null) {
    uknAdminFail('Skill not found.', 'skills.php');
}
try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT name FROM skills WHERE id = ? FOR UPDATE');
    $stmt->execute([$skillId]);
    $name = $stmt->fetchColumn();
    if ($name === false) {
        $pdo->rollBack();
        uknAdminFail('Skill not found.', 'skills.php');
    }
    $labels = ['member_skills' => 'member skill', 'post_tags' => 'post tag', 'goals' => 'learning goal',
               'sessions' => 'session', 'ratings' => 'rating', 'relations' => 'skill relation'];
    $inUse = [];
    foreach (uknAdminSkillReferences($pdo, $skillId) as $key => $count) {
        if ($count > 0) {
            $inUse[] = $count . ' ' . $labels[$key] . ($count === 1 ? '' : 's');
        }
    }
    if ($inUse !== []) {
        $pdo->rollBack();
        uknAdminFail('"' . $name . '" is still used (' . implode(', ', $inUse) . ') and cannot be deleted. Deactivate it instead.', 'skills.php');
    }
    $pdo->prepare('DELETE FROM skills WHERE id = ?')->execute([$skillId]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN admin/skills/delete] ' . $e->getMessage());
    uknAdminFail('Could not delete the skill. Please try again.', 'skills.php');
}
uknAdminDone('"' . $name . '" deleted.', 'skills.php');
