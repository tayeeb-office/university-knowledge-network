<?php
// Forgot Password / Reset Password: token and email helpers used by
// backend/auth/request-password-reset.php, backend/auth/reset-password.php and index.php.
// Reset tokens are separate from email-verification tokens (own columns, own lifetime).
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mail.php';

if (!defined('UKN_PASSWORD_RESET_TTL_MINUTES')) {
    define('UKN_PASSWORD_RESET_TTL_MINUTES', 60);
}
if (!defined('UKN_PASSWORD_RESET_COOLDOWN_SECONDS')) {
    // At most one reset email per account per this many seconds.
    define('UKN_PASSWORD_RESET_COOLDOWN_SECONDS', 60);
}
if (!defined('UKN_PASSWORD_RESET_IP_LIMIT')) {
    // Reset requests per client IP per window (abuse limit, independent of the account).
    define('UKN_PASSWORD_RESET_IP_LIMIT', 10);
    define('UKN_PASSWORD_RESET_IP_WINDOW_MINUTES', 15);
}

if (!function_exists('uknPasswordResetThrottleKey')) {
    /** SHA-256 key in login_attempts; the 'reset-ip|' prefix keeps it apart from login keys. */
    function uknPasswordResetThrottleKey(string $ip): string
    {
        return hash('sha256', 'reset-ip|' . $ip);
    }
}
if (!function_exists('uknPasswordResetThrottleAttempt')) {
    /**
     * Counts one reset request from $ip in the shared login_attempts table (same fixed-window
     * scheme as backend/helpers/login-throttle.php, under its own key) and returns whether it is
     * within the limit. Over the limit, the request still gets the generic response; no email.
     */
    function uknPasswordResetThrottleAttempt(PDO $pdo, string $ip): bool
    {
        $window = (int) UKN_PASSWORD_RESET_IP_WINDOW_MINUTES;
        $key = uknPasswordResetThrottleKey($ip);
        $pdo->prepare(
            "INSERT INTO login_attempts (throttle_key, attempts, window_started_at) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                 attempts = IF(window_started_at <= NOW() - INTERVAL {$window} MINUTE, 1, attempts + 1),
                 window_started_at = IF(window_started_at <= NOW() - INTERVAL {$window} MINUTE, NOW(), window_started_at)"
        )->execute([$key]);
        $read = $pdo->prepare('SELECT attempts FROM login_attempts WHERE throttle_key = ?');
        $read->execute([$key]);
        return (int) $read->fetchColumn() <= UKN_PASSWORD_RESET_IP_LIMIT;
    }
}

if (!function_exists('uknHashPasswordResetToken')) {
    /**
     * Returns the stored form of a raw reset token (64 lowercase hex characters), or null if the
     * raw value is malformed, so malformed input never reaches the database.
     */
    function uknHashPasswordResetToken($rawToken): ?string
    {
        if (!is_string($rawToken) || !preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return null;
        }
        return hash('sha256', $rawToken);
    }
}
if (!function_exists('uknIssuePasswordResetToken')) {
    /**
     * Creates a new random reset token for an active, verified user, stores only its hash
     * (replacing any earlier token) and returns the raw token for the email link, or null if
     * the account is not eligible.
     */
    function uknIssuePasswordResetToken(User $userModel, int $userId): ?string
    {
        $rawToken = bin2hex(random_bytes(32));
        if (!$userModel->setPasswordResetToken($userId, hash('sha256', $rawToken), UKN_PASSWORD_RESET_TTL_MINUTES)) {
            return null;
        }
        return $rawToken;
    }
}
if (!function_exists('uknPasswordResetLink')) {
    function uknPasswordResetLink(string $rawToken): string
    {
        // Production must set UKN_APP_URL (backend/config/app.php); see uknAppUrl().
        return uknAppUrl() . '/index.php?page=reset-password&token=' . rawurlencode($rawToken);
    }
}
if (!function_exists('uknSendPasswordResetEmail')) {
    function uknSendPasswordResetEmail(string $email, string $fullName, string $rawToken): bool
    {
        $body = "Hi {$fullName},\n\n"
            . "We received a request to reset the password for your University Knowledge Network account.\n"
            . "To choose a new password, open this link:\n\n"
            . uknPasswordResetLink($rawToken) . "\n\n"
            . 'This link expires in ' . UKN_PASSWORD_RESET_TTL_MINUTES . " minutes and can only be used once.\n"
            . "If you did not request a password reset, you can ignore this email; your password will not change.\n";
        return uknSendMail($email, 'Reset your University Knowledge Network password', $body);
    }
}
