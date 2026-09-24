<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 46: permanent delete of an empty category only. skills.category_id is ON DELETE
// RESTRICT; a category that still has skills must be deactivated, never emptied by deleting
// its skills. The category row is locked for the count and the delete (a concurrent skill
// insert/move into it waits on that lock for its foreign-key check).
uknAdminBeginAction('skill-categories.php');
$categoryId = uknPostId('category_id');
if ($categoryId === null) {
    uknAdminFail('Category not found.', 'skill-categories.php');
}
try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT name FROM skill_categories WHERE id = ? FOR UPDATE');
    $stmt->execute([$categoryId]);
    $name = $stmt->fetchColumn();
    if ($name === false) {
        $pdo->rollBack();
        uknAdminFail('Category not found.', 'skill-categories.php');
    }
    $count = $pdo->prepare('SELECT COUNT(*) FROM skills WHERE category_id = ?');
    $count->execute([$categoryId]);
    $skillCount = (int) $count->fetchColumn();
    if ($skillCount > 0) {
        $pdo->rollBack();
        uknAdminFail('"' . $name . '" has ' . $skillCount . ' skill' . ($skillCount === 1 ? '' : 's')
            . ' and cannot be deleted. Deactivate it instead.', 'skill-categories.php');
    }
    $pdo->prepare('DELETE FROM skill_categories WHERE id = ?')->execute([$categoryId]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN admin/skill-categories/delete] ' . $e->getMessage());
    uknAdminFail('Could not delete the category. Please try again.', 'skill-categories.php');
}
uknAdminDone('"' . $name . '" deleted.', 'skill-categories.php');
