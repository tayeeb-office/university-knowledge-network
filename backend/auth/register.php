<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/verification.php';
require_once __DIR__ . '/../helpers/private-contacts.php';
if (!function_exists('uknRegisterFail')) {
    function uknRegisterFail(array $errors, array $old = []): void
    {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_old']    = $old;
        uknRedirectToRoute('register');
    }
}
if (!function_exists('uknRegisterAbort')) {
    function uknRegisterAbort(int $status, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/plain; charset=utf-8');
        }
        exit($message . "\n");
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        header('Allow: POST');
    }
    uknRegisterAbort(405, 'Method Not Allowed. This endpoint accepts POST only.');
}
redirectIfLoggedIn();
if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    uknRegisterFail(['form' => 'Your form expired. Please try again.']);
}
if (uknInputHasInvalidUtf8($_POST)) {
    uknRegisterFail(['form' => 'Your input contains invalid characters. Please check it and try again.']);
}
$input = [
    'full_name'     => sanitizeInput($_POST['fullName'] ?? ''),
    'email'         => sanitizeInput($_POST['email'] ?? ''),
    'university_id' => sanitizeInput($_POST['universityId'] ?? ''),
    'department'    => sanitizeInput($_POST['department'] ?? ''),
    'mobile'        => uknPostString('mobileNumber'),

    'password'         => is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
    'confirm_password' => is_string($_POST['confirmPassword'] ?? null) ? $_POST['confirmPassword'] : '',
    'terms'            => ($_POST['terms'] ?? null) === 'on',
];
$old = [
    'fullName'     => $input['full_name'],
    'email'        => $input['email'],
    'universityId' => $input['university_id'],
    'department'   => $input['department'],
    'mobileNumber' => sanitizeInput($input['mobile'] ?? ''),
];

$validation = validateRegistrationData($input);
// Private mobile number (user_private_contacts), stored normalised as +8801XXXXXXXXX.
$mobile = uknNormalizeMobile($input['mobile']);
if ($mobile === null) {
    $validation['errors']['mobile'] = trim((string) $input['mobile']) === ''
        ? 'Please enter your mobile number.'
        : 'Enter a valid Bangladesh mobile number, e.g. 01XXXXXXXXX or +8801XXXXXXXXX.';
    $validation['valid'] = false;
}
if (!$validation['valid']) {
    uknRegisterFail($validation['errors'], $old);
}
try {
    $pdo = getDatabaseConnection();
    $userModel = new User($pdo);
    $errors = [];

    if ($userModel->emailExists($input['email'])) {
        $errors['email'] = 'This email is already registered.';
    }
    if ($userModel->universityIdExists($input['university_id'])) {
        $errors['university_id'] = 'This university ID is already registered.';
    }

    $departmentId = uknResolveDepartmentId($pdo, $input['department']);
    if ($departmentId === null) {
        $errors['department'] = 'Invalid department selected.';
    }
    if ($errors !== []) {
        uknRegisterFail($errors, $old);
    }
    $initials = uknDeriveInitials($input['full_name']);
    $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
    if (!is_string($passwordHash) || $passwordHash === '') {

        throw new RuntimeException('Password hashing failed.');
    }

    $pdo->beginTransaction();
    try {
        $userId = $userModel->create([
            'full_name'     => $input['full_name'],
            'initials'      => $initials,
            'email'         => $input['email'],
            'university_id' => $input['university_id'],
            'password_hash' => $passwordHash,
            'department_id' => $departmentId,
        ]);
        $userModel->createUserSettings($userId);
        // Same transaction: an account is never created without its mobile number.
        uknSaveOwnMobile($pdo, $userId, $mobile);
        $verificationToken = uknIssueVerificationToken($userModel, $userId);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
} catch (RuntimeException $e) {

    $errors = [];
    try {
        if (isset($userModel)) {
            if ($userModel->emailExists($input['email'])) {
                $errors['email'] = 'This email is already registered.';
            }
            if ($userModel->universityIdExists($input['university_id'])) {
                $errors['university_id'] = 'This university ID is already registered.';
            }
        }
    } catch (Throwable $ignored) {
    }
    if ($errors === []) {
        error_log('[UKN register] ' . $e->getMessage());
        $errors['form'] = 'Registration could not be completed. Please try again.';
    }

    uknRegisterFail($errors, $old);
} catch (Throwable $e) {

    error_log('[UKN register] ' . $e->getMessage());

    uknRegisterFail(
        ['form' => 'Registration could not be completed. Please try again.'],
        $old
    );
}


unset($_SESSION['register_errors'], $_SESSION['register_old']);

// The account exists either way; if the email could not be sent the user can request a new
// link from the login page.
if (uknSendVerificationEmail($input['email'], $input['full_name'], $verificationToken)) {
    $_SESSION['flash_success'] = 'Your account has been created. We sent a verification link to your email — verify your address, then log in.';
} else {
    $_SESSION['flash_success'] = 'Your account has been created, but we could not send the verification email. Use "Resend verification email" below to try again.';
}
uknRedirectToRoute('login');