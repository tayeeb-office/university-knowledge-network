<?php
// Mentor applications: a learner-only account (users.role = 'learner') asks to become a mentor;
// an admin approves (users.role becomes 'dual' = learner + mentor) or rejects it. Every
// application is kept in mentor_applications as history; at most one can be pending per user.
require_once __DIR__ . '/../config/database.php';

if (!defined('UKN_MENTOR_APPLICATION_MESSAGE_MAX')) {
    define('UKN_MENTOR_APPLICATION_MESSAGE_MAX', 1000);
}
if (!defined('UKN_MENTOR_APPLICATION_NOTE_MAX')) {
    define('UKN_MENTOR_APPLICATION_NOTE_MAX', 255);
}

if (!function_exists('uknLatestMentorApplication')) {
    /** The user's most recent application (any status), or null if they never applied. */
    function uknLatestMentorApplication(PDO $pdo, int $userId): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id, status, application_message, requested_at, reviewed_at
             FROM mentor_applications WHERE user_id = ? ORDER BY requested_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
}
if (!function_exists('uknMentorApplicationBlocker')) {
    /**
     * Why this account cannot apply right now, or null if it can. Admin privilege does not
     * matter here: an admin whose role is still 'learner' may apply (another admin reviews it).
     */
    function uknMentorApplicationBlocker(array $user, ?array $latest): ?string
    {
        if (($user['role'] ?? '') === 'dual') {
            return 'already-mentor';
        }
        if (($user['role'] ?? '') !== 'learner' || ($user['status'] ?? '') !== 'active' || empty($user['email_verified_at'])) {
            return 'ineligible';
        }
        if ($latest !== null && $latest['status'] === 'pending') {
            return 'pending';
        }
        return null;
    }
}
