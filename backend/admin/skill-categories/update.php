<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 46: admin edits a category's name, description and status. Its skills are untouched.
uknAdminBeginAction('skill-categories.php');
$categoryId = uknPostId('category_id');
if ($categoryId === null) {
    uknAdminFail('Category not found.', 'skill-categories.php');
}
[$data, $error] = uknAdminCategoryInput();
if ($error !== null) {
    uknAdminFail($error, 'skill-categories.php');
}
try {
    $pdo = getDatabaseConnection();
    $exists = $pdo->prepare('SELECT 1 FROM skill_categories WHERE id = ?');
    $exists->execute([$categoryId]);
    if ($exists->fetchColumn() === false) {
        uknAdminFail('Category not found.', 'skill-categories.php');
    }
    $dup = $pdo->prepare('SELECT 1 FROM skill_categories WHERE name = ? AND id <> ?');
    $dup->execute([$data['name'], $categoryId]);
    if ($dup->fetchColumn() !== false) {
        uknAdminFail('A category with this name already exists.', 'skill-categories.php');
    }
    $pdo->prepare('UPDATE skill_categories SET name = ?, description = ?, status = ? WHERE id = ?')
        ->execute([$data['name'], $data['description'], $data['status'], $categoryId]);
} catch (Throwable $e) {
    if (uknIsDuplicateKeyError($e)) {
        uknAdminFail('A category with this name already exists.', 'skill-categories.php');
    }
    error_log('[UKN admin/skill-categories/update] ' . $e->getMessage());
    uknAdminFail('Could not save the category. Please try again.', 'skill-categories.php');
}
uknAdminDone('Category updated.', 'skill-categories.php');
