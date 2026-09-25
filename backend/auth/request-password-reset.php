<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/login-throttle.php';
require_once __DIR__ . '/../helpers/password-reset.php';

// "Forgot Password?": emails a reset link to an active, verified account. The browser always
// gets the same response, whether or not the email has an account (no account enumeration).
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit("Method Not Allowed. This endpoint accepts POST only.\n");
}
redirectIfLoggedIn();
if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    $_SESSION['forgot_error'] = 'Your form expired. Please try again.';
    uknRedirectToRoute('forgot-password');
}

$email = uknInputHasInvalidUtf8($_POST) ? '' : sanitizeInput($_POST['email'] ?? '');
if (!validateEmail($email) || !validateLength($email, 1, UKN_MAX_EMAIL)) {
    $_SESSION['forgot_error'] = 'Enter a valid email address.';
    $_SESSION['forgot_old_email'] = $email;
    uknRedirectToRoute('forgot-password');
}

try {
    $pdo = getDatabaseConnection();
    if (uknPasswordResetThrottleAttempt($pdo, uknLoginClientIp())) {
        $userModel = new User($pdo);
        $user = $userModel->findByEmail($email);
        // Only active, verified accounts get a link, at most once per cooldown. Unverified
        // accounts keep using "Resend verification email".
        if ($user !== null
            && $user['status'] === 'active'
            && $user['email_verified_at'] !== null
            && !$userModel->passwordResetRecentlyIssued((int) $user['id'], UKN_PASSWORD_RESET_TTL_MINUTES, UKN_PASSWORD_RESET_COOLDOWN_SECONDS)
        ) {
            $rawToken = uknIssuePasswordResetToken($userModel, (int) $user['id']);
            if ($rawToken !== null && !uknSendPasswordResetEmail($user['email'], $user['full_name'], $rawToken)) {
                // Not delivered: withdraw the token so no unusable-but-valid link is left behind.
                // uknSendMail() already logged a redacted reason.
                $userModel->clearPasswordResetToken((int) $user['id'], hash('sha256', $rawToken));
            }
            unset($rawToken);
        }
    }
} catch (Throwable $e) {
    error_log('[UKN password-reset request] ' . $e->getMessage());
}

$_SESSION['flash_success'] = 'If an eligible account exists for that email, a password reset link is on its way.';
uknRedirectToRoute('forgot-password');
