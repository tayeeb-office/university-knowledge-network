<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 47: admin edits a skill. The category must exist and be active, except that a skill
// may stay in its current category after that category was deactivated. Member skills,
// goals, post tags and sessions reference the skill by id, so they follow a rename.
uknAdminBeginAction('skills.php');
$skillId = uknPostId('skill_id');
if ($skillId === null) {
    uknAdminFail('Skill not found.', 'skills.php');
}
[$data, $error] = uknAdminSkillInput();
if ($error !== null) {
    uknAdminFail($error, 'skills.php');
}
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT name, slug, category_id FROM skills WHERE id = ?');
    $stmt->execute([$skillId]);
    $current = $stmt->fetch();
    if ($current === false) {
        uknAdminFail('Skill not found.', 'skills.php');
    }
    $cat = $pdo->prepare("SELECT 1 FROM skill_categories WHERE id = ? AND (status = 'active' OR id = ?)");
    $cat->execute([$data['category_id'], (int) $current['category_id']]);
    if ($cat->fetchColumn() === false) {
        uknAdminFail('Choose an active category.', 'skills.php');
    }
    $dup = $pdo->prepare('SELECT 1 FROM skills WHERE name = ? AND id <> ?');
    $dup->execute([$data['name'], $skillId]);
    if ($dup->fetchColumn() !== false) {
        uknAdminFail('A skill with this name already exists.', 'skills.php');
    }
    $slug = $current['name'] === $data['name'] ? $current['slug'] : uknAdminSkillSlug($pdo, $data['name'], $skillId);
    $pdo->prepare('UPDATE skills SET name = ?, slug = ?, category_id = ?, description = ?, status = ? WHERE id = ?')
        ->execute([$data['name'], $slug, $data['category_id'], $data['description'], $data['status'], $skillId]);
} catch (Throwable $e) {
    if (uknIsDuplicateKeyError($e)) {
        uknAdminFail('A skill with this name already exists.', 'skills.php');
    }
    error_log('[UKN admin/skills/update] ' . $e->getMessage());
    uknAdminFail('Could not save the skill. Please try again.', 'skills.php');
}
uknAdminDone('Skill updated.', 'skills.php');
