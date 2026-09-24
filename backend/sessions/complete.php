<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/sessions.php';

// Steps 31 + 33: the session's mentor marks an accepted session (that has started) as completed.
// One transaction: status change, both parties' points, and the session counters the profile,
// dashboard and skill pages display. The conditional UPDATE plus the ledger check make a repeated
// submission a no-op, so points can never be awarded twice.
uknRequirePostMethod();
requireMentor();
uknRequireActionCsrf('sessions');

$sessionId = uknPostId('session_id');
if ($sessionId === null) {
    uknFailAction('Session not found.', 'sessions');
}
try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();
    $session = uknLockSession($pdo, $sessionId, (int) getCurrentUser()['id'], 'mentor');
    if ($session === null || $session['status'] !== 'accepted') {
        $pdo->rollBack();
        uknFailAction($session === null ? 'Session not found.' : 'Only accepted sessions can be completed.', 'sessions');
    }
    if ((int) $session['has_started'] !== 1) {
        $pdo->rollBack();
        uknFailAction('A session can be completed once its scheduled time has started.', 'sessions');
    }
    $learnerId = (int) $session['learner_id'];
    $mentorId = (int) $session['mentor_id'];
    $skillId = (int) $session['skill_id'];

    // First completed session between this pair? (counts toward the mentor's learners_helped)
    $prior = $pdo->prepare(
        "SELECT 1 FROM mentoring_sessions WHERE learner_id = ? AND mentor_id = ? AND status = 'completed' AND id <> ? LIMIT 1"
    );
    $prior->execute([$learnerId, $mentorId, $sessionId]);
    $newLearner = $prior->fetchColumn() === false;

    $update = $pdo->prepare(
        "UPDATE mentoring_sessions SET status = 'completed', completed_at = NOW() WHERE id = ? AND status = 'accepted'"
    );
    $update->execute([$sessionId]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Session state changed during completion.');
    }
    uknAwardPoints($pdo, $learnerId, $sessionId, 'learning', 'session', UKN_POINTS_SESSION_LEARNER,
        "Completed {$session['skill_name']} session with {$session['mentor_name']}");
    uknAwardPoints($pdo, $mentorId, $sessionId, 'mentor', 'session', UKN_POINTS_SESSION_MENTOR,
        "Completed {$session['skill_name']} mentoring session with {$session['learner_name']}");

    $pdo->prepare('UPDATE users SET sessions_as_learner = sessions_as_learner + 1 WHERE id = ?')->execute([$learnerId]);
    $pdo->prepare('UPDATE users SET sessions_as_mentor = sessions_as_mentor + 1, learners_helped = learners_helped + ? WHERE id = ?')
        ->execute([$newLearner ? 1 : 0, $mentorId]);
    $skillRow = $pdo->prepare('UPDATE user_skills SET sessions_count = sessions_count + 1 WHERE user_id = ? AND skill_id = ? AND skill_type = ?');
    $skillRow->execute([$mentorId, $skillId, 'teaching']);
    $skillRow->execute([$learnerId, $skillId, 'learning']);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN sessions/complete] ' . $e->getMessage());
    uknFailAction('The session could not be completed. Please try again.', 'sessions');
}
uknFlashToast('success', 'Session completed. +' . UKN_POINTS_SESSION_MENTOR . ' mentor points.');
uknRedirectBack('sessions');
