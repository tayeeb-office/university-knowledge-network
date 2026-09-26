<?php
// Private contact details (user_private_contacts). A mobile number is never part of a user
// object or a general user query: it is read only by the functions below, for its owner
// (Settings) or for the mentor of one of that learner's open sessions (Session Details).
// Numbers are never logged or echoed in messages.
if (!defined('UKN_MOBILE_INPUT_MAX')) {
    define('UKN_MOBILE_INPUT_MAX', 20);
    // Session statuses in which the session's mentor may see the learner's number: while the
    // request is open and while the session is upcoming. Hidden once rejected, cancelled or
    // completed.
    define('UKN_CONTACT_VISIBLE_SESSION_STATUSES', ['pending', 'accepted']);
}

if (!function_exists('uknNormalizeMobile')) {
    /**
     * Bangladesh mobile number → +8801XXXXXXXXX, or null when invalid. Accepts 01XXXXXXXXX,
     * 8801XXXXXXXXX and +8801XXXXXXXXX (spaces and hyphens between digits are ignored);
     * operator code 3–9 after "01". Anything else (letters, other symbols, wrong length) → null.
     */
    function uknNormalizeMobile($value): ?string
    {
        if (!is_string($value) || strlen($value) > UKN_MOBILE_INPUT_MAX) {
            return null;
        }
        $compact = str_replace([' ', '-'], '', trim($value));
        if (!preg_match('/^(?:\+880|880|0)(1[3-9][0-9]{8})$/D', $compact, $m)) {
            return null;
        }
        return '+880' . $m[1];
    }
}
if (!function_exists('uknGetOwnMobile')) {
    /** The signed-in user's own number (Settings), or null when none is stored. */
    function uknGetOwnMobile(PDO $pdo, int $userId): ?string
    {
        $stmt = $pdo->prepare('SELECT mobile_number FROM user_private_contacts WHERE user_id = ?');
        $stmt->execute([$userId]);
        $mobile = $stmt->fetchColumn();
        return $mobile === false ? null : (string) $mobile;
    }
}
if (!function_exists('uknUserHasMobile')) {
    function uknUserHasMobile(PDO $pdo, int $userId): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM user_private_contacts WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() !== false;
    }
}
if (!function_exists('uknSaveOwnMobile')) {
    /** Insert or replace the number of $userId (always the session user; $mobile already normalised). */
    function uknSaveOwnMobile(PDO $pdo, int $userId, string $mobile): void
    {
        $pdo->prepare(
            'INSERT INTO user_private_contacts (user_id, mobile_number) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE mobile_number = VALUES(mobile_number)'
        )->execute([$userId, $mobile]);
    }
}
if (!function_exists('uknSessionLearnerMobileForMentor')) {
    /**
     * The learner's number for Session Details, or null. The only way a number reaches another
     * user: $viewerId must be the mentor of session $sessionId, the session must still be
     * pending/accepted, and the learner is taken from that session row — never from input.
     */
    function uknSessionLearnerMobileForMentor(PDO $pdo, int $sessionId, int $viewerId): ?string
    {
        if ($sessionId <= 0 || $viewerId <= 0) {
            return null;
        }
        $statuses = UKN_CONTACT_VISIBLE_SESSION_STATUSES;
        $stmt = $pdo->prepare(
            'SELECT pc.mobile_number
             FROM mentoring_sessions ms
             JOIN user_private_contacts pc ON pc.user_id = ms.learner_id
             WHERE ms.id = ? AND ms.mentor_id = ? AND ms.status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')'
        );
        $stmt->execute(array_merge([$sessionId, $viewerId], $statuses));
        $mobile = $stmt->fetchColumn();
        return $mobile === false ? null : (string) $mobile;
    }
}
