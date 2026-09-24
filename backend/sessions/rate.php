<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/sessions.php';

// Step 32: the session's learner rates the mentor once, after the session is completed.
// Saves session_ratings and updates the mentor's rating fields in one transaction.
uknRequirePostMethod();
requireLearner();
uknRequireActionCsrf('sessions');

$sessionId = uknPostId('session_id');
if ($sessionId === null) {
    uknFailAction('Session not found.', 'sessions');
}
// overall is required; teaching / communication / helpfulness are optional; all 1–5.
$scores = [];
foreach (['rating_overall' => true, 'rating_teaching' => false, 'rating_communication' => false, 'rating_helpfulness' => false] as $field => $required) {
    $raw = $_POST[$field] ?? '';
    if (!is_string($raw) || ($raw === '' && $required) || ($raw !== '' && !preg_match('/^[1-5]$/', $raw))) {
        uknFailAction($required ? 'Choose an overall rating from 1 to 5 stars.' : 'Ratings must be between 1 and 5 stars.', 'sessions');
    }
    $scores[$field] = $raw === '' ? null : (int) $raw;
}
if (array_key_exists('review', $_POST) && uknPostString('review') === null) {
    uknFailAction('Invalid review.', 'sessions');
}
$review = sanitizeInput(uknPostString('review') ?? '');
if (!validateLength($review, 0, 2000)) {
    uknFailAction('Keep your review under 2000 characters.', 'sessions');
}

try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();
    $session = uknLockSession($pdo, $sessionId, (int) getCurrentUser()['id'], 'learner');
    if ($session === null || $session['status'] !== 'completed') {
        $pdo->rollBack();
        uknFailAction($session === null ? 'Session not found.' : 'Only completed sessions can be rated.', 'sessions');
    }
    $already = $pdo->prepare('SELECT 1 FROM session_ratings WHERE session_id = ?');
    $already->execute([$sessionId]);
    if ($already->fetchColumn() !== false) {
        $pdo->rollBack();
        uknFailAction('You have already rated this session.', 'sessions');
    }
    $mentorId = (int) $session['mentor_id'];
    $skillId = (int) $session['skill_id'];
    $pdo->prepare(
        'INSERT INTO session_ratings (session_id, reviewer_id, mentor_id, skill_id, overall, teaching, communication, helpfulness, review)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $sessionId, (int) $session['learner_id'], $mentorId, $skillId,
        $scores['rating_overall'], $scores['rating_teaching'], $scores['rating_communication'], $scores['rating_helpfulness'],
        $review === '' ? null : $review,
    ]);
    // Mentor's running average (keeps any earlier reviews already counted in total_reviews).
    $pdo->prepare(
        'UPDATE users
         SET avg_rating = ROUND((COALESCE(avg_rating, 0) * total_reviews + ?) / (total_reviews + 1), 1),
             total_reviews = total_reviews + 1
         WHERE id = ?'
    )->execute([$scores['rating_overall'], $mentorId]);
    // Per-skill average shown on the mentor's teaching skills, from this mentor's ratings for it.
    $pdo->prepare(
        "UPDATE user_skills us
         SET us.avg_rating = (SELECT ROUND(AVG(sr.overall), 1) FROM session_ratings sr WHERE sr.mentor_id = ? AND sr.skill_id = ?)
         WHERE us.user_id = ? AND us.skill_id = ? AND us.skill_type = 'teaching'"
    )->execute([$mentorId, $skillId, $mentorId, $skillId]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1062) {
        uknFailAction('You have already rated this session.', 'sessions');
    }
    error_log('[UKN sessions/rate] ' . $e->getMessage());
    uknFailAction('Your rating could not be saved. Please try again.', 'sessions');
}
uknFlashToast('success', "Thanks — your rating for {$session['mentor_name']} was saved.");
uknRedirectBack('sessions');
