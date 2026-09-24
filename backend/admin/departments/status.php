<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 45: activate / deactivate. An inactive department is hidden from registration and
// profile editing; members keep it on their profile (nothing about users changes).
uknAdminBeginAction('departments.php');
$departmentId = uknPostId('department_id');
$status = uknAdminEnum('status', ['active', 'inactive']);
if ($departmentId === null || $status === null) {
    uknAdminFail('Department not found.', 'departments.php');
}
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT name FROM departments WHERE id = ?');
    $stmt->execute([$departmentId]);
    $name = $stmt->fetchColumn();
    if ($name === false) {
        uknAdminFail('Department not found.', 'departments.php');
    }
    $pdo->prepare('UPDATE departments SET status = ? WHERE id = ?')->execute([$status, $departmentId]);
} catch (Throwable $e) {
    error_log('[UKN admin/departments/status] ' . $e->getMessage());
    uknAdminFail('Could not update the department. Please try again.', 'departments.php');
}
uknAdminDone('"' . $name . '" ' . ($status === 'active' ? 'activated.' : 'deactivated.'), 'departments.php');
