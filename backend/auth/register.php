<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../models/User.php';
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
if (!function_exists('uknDeriveInitials')) {

    function uknDeriveInitials(string $fullName): string
    {
        $cleaned = preg_replace('/[^\p{L}\s]+/u', ' ', $fullName);
        if ($cleaned === null) {
            $cleaned = $fullName;
        }
        $parts = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts) || $parts === []) {
            return 'U';
        }
        if (count($parts) === 1) {
            $initials = mb_substr($parts[0], 0, 2, 'UTF-8');
        } else {
            $initials = mb_substr($parts[0], 0, 1, 'UTF-8')
                . mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');
        }
        return mb_substr(mb_strtoupper($initials, 'UTF-8'), 0, 4, 'UTF-8');
    }
}
if (!function_exists('uknResolveDepartmentId')) {
    function uknResolveDepartmentId(PDO $pdo, string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $stmt = $pdo->prepare(
            'SELECT id FROM departments WHERE name = ? AND status = ? LIMIT 1'
        );
        $stmt->execute([$name, 'active']);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        header('Allow: POST');
    }
    uknRegisterAbort(405, 'Method Not Allowed. This endpoint accepts POST only.');
}
if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    uknRegisterAbort(403, 'Invalid or expired form token. Please reload the page and try again.');
}
$input = [
    'full_name'     => sanitizeInput($_POST['fullName'] ?? ''),
    'email'         => sanitizeInput($_POST['email'] ?? ''),
    'university_id' => sanitizeInput($_POST['universityId'] ?? ''),
    'department'    => sanitizeInput($_POST['department'] ?? ''),

    'password'         => is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
    'confirm_password' => is_string($_POST['confirmPassword'] ?? null) ? $_POST['confirmPassword'] : '',
];
$old = [
    'fullName'     => $input['full_name'],
    'email'        => $input['email'],
    'universityId' => $input['university_id'],
    'department'   => $input['department'],
];

$validation = validateRegistrationData($input);
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

$_SESSION['flash_success'] = 'Your account has been created. Please log in.';
uknRedirectToRoute('login');