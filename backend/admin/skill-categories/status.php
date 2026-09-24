<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 46: activate / deactivate. An inactive category is hidden from category filters and
// cannot receive new skills; skills already in it keep their category and status.
uknAdminBeginAction('skill-categories.php');
$categoryId = uknPostId('category_id');
$status = uknAdminEnum('status', ['active', 'inactive']);
if ($categoryId === null || $status === null) {
    uknAdminFail('Category not found.', 'skill-categories.php');
}
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT name FROM skill_categories WHERE id = ?');
    $stmt->execute([$categoryId]);
    $name = $stmt->fetchColumn();
    if ($name === false) {
        uknAdminFail('Category not found.', 'skill-categories.php');
    }
    $pdo->prepare('UPDATE skill_categories SET status = ? WHERE id = ?')->execute([$status, $categoryId]);
} catch (Throwable $e) {
    error_log('[UKN admin/skill-categories/status] ' . $e->getMessage());
    uknAdminFail('Could not update the category. Please try again.', 'skill-categories.php');
}
uknAdminDone('"' . $name . '" ' . ($status === 'active' ? 'activated.' : 'deactivated.'), 'skill-categories.php');
