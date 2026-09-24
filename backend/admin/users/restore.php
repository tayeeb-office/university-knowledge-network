<?php
require_once __DIR__ . '/../../helpers/admin.php';

// Step 48: restore a suspended account to active and clear the suspend reason. Only
// suspended accounts are restored here (inactive accounts are a separate state this admin
// screen does not manage). Nothing else about the user changes.
uknAdminBeginAction('users.php');
$userId = uknPostId('user_id');
if ($userId === null) {
    uknAdminFail('User not found.', 'users.php');
}
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT full_name, status FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $target = $stmt->fetch();
    if ($target === false) {
        uknAdminFail('User not found.', 'users.php');
    }
    if ($target['status'] !== 'suspended') {
        uknAdminFail($target['full_name'] . ' is not suspended.', 'users.php');
    }
    $pdo->prepare("UPDATE users SET status = 'active', suspend_reason = NULL WHERE id = ? AND status = 'suspended'")
        ->execute([$userId]);
} catch (Throwable $e) {
    error_log('[UKN admin/users/restore] ' . $e->getMessage());
    uknAdminFail('Could not restore the user. Please try again.', 'users.php');
}
uknAdminDone($target['full_name'] . ' restored.', 'users.php');
