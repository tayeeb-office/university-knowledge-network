<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 45: admin edits a department's name, code and status. Users keep their department_id.
uknAdminBeginAction('departments.php');
$departmentId = uknPostId('department_id');
if ($departmentId === null) {
    uknAdminFail('Department not found.', 'departments.php');
}
[$data, $error] = uknAdminDepartmentInput();
if ($error !== null) {
    uknAdminFail($error, 'departments.php');
}
try {
    $pdo = getDatabaseConnection();
    $exists = $pdo->prepare('SELECT 1 FROM departments WHERE id = ?');
    $exists->execute([$departmentId]);
    if ($exists->fetchColumn() === false) {
        uknAdminFail('Department not found.', 'departments.php');
    }
    $conflict = uknAdminDepartmentConflict($pdo, $data, $departmentId);
    if ($conflict !== null) {
        uknAdminFail($conflict, 'departments.php');
    }
    $pdo->prepare('UPDATE departments SET name = ?, code = ?, status = ? WHERE id = ?')
        ->execute([$data['name'], $data['code'], $data['status'], $departmentId]);
} catch (Throwable $e) {
    if (uknIsDuplicateKeyError($e)) {
        uknAdminFail('A department with this name or code already exists.', 'departments.php');
    }
    error_log('[UKN admin/departments/update] ' . $e->getMessage());
    uknAdminFail('Could not save the department. Please try again.', 'departments.php');
}
uknAdminDone('Department updated.', 'departments.php');
