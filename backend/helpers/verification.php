<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mail.php';

if (!function_exists('uknHashVerificationToken')) {
    /**
     * Returns the stored form of a raw emailed token, or null if the raw value is malformed
     * (so malformed input never reaches the database).
     */
    function uknHashVerificationToken($rawToken): ?string
    {
        if (!is_string($rawToken) || !preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return null;
        }
        return hash('sha256', $rawToken);
    }
}
if (!function_exists('uknIssueVerificationToken')) {
    /**
     * Creates a new random token for the user, stores only its hash, and returns the raw
     * token for the email link.
     */
    function uknIssueVerificationToken(User $userModel, int $userId): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $userModel->setVerificationToken($userId, hash('sha256', $rawToken), UKN_VERIFICATION_TTL_HOURS);
        return $rawToken;
    }
}
if (!function_exists('uknVerificationLink')) {
    function uknVerificationLink(string $rawToken): string
    {
        // Production must set UKN_APP_URL (backend/config/app.php); see uknAppUrl().
        return uknAppUrl() . '/index.php?page=verify-email&token=' . rawurlencode($rawToken);
    }
}
if (!function_exists('uknSendVerificationEmail')) {
    function uknSendVerificationEmail(string $email, string $fullName, string $rawToken): bool
    {
        $body = "Hi {$fullName},\n\n"
            . "Please confirm your email address to activate your University Knowledge Network account:\n\n"
            . uknVerificationLink($rawToken) . "\n\n"
            . 'This link expires in ' . UKN_VERIFICATION_TTL_HOURS . " hours and can only be used once.\n"
            . "If you did not create this account, you can ignore this email.\n";
        return uknSendMail($email, 'Verify your University Knowledge Network account', $body);
    }
}
