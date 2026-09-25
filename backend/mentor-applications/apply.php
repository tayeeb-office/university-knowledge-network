<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/mentor-applications.php';

// A learner-only account applies to become a mentor. The applicant is always the logged-in
// user (never a posted id). Submitting changes no role and no admin flag; an admin decides later.
// One transaction locks the applicant's users row, so concurrent submissions from the same
// account run one after the other; uq_mentor_applications_pending is the database backstop.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('mentor-application');

if (array_key_exists('message', $_POST) && uknPostString('message') === null) {
    uknFailAction('Invalid application.', 'mentor-application');
}
$message = sanitizeInput(uknPostString('message') ?? '');
if (!validateLength($message, 1, UKN_MENTOR_APPLICATION_MESSAGE_MAX)) {
    uknFailAction('Tell us why you would like to become a mentor (up to ' . UKN_MENTOR_APPLICATION_MESSAGE_MAX . ' characters).', 'mentor-application');
}
$userId = (int) getCurrentUser()['id'];

$blocked = null;
try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, role, status, email_verified_at FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $blocked = $user === false ? 'ineligible' : uknMentorApplicationBlocker($user, uknLatestMentorApplication($pdo, $userId));
    if ($blocked === null) {
        $pdo->prepare("INSERT INTO mentor_applications (user_id, status, application_message) VALUES (?, 'pending', ?)")
            ->execute([$userId, $message]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof PDOException && ($e->errorInfo[1] ?? null) === 1062) {
        $blocked = 'pending';
    } else {
        error_log('[UKN mentor-applications/apply] ' . $e->getMessage());
        uknFailAction('Your application could not be submitted. Please try again.', 'mentor-application');
    }
}

$messages = [
    'pending'        => 'You already have a mentor application waiting for review.',
    'already-mentor' => 'Your account already has mentor access.',
    'ineligible'     => 'Your account cannot apply to become a mentor.',
];
if ($blocked !== null) {
    uknFailAction($messages[$blocked] ?? $messages['ineligible'], 'mentor-application');
}
uknFlashToast('success', 'Application submitted. An administrator will review it.');
uknRedirectToRoute('mentor-application');
