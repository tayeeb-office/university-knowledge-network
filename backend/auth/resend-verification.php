<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/verification.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit("Method Not Allowed. This endpoint accepts POST only.\n");
}
if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    $_SESSION['login_error'] = 'Your form expired. Please try again.';
    uknRedirectToRoute('login');
}

$email = sanitizeInput($_POST['email'] ?? '');
if (validateEmail($email)) {
    try {
        $userModel = new User(getDatabaseConnection());
        $user = $userModel->findByEmail($email);
        // Only unverified, active accounts get a new link, at most once a minute.
        if ($user !== null
            && $user['email_verified_at'] === null
            && $user['status'] === 'active'
            && !$userModel->verificationRecentlyIssued((int) $user['id'], UKN_VERIFICATION_TTL_HOURS, 60)
        ) {
            $rawToken = uknIssueVerificationToken($userModel, (int) $user['id']);
            uknSendVerificationEmail($user['email'], $user['full_name'], $rawToken);
        }
    } catch (Throwable $e) {
        error_log('[UKN resend-verification] ' . $e->getMessage());
    }
}

// Same response whether or not the email has an account (no account enumeration).
$_SESSION['flash_success'] = 'If that email belongs to an unverified account, a new verification link is on its way.';
uknRedirectToRoute('login');
