<?php
require_once __DIR__ . '/../../helpers/admin-taxonomy.php';

// Step 45: admin creates a department.
uknAdminBeginAction('departments.php');
[$data, $error] = uknAdminDepartmentInput();
if ($error !== null) {
    uknAdminFail($error, 'departments.php');
}
try {
    $pdo = getDatabaseConnection();
    $conflict = uknAdminDepartmentConflict($pdo, $data);
    if ($conflict !== null) {
        uknAdminFail($conflict, 'departments.php');
    }
    $pdo->prepare('INSERT INTO departments (name, code, status) VALUES (?, ?, ?)')
        ->execute([$data['name'], $data['code'], $data['status']]);
} catch (Throwable $e) {
    if (uknIsDuplicateKeyError($e)) {
        uknAdminFail('A department with this name or code already exists.', 'departments.php');
    }
    error_log('[UKN admin/departments/create] ' . $e->getMessage());
    uknAdminFail('Could not save the department. Please try again.', 'departments.php');
}
uknAdminDone('Department added.', 'departments.php');
