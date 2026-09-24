<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/sessions.php';

// Step 30: the session's own mentor rejects a pending request.
uknRequirePostMethod();
requireMentor();
uknRequireActionCsrf('learner-requests');

$sessionId = uknPostId('session_id');
if ($sessionId === null) {
    uknFailAction('Session not found.', 'learner-requests');
}
try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();
    $session = uknLockSession($pdo, $sessionId, (int) getCurrentUser()['id'], 'mentor');
    if ($session === null || $session['status'] !== 'pending') {
        $pdo->rollBack();
        uknFailAction($session === null ? 'Session not found.' : 'Only pending requests can be rejected.', 'learner-requests');
    }
    $pdo->prepare(
        "UPDATE mentoring_sessions SET status = 'rejected', responded_at = NOW() WHERE id = ? AND status = 'pending'"
    )->execute([$sessionId]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN sessions/reject] ' . $e->getMessage());
    uknFailAction('The request could not be rejected. Please try again.', 'learner-requests');
}
uknFlashToast('success', 'Session request rejected.');
uknRedirectBack('learner-requests');
