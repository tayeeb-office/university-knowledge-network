<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/skills.php';
require_once __DIR__ . '/../helpers/sessions.php';

// Step 29: the current learner requests a session with a mentor (status 'pending').
uknRequirePostMethod();
requireLearner();
uknRequireActionCsrf('sessions');

$mentorId = uknPostId('mentor_id');
$skillName = uknPostString('skill');
$durationRaw = uknPostString('duration') ?? '';
$date = uknPostString('date') ?? '';
$time = uknPostString('time') ?? '';
$message = sanitizeInput(uknPostString('message') ?? '');
if (array_key_exists('message', $_POST) && uknPostString('message') === null) {
    uknFailAction('Invalid request data.', 'sessions');
}
$learnerId = (int) getCurrentUser()['id'];
if ($mentorId === null || $mentorId === $learnerId) {
    uknFailAction('Choose a valid mentor.', 'sessions');
}
$duration = ctype_digit($durationRaw) ? (int) $durationRaw : 0;
if (!in_array($duration, UKN_SESSION_DURATIONS, true)) {
    uknFailAction('Choose a session length of 30, 45 or 60 minutes.', 'sessions');
}
$dateOk = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $d) && checkdate((int) $d[2], (int) $d[3], (int) $d[1]) && (int) $d[1] <= 2100;
if (!$dateOk || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
    uknFailAction('Choose a valid date and time.', 'sessions');
}
if (!validateLength($message, 0, 1000)) {
    uknFailAction('Keep your message under 1000 characters.', 'sessions');
}

try {
    $pdo = getDatabaseConnection();
    $future = $pdo->prepare('SELECT TIMESTAMP(?, ?) > NOW()');
    $future->execute([$date, $time . ':00']);
    if ((int) $future->fetchColumn() !== 1) {
        uknFailAction('Choose a date and time in the future.', 'sessions');
    }
    // The mentor must be an active, verified, mentor-capable user who teaches this skill.
    $skillId = $skillName !== null ? uknFindActiveSkillIdByName($pdo, $skillName) : null;
    $mentorStmt = $pdo->prepare(
        "SELECT u.full_name FROM users u
         JOIN user_skills us ON us.user_id = u.id AND us.skill_type = 'teaching' AND us.skill_id = ?
         WHERE u.id = ? AND u.role = 'dual' AND u.status = 'active' AND u.email_verified_at IS NOT NULL"
    );
    $mentorStmt->execute([$skillId ?? 0, $mentorId]);
    $mentorName = $mentorStmt->fetchColumn();
    if ($skillId === null || $mentorName === false) {
        uknFailAction('This mentor is not available for that skill.', 'sessions');
    }

    $pdo->beginTransaction();
    // Serialise this learner's requests so the duplicate check below cannot race.
    $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$learnerId]);
    $dup = $pdo->prepare(
        "SELECT 1 FROM mentoring_sessions
         WHERE learner_id = ? AND mentor_id = ? AND skill_id = ? AND status IN ('pending', 'accepted') LIMIT 1"
    );
    $dup->execute([$learnerId, $mentorId, $skillId]);
    if ($dup->fetchColumn() !== false) {
        $pdo->rollBack();
        uknFailAction("You already have an open {$skillName} session or request with {$mentorName}.", 'sessions');
    }
    $insert = $pdo->prepare(
        'INSERT INTO mentoring_sessions
            (reference_code, learner_id, mentor_id, skill_id, scheduled_date, scheduled_time, duration_minutes, status, request_message)
         VALUES (?, ?, ?, ?, ?, ?, ?, \'pending\', ?)'
    );
    $insert->execute(['TMP-' . bin2hex(random_bytes(8)), $learnerId, $mentorId, $skillId, $date, $time . ':00', $duration, $message === '' ? null : $message]);
    $sessionId = (int) $pdo->lastInsertId();
    // Same reference format as existing sessions (UKN-S-1001 for id 1).
    $pdo->prepare('UPDATE mentoring_sessions SET reference_code = ? WHERE id = ?')->execute(['UKN-S-' . (1000 + $sessionId), $sessionId]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN sessions/request] ' . $e->getMessage());
    uknFailAction('Your request could not be sent. Please try again.', 'sessions');
}
uknFlashToast('success', "Session request sent to {$mentorName}.");
uknRedirectBack('sessions');
