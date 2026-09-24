<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 46: admin creates a skill category.
uknAdminBeginAction('skill-categories.php');
[$data, $error] = uknAdminCategoryInput();
if ($error !== null) {
    uknAdminFail($error, 'skill-categories.php');
}
try {
    $pdo = getDatabaseConnection();
    $dup = $pdo->prepare('SELECT 1 FROM skill_categories WHERE name = ?');
    $dup->execute([$data['name']]);
    if ($dup->fetchColumn() !== false) {
        uknAdminFail('A category with this name already exists.', 'skill-categories.php');
    }
    $pdo->prepare('INSERT INTO skill_categories (name, description, status) VALUES (?, ?, ?)')
        ->execute([$data['name'], $data['description'], $data['status']]);
} catch (Throwable $e) {
    if (uknIsDuplicateKeyError($e)) {
        uknAdminFail('A category with this name already exists.', 'skill-categories.php');
    }
    error_log('[UKN admin/skill-categories/create] ' . $e->getMessage());
    uknAdminFail('Could not save the category. Please try again.', 'skill-categories.php');
}
uknAdminDone('Category added.', 'skill-categories.php');
