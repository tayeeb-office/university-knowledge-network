<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 47: admin creates a skill in an existing, active category.
uknAdminBeginAction('skills.php');
[$data, $error] = uknAdminSkillInput();
if ($error !== null) {
    uknAdminFail($error, 'skills.php');
}
try {
    $pdo = getDatabaseConnection();
    $cat = $pdo->prepare("SELECT 1 FROM skill_categories WHERE id = ? AND status = 'active'");
    $cat->execute([$data['category_id']]);
    if ($cat->fetchColumn() === false) {
        uknAdminFail('Choose an active category.', 'skills.php');
    }
    $dup = $pdo->prepare('SELECT 1 FROM skills WHERE name = ?');
    $dup->execute([$data['name']]);
    if ($dup->fetchColumn() !== false) {
        uknAdminFail('A skill with this name already exists.', 'skills.php');
    }
    $pdo->prepare('INSERT INTO skills (name, slug, category_id, description, status) VALUES (?, ?, ?, ?, ?)')
        ->execute([$data['name'], uknAdminSkillSlug($pdo, $data['name']), $data['category_id'], $data['description'], $data['status']]);
} catch (Throwable $e) {
    if (uknIsDuplicateKeyError($e)) {
        uknAdminFail('A skill with this name already exists.', 'skills.php');
    }
    error_log('[UKN admin/skills/create] ' . $e->getMessage());
    uknAdminFail('Could not save the skill. Please try again.', 'skills.php');
}
uknAdminDone('Skill added.', 'skills.php');
