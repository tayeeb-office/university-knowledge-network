<?php
// Steps 29–33: mentoring session lifecycle, ratings and points.
//
// Lifecycle (mentoring_sessions.status):
//   pending  --accept (mentor)-->   accepted  --complete (mentor, once started)--> completed
//   pending  --reject (mentor)-->   rejected
//   pending  --cancel (learner)-->  cancelled
//   accepted --cancel (learner or mentor)--> cancelled
// rejected / cancelled / completed are final. A completed session can be rated once by its learner.
//
// Points follow the project's "Point Rules" (admin/settings.php, design handoff):
//   completed session: learner +25 learning points, mentor +30 mentor points
//   late cancellation (accepted session, < 6 hours before its start): canceller −10
require_once __DIR__ . '/../config/database.php';

if (!defined('UKN_POINTS_SESSION_LEARNER')) {
    define('UKN_POINTS_SESSION_LEARNER', 25);
    define('UKN_POINTS_SESSION_MENTOR', 30);
    define('UKN_POINTS_LATE_CANCEL', -10);
    define('UKN_LATE_CANCEL_HOURS', 6);
    define('UKN_SESSION_DURATIONS', [30, 45, 60]); // options offered by the request form
}

if (!function_exists('uknLockSession')) {
    /**
     * Loads and row-locks a session the current user takes part in on the given side
     * ('learner' or 'mentor'). Returns null when it does not exist or belongs to others, so
     * callers answer both cases identically. Must run inside a transaction.
     */
    function uknLockSession(PDO $pdo, int $sessionId, int $userId, string $side): ?array
    {
        $column = $side === 'mentor' ? 'ms.mentor_id' : 'ms.learner_id';
        $stmt = $pdo->prepare(
            "SELECT ms.id, ms.reference_code, ms.learner_id, ms.mentor_id, ms.skill_id, ms.status,
                    TIMESTAMP(ms.scheduled_date, ms.scheduled_time) AS starts_at,
                    TIMESTAMP(ms.scheduled_date, ms.scheduled_time) <= NOW() AS has_started,
                    TIMESTAMP(ms.scheduled_date, ms.scheduled_time) < NOW() + INTERVAL " . (int) UKN_LATE_CANCEL_HOURS . " HOUR AS is_late,
                    sk.name AS skill_name, l.full_name AS learner_name, m.full_name AS mentor_name
             FROM mentoring_sessions ms
             JOIN skills sk ON sk.id = ms.skill_id
             JOIN users l ON l.id = ms.learner_id
             JOIN users m ON m.id = ms.mentor_id
             WHERE ms.id = ? AND {$column} = ?
             FOR UPDATE"
        );
        $stmt->execute([$sessionId, $userId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
if (!function_exists('uknAwardPoints')) {
    /**
     * Adds one ledger row and keeps users.<type>_points in step with it. Idempotent per
     * (user, session, type, category): a second call for the same event adds nothing.
     * Must run inside the caller's transaction. Returns true if points were added.
     */
    function uknAwardPoints(PDO $pdo, int $userId, int $sessionId, string $pointType, string $category, int $amount, string $reason): bool
    {
        $exists = $pdo->prepare(
            'SELECT 1 FROM point_transactions
             WHERE user_id = ? AND related_session_id = ? AND point_type = ? AND category = ? LIMIT 1'
        );
        $exists->execute([$userId, $sessionId, $pointType, $category]);
        if ($exists->fetchColumn() !== false) {
            return false;
        }
        $pdo->prepare(
            'INSERT INTO point_transactions (user_id, amount, point_type, category, reason, related_session_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $amount, $pointType, $category, mb_substr($reason, 0, 160, 'UTF-8'), $sessionId]);
        $column = $pointType === 'mentor' ? 'mentor_points' : 'learning_points';
        $pdo->prepare("UPDATE users SET {$column} = {$column} + ? WHERE id = ?")->execute([$amount, $userId]);
        return true;
    }
}
