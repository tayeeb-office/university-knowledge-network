<?php
/**
 * Registration endpoint — creates a user account.
 *
 * The first real authentication endpoint in this project. Accepts a POST
 * from pages/auth/register.php (once that form gains method/action) and
 * nothing else.
 *
 * FLOW
 *   POST only  ->  CSRF  ->  collect + sanitise  ->  validate
 *              ->  duplicate checks  ->  resolve department
 *              ->  derive initials   ->  hash password
 *              ->  transaction: create user + settings
 *              ->  redirect
 *
 * RESPONSE MODEL — POST/Redirect/GET
 * ----------------------------------
 * This endpoint never renders anything. It writes the outcome into the
 * session and redirects, so a refresh after submitting can never re-post
 * the form:
 *
 *   failure -> $_SESSION['register_errors'] (field => message)
 *              $_SESSION['register_old']    (previously typed values)
 *              redirect back to index.php?page=register
 *
 *   success -> $_SESSION['flash_success']
 *              redirect to index.php?page=login
 *
 * pages/auth/register.php cannot display any of that yet — it has not
 * been modified, as instructed. The keys are written now so that wiring
 * the form up later is a display change only, with no change here.
 *
 * PASSWORDS ARE NEVER FLASHED BACK. 'password' and 'confirmPassword' are
 * excluded from register_old, so a password can never end up written to
 * the session file on disk, or re-rendered into an HTML value attribute.
 *
 * THIS ENDPOINT DOES NOT LOG ANYONE IN. setAuthSession() is deliberately
 * never called — see the note at the bottom of this file.
 *
 * NOTE: no closing `?>` tag. Whitespace after one would be sent to the
 * browser, set headers_sent(), and break every redirect below.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../models/User.php';

/*
 * auth.php is included for uknRouteUrl() / uknRedirectToRoute() only —
 * it resolves the app's base path, so redirects work whether the project
 * sits at the web root or in an htdocs subfolder. Including it defines
 * the guard functions but calls none of them.
 */

// ---------------------------------------------------------------------
// Local helpers. These live here rather than in backend/helpers/ because
// this task was scoped to creating this one file.
// ---------------------------------------------------------------------

if (!function_exists('uknRegisterFail')) {
    /**
     * Send the user back to the form with their errors and their input.
     * Always exits — a redirect that returns is not a redirect.
     *
     * @param array<string, string> $errors field => message
     * @param array<string, mixed>  $old    values to re-fill, never passwords
     */
    function uknRegisterFail(array $errors, array $old = []): void
    {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_old']    = $old;

        uknRedirectToRoute('register');
    }
}

if (!function_exists('uknRegisterAbort')) {
    /**
     * Stop with a bare status code and a plain-text line.
     *
     * Used for the two cases that are not a user filling in a form badly
     * — a wrong HTTP method, and a failed CSRF check. Neither should be
     * redirected into the form, because neither came from the form.
     *
     * Plain text, never HTML, and never anything derived from input.
     */
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
    /**
     * Turn a full name into the 2-character avatar initials the whole
     * frontend renders. users.initials is NOT NULL with no default, and
     * the register form has no field for it, so it must be derived here
     * or the INSERT fails outright.
     *
     *   "Nabila Rahman"        -> "NR"
     *   "Imran  Chowdhury"     -> "IC"   (extra spaces ignored)
     *   "Ayesha binte Rahman"  -> "AR"   (first + LAST word, not middle)
     *   "Prince"               -> "PR"   (single word -> first two letters)
     *
     * Multibyte-safe: mb_* throughout, so a non-ASCII first letter is
     * taken as one character rather than half a byte sequence.
     */
    function uknDeriveInitials(string $fullName): string
    {
        // Keep letters and separators only, so "O'Brien-Smith" and
        // "Rahman (CSE)" both still yield sensible initials.
        $cleaned = preg_replace('/[^\p{L}\s]+/u', ' ', $fullName);

        if ($cleaned === null) {
            $cleaned = $fullName; // malformed UTF-8 — fall back to the raw value
        }

        $parts = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY);

        if (!is_array($parts) || $parts === []) {
            return 'U'; // nothing usable; still satisfies NOT NULL
        }

        if (count($parts) === 1) {
            $initials = mb_substr($parts[0], 0, 2, 'UTF-8');
        } else {
            $initials = mb_substr($parts[0], 0, 1, 'UTF-8')
                . mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');
        }

        // Column is VARCHAR(4); mb_substr guards against a multibyte
        // uppercase expanding past it.
        return mb_substr(mb_strtoupper($initials, 'UTF-8'), 0, 4, 'UTF-8');
    }
}

if (!function_exists('uknResolveDepartmentId')) {
    /**
     * Look up a department id from the name the form submitted.
     *
     * The select in pages/auth/register.php posts a display NAME
     * ("Computer Science"), while users.department_id is a foreign key.
     * The posted value is never trusted: it is matched against the
     * departments table, and only an ACTIVE row counts, so a deactivated
     * department cannot be joined through a hand-crafted POST.
     *
     * @return int|null the id, or null when the name matches nothing
     */
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

// ---------------------------------------------------------------------
// 1. POST only
// ---------------------------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        header('Allow: POST');
    }

    uknRegisterAbort(405, 'Method Not Allowed. This endpoint accepts POST only.');
}

// ---------------------------------------------------------------------
// 2. CSRF — before anything else, and before any database contact
// ---------------------------------------------------------------------

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    uknRegisterAbort(403, 'Invalid or expired form token. Please reload the page and try again.');
}

// ---------------------------------------------------------------------
// 3. Collect and map. The form uses camelCase names; the validator and
//    the User model use snake_case. Fields are read INDIVIDUALLY and
//    never as a bulk copy of $_POST, so no extra key a caller invents
//    (role, is_admin, status, id...) can reach the database.
// ---------------------------------------------------------------------

$input = [
    'full_name'     => sanitizeInput($_POST['fullName'] ?? ''),
    'email'         => sanitizeInput($_POST['email'] ?? ''),
    'university_id' => sanitizeInput($_POST['universityId'] ?? ''),
    'department'    => sanitizeInput($_POST['department'] ?? ''),

    // Passwords are NOT sanitised. Every character typed is part of the
    // password; trimming or stripping any of it here would mean the
    // stored hash no longer matches what the user types at login.
    'password'         => is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
    'confirm_password' => is_string($_POST['confirmPassword'] ?? null) ? $_POST['confirmPassword'] : '',
];

// Re-fill values for the form on failure — text fields only, no passwords.
$old = [
    'fullName'     => $input['full_name'],
    'email'        => $input['email'],
    'universityId' => $input['university_id'],
    'department'   => $input['department'],
];

// ---------------------------------------------------------------------
// 4. Validate. No database is touched until this passes, so a malformed
//    payload can never be used to probe for existing accounts.
// ---------------------------------------------------------------------

$validation = validateRegistrationData($input);

if (!$validation['valid']) {
    uknRegisterFail($validation['errors'], $old);
}

// ---------------------------------------------------------------------
// 5. Database work
// ---------------------------------------------------------------------

try {
    // One connection, shared by every query below. getDatabaseConnection()
    // builds a NEW connection per call, and a transaction cannot span two.
    $pdo = getDatabaseConnection();
    $userModel = new User($pdo);

    $errors = [];

    // 5a. Duplicates. Checked together so the user sees every clash at
    //     once rather than fixing one and discovering the other.
    if ($userModel->emailExists($input['email'])) {
        $errors['email'] = 'This email is already registered.';
    }

    if ($userModel->universityIdExists($input['university_id'])) {
        $errors['university_id'] = 'This university ID is already registered.';
    }

    // 5b. Department name -> id.
    $departmentId = uknResolveDepartmentId($pdo, $input['department']);

    if ($departmentId === null) {
        $errors['department'] = 'Invalid department selected.';
    }

    if ($errors !== []) {
        uknRegisterFail($errors, $old);
    }

    // 5c. Derive what the schema needs but the form does not collect.
    $initials = uknDeriveInitials($input['full_name']);

    // 5d. Hash. PASSWORD_DEFAULT follows PHP's current recommendation, so
    //     this strengthens automatically as PHP updates its default.
    $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

    if (!is_string($passwordHash) || $passwordHash === '') {
        // Hashing effectively cannot fail on a sane install, but storing
        // an empty hash would create an account nobody could ever log in
        // to — worth refusing loudly rather than writing the row.
        throw new RuntimeException('Password hashing failed.');
    }

    // 5e. Create the user and its settings row atomically. Either both
    //     exist afterwards, or neither does — a user without settings
    //     would break pages/settings/settings.php on first visit.
    //
    //     role / status / is_admin are deliberately ABSENT here. They are
    //     not passed, not read from POST, and take their schema defaults
    //     ('learner', 'active', 0), so registration cannot mint a mentor
    //     or an administrator.
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
    // User::create() translates a duplicate-key collision into a
    // RuntimeException. That happens when two people submit the same
    // email within the same instant, so the checks at 5a both passed and
    // the UNIQUE index caught it a moment later. Re-check to find out
    // which field lost the race, and report it as an ordinary field error.
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
        // The re-check is best effort; fall through to the generic error.
    }

    if ($errors === []) {
        error_log('[UKN register] ' . $e->getMessage());
        $errors['form'] = 'Registration could not be completed. Please try again.';
    }

    uknRegisterFail($errors, $old);
} catch (Throwable $e) {
    // Anything else — connection loss, a schema mismatch, a disk error.
    // The real reason goes to the server log; the user gets a message
    // that reveals nothing about the database.
    error_log('[UKN register] ' . $e->getMessage());

    uknRegisterFail(
        ['form' => 'Registration could not be completed. Please try again.'],
        $old
    );
}

// ---------------------------------------------------------------------
// 6. Success
//
// NO SESSION IS CREATED HERE. setAuthSession() is not called, $_SESSION
// gains no user_id, and isLoggedIn() stays false. Registering does not
// sign you in — the user is sent to the login page to authenticate,
// which is what pages/auth/register.php's own [data-continue-to-login]
// element already anticipates.
// ---------------------------------------------------------------------

unset($_SESSION['register_errors'], $_SESSION['register_old']);

$_SESSION['flash_success'] = 'Your account has been created. Please log in.';

uknRedirectToRoute('login');
