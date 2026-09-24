<?php
require_once __DIR__ . '/../../helpers/admin-moderation.php';

// Step 48: suspend an account (status 'suspended' + required reason). getCurrentUser()
// already treats any non-active user as logged out, so the member loses access on their
// next request; their posts, comments, sessions, ratings and points are kept. Self-suspension
// and suspending the last usable admin are refused (see uknAdminSuspendUserTx()).
$adminId = uknAdminBeginAction('users.php');
$userId = uknPostId('user_id');
if ($userId === null) {
    uknAdminFail('User not found.', 'users.php');
}
$reason = uknAdminText('reason', 255);
if ($reason === null) {
    uknAdminFail('Give a reason for the suspension (up to 255 characters).', 'users.php');
}
$outcome = uknAdminRunTx(
    static fn (PDO $pdo): array => uknAdminSuspendUserTx($pdo, $adminId, $userId, $reason),
    'admin/users/suspend'
);
if ($outcome === null) {
    uknAdminFail('Could not suspend the user. Please try again.', 'users.php');
}
$outcome['result'] === 'done'
    ? uknAdminDone($outcome['message'], 'users.php')
    : uknAdminFail($outcome['message'], 'users.php');
