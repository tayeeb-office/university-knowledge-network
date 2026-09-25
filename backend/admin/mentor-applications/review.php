<?php
require_once __DIR__ . '/../../helpers/admin-moderation.php';
require_once __DIR__ . '/../../helpers/mentor-applications.php';

// Approve or reject a pending mentor application (admins only; POST + CSRF). One transaction
// locks the application row, so two admins cannot both decide it: the second one finds it
// already reviewed. Approval re-checks the applicant (still active, verified and learner-only)
// and changes only users.role 'learner' → 'dual' (learner + mentor); is_admin, the password,
// verification state and all learner data are never touched. Rejection changes only the
// application row. Nobody can review their own application.
$adminId = uknAdminBeginAction('mentor-applications.php');
$applicationId = uknPostId('application_id');
$decision = uknAdminEnum('decision', ['approved', 'rejected']);
if ($applicationId === null) {
    uknAdminFail('Application not found.', 'mentor-applications.php');
}
if ($decision === null) {
    uknAdminFail('Choose to approve or reject the application.', 'mentor-applications.php');
}
$note = null;
if (array_key_exists('admin_note', $_POST) && uknPostString('admin_note') !== '') {
    $note = uknAdminText('admin_note', UKN_MENTOR_APPLICATION_NOTE_MAX, false);
    if ($note === null) {
        uknAdminFail('The note must be a single line of up to ' . UKN_MENTOR_APPLICATION_NOTE_MAX . ' characters.', 'mentor-applications.php');
    }
}

$outcome = uknAdminRunTx(static function (PDO $pdo) use ($applicationId, $decision, $adminId, $note): array {
    $stmt = $pdo->prepare('SELECT id, user_id, status FROM mentor_applications WHERE id = ? FOR UPDATE');
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    if ($application === false) {
        return ['result' => 'missing', 'message' => 'Application not found.'];
    }
    if ($application['status'] !== 'pending') {
        return ['result' => 'refused', 'message' => 'This application has already been reviewed.'];
    }
    $applicantId = (int) $application['user_id'];
    if ($applicantId === $adminId) {
        return ['result' => 'refused', 'message' => 'You cannot review your own application.'];
    }
    $userStmt = $pdo->prepare('SELECT full_name, role, status, email_verified_at FROM users WHERE id = ? FOR UPDATE');
    $userStmt->execute([$applicantId]);
    $applicant = $userStmt->fetch();
    if ($applicant === false) {
        return ['result' => 'refused', 'message' => 'The applicant account no longer exists.'];
    }
    if ($decision === 'approved') {
        if ($applicant['status'] !== 'active') {
            return ['result' => 'refused', 'message' => 'The applicant account is not active, so it cannot be approved.'];
        }
        if ($applicant['email_verified_at'] === null) {
            return ['result' => 'refused', 'message' => 'The applicant has not verified their email, so it cannot be approved.'];
        }
        if ($applicant['role'] !== 'learner') {
            return ['result' => 'refused', 'message' => 'The applicant already has mentor access.'];
        }
        $grant = $pdo->prepare("UPDATE users SET role = 'dual' WHERE id = ? AND role = 'learner'");
        $grant->execute([$applicantId]);
        if ($grant->rowCount() !== 1) {
            return ['result' => 'refused', 'message' => 'The applicant already has mentor access.'];
        }
    }
    $pdo->prepare(
        "UPDATE mentor_applications SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_note = ?
         WHERE id = ? AND status = 'pending'"
    )->execute([$decision, $adminId, $note, $applicationId]);
    return [
        'result' => 'done',
        'message' => $decision === 'approved'
            ? 'Application approved. ' . $applicant['full_name'] . ' can now switch to Mentor mode.'
            : 'Application rejected. ' . $applicant['full_name'] . ' remains a learner.',
    ];
}, 'admin/mentor-applications/review');

if ($outcome === null) {
    uknAdminFail('Could not update the application. Please try again.', 'mentor-applications.php');
}
$outcome['result'] === 'done'
    ? uknAdminDone($outcome['message'], 'mentor-applications.php')
    : uknAdminFail($outcome['message'], 'mentor-applications.php');
