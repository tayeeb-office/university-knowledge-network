<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/password-reset.php';

// Sets a new password from an emailed reset link. The token is consumed only here (POST), never
// by opening the link. Success does not log the user in; every existing session of the account
// ends because it is bound to the old password (see setAuthSession() / getCurrentUser()).
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit("Method Not Allowed. This endpoint accepts POST only.\n");
}
redirectIfLoggedIn();

$rawToken = $_POST['token'] ?? null;
$tokenHash = uknHashPasswordResetToken($rawToken);
// Back to the reset form for this (well-formed) token; a malformed token shows the invalid-link page.
$backToForm = static function (string $message) use ($tokenHash, $rawToken): void {
    $_SESSION['reset_error'] = $message;
    $target = uknRouteUrl('reset-password') . ($tokenHash !== null ? '&token=' . rawurlencode((string) $rawToken) : '');
    header('Location: ' . $target, true, 302);
    exit;
};

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    $backToForm('Your form expired. Please try again.');
}
if ($tokenHash === null) {
    $backToForm('This password reset link is invalid or has expired.');
}
if (uknInputHasInvalidUtf8($_POST)) {
    $backToForm('Your input contains invalid characters. Please check it and try again.');
}

$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$confirmPassword = is_string($_POST['confirmPassword'] ?? null) ? $_POST['confirmPassword'] : '';
$passwordCheck = validatePassword($password);
if (!$passwordCheck['valid']) {
    $backToForm($passwordCheck['errors'][0]);
}
if (!validatePasswordMatch($password, $confirmPassword)) {
    $backToForm('Passwords do not match.');
}

try {
    $result = (new User(getDatabaseConnection()))->resetPasswordByTokenHash($tokenHash, password_hash($password, PASSWORD_DEFAULT));
} catch (Throwable $e) {
    error_log('[UKN password-reset] ' . $e->getMessage());
    $backToForm('Your password could not be reset. Please try again.');
}
unset($password, $confirmPassword);

if ($result !== 'reset') {
    // expired / invalid / ineligible: the form page shows the generic invalid-link message.
    $backToForm('This password reset link is invalid or has expired.');
}
$_SESSION['flash_success'] = 'Your password has been reset. You can now log in with your new password.';
uknRedirectToRoute('login');
