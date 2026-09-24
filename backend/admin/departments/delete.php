<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 45: permanent delete, only for a department nobody belongs to. users.department_id is
// ON DELETE SET NULL, so deleting a used department would silently strip members of their
// department — those must be deactivated instead. The department row is locked (FOR UPDATE)
// for the count and the delete; a concurrent user insert/update pointing at it needs a
// shared lock on that row for its foreign-key check, so it waits and then fails cleanly.
uknAdminBeginAction('departments.php');
$departmentId = uknPostId('department_id');
if ($departmentId === null) {
    uknAdminFail('Department not found.', 'departments.php');
}
try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT name FROM departments WHERE id = ? FOR UPDATE');
    $stmt->execute([$departmentId]);
    $name = $stmt->fetchColumn();
    if ($name === false) {
        $pdo->rollBack();
        uknAdminFail('Department not found.', 'departments.php');
    }
    $members = $pdo->prepare('SELECT COUNT(*) FROM users WHERE department_id = ?');
    $members->execute([$departmentId]);
    $memberCount = (int) $members->fetchColumn();
    if ($memberCount > 0) {
        $pdo->rollBack();
        uknAdminFail('"' . $name . '" has ' . $memberCount . ' member' . ($memberCount === 1 ? '' : 's')
            . ' and cannot be deleted. Deactivate it instead.', 'departments.php');
    }
    $pdo->prepare('DELETE FROM departments WHERE id = ?')->execute([$departmentId]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN admin/departments/delete] ' . $e->getMessage());
    uknAdminFail('Could not delete the department. Please try again.', 'departments.php');
}
uknAdminDone('"' . $name . '" deleted.', 'departments.php');
