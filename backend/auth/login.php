<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/login-throttle.php';
if (!function_exists('uknLoginFail')) {
    function uknLoginFail(string $message, string $email = '', bool $unverified = false): void
    {
        $_SESSION['login_error'] = $message;
        $_SESSION['login_old_email'] = $email;
        if ($unverified) {
            $_SESSION['login_unverified'] = true;
        }
        uknRedirectToRoute('login');
    }
}

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
    uknLoginFail('Your form expired. Please try again.');
}
if (uknInputHasInvalidUtf8($_POST)) {
    uknLoginFail('Enter your university email and password.');
}

$email = sanitizeInput($_POST['email'] ?? '');
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
if (!validateEmail($email) || !validateLength($email, 1, UKN_MAX_EMAIL) || $password === '') {
    uknLoginFail('Enter your university email and password.', $email);
}

// Phase J: count this attempt before anything about the account is looked up (see
// backend/helpers/login-throttle.php). While throttled the password is not checked at all,
// and the message is the same whether or not the email has an account.
$clientIp = uknLoginClientIp();
try {
    $throttle = uknLoginThrottleAttempt(getDatabaseConnection(), $email, $clientIp);
} catch (Throwable $e) {
    error_log('[UKN login throttle] ' . $e->getMessage());
    uknLoginFail('Login could not be completed. Please try again.', $email);
}
if (!$throttle['allowed']) {
    $minutes = $throttle['retry_minutes'];
    uknLoginFail('Too many sign-in attempts. Please wait ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' and try again.', $email);
}

// One generic message for unknown email and wrong password, so the form does not reveal
// which emails have accounts.
$genericError = 'Incorrect email or password.';
try {
    $userModel = new User(getDatabaseConnection());
    $user = $userModel->findByEmail($email);

    if ($user === null) {
        // Spend the same hashing time as a real check to avoid a timing side channel.
        password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
        uknLoginFail($genericError, $email);
    }
    if (strlen($password) > UKN_MAX_PASSWORD_BYTES || !password_verify($password, $user['password_hash'])) {
        uknLoginFail($genericError, $email);
    }
    // Password proven: this is not a guessing attempt, so its throttle state is cleared.
    uknLoginThrottleSucceeded(getDatabaseConnection(), $email, $clientIp);
    // Only reveal account state once the password is proven correct.
    if ($user['email_verified_at'] === null) {
        uknLoginFail('Please verify your email address before logging in. Check your inbox for the verification link.', $email, true);
    }
    if ($user['status'] !== 'active') {
        uknLoginFail('This account is not active. Please contact the network administrators.', $email);
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $userModel->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
    }
    $userModel->updateLastActive((int) $user['id']);
} catch (Throwable $e) {
    error_log('[UKN login] ' . $e->getMessage());
    uknLoginFail('Login could not be completed. Please try again.', $email);
}

$redirectPath = uknTakeFlash('redirect_after_login');
unset($_SESSION['login_error'], $_SESSION['login_old_email'], $_SESSION['login_unverified']);
setAuthSession($user);

if (uknIsSafeRedirectPath($redirectPath)) {
    header('Location: ' . $redirectPath, true, 302);
    exit;
}
uknRedirectToRoute(getActiveRole() === 'mentor' ? 'mentor-dashboard' : 'learner-dashboard');
