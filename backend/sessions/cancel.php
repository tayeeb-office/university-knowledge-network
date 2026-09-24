<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/sessions.php';

// Step 31: cancel a session from the side the user is currently acting as.
//   learner: own pending request or accepted session;  mentor: own accepted session.
// Cancelling an accepted session less than 6 hours before it starts costs the canceller
// 10 points (Step 33), in the same transaction.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('sessions');

$side = getCurrentActiveRole() === 'mentor' ? 'mentor' : 'learner';
$sessionId = uknPostId('session_id');
$reason = sanitizeInput(uknPostString('reason') ?? '');
if ($sessionId === null || (array_key_exists('reason', $_POST) && uknPostString('reason') === null)) {
    uknFailAction('Session not found.', 'sessions');
}
if (!validateLength($reason, 0, 255)) {
    uknFailAction('Keep the cancellation reason under 255 characters.', 'sessions');
}
$allowedFrom = $side === 'learner' ? ['pending', 'accepted'] : ['accepted'];
$penalised = false;
try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    $pdo->beginTransaction();
    $session = uknLockSession($pdo, $sessionId, $userId, $side);
    if ($session === null || !in_array($session['status'], $allowedFrom, true)) {
        $pdo->rollBack();
        uknFailAction($session === null ? 'Session not found.' : 'This session can no longer be cancelled.', 'sessions');
    }
    $pdo->prepare(
        "UPDATE mentoring_sessions SET status = 'cancelled', cancel_reason = ? WHERE id = ? AND status = ?"
    )->execute([$reason !== '' ? $reason : "Cancelled by the {$side}", $sessionId, $session['status']]);

    if ($session['status'] === 'accepted' && (int) $session['is_late'] === 1) {
        $penalised = uknAwardPoints(
            $pdo, $userId, $sessionId, $side === 'mentor' ? 'mentor' : 'learning', 'penalty', UKN_POINTS_LATE_CANCEL,
            "Late cancellation of {$session['skill_name']} session {$session['reference_code']}"
        );
    }
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN sessions/cancel] ' . $e->getMessage());
    uknFailAction('The session could not be cancelled. Please try again.', 'sessions');
}
uknFlashToast('success', $penalised
    ? 'Session cancelled. Late cancellation: ' . UKN_POINTS_LATE_CANCEL . ' points.'
    : 'Session cancelled.');
uknRedirectBack('sessions');
