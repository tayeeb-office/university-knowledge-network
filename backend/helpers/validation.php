<?php
/**
 * Server-side validation — the authoritative half of the rules that
 * assets/js/core/validation.js currently enforces in the browser.
 *
 *     require_once __DIR__ . '/backend/helpers/validation.php';
 *
 * The client-side checks are a convenience for the person filling in the
 * form. They are not a control: anything in the browser can be edited,
 * disabled or bypassed entirely by posting straight to the endpoint.
 * Every rule that matters has to be re-run here, on values the server
 * received, before anything reaches the database.
 *
 * SCOPE
 * -----
 * Pure functions over values. No database queries, no session access, no
 * redirects, no output of any kind. That keeps these usable from an
 * endpoint, from a CLI import script, and from a future admin form
 * without dragging a connection or a session along.
 *
 * Checks that genuinely need the database — "is this email already
 * taken?", "is this a real department id?" — deliberately live in
 * backend/models/User.php instead. This file never answers a question it
 * would need a query for.
 *
 * NOTE: no closing `?>` tag, matching the rest of backend/.
 */

/**
 * Length bounds, traced to database/schema.sql so a value that passes
 * here can never be silently truncated on INSERT.
 *   users.full_name     VARCHAR(120)
 *   users.email         VARCHAR(160)
 *   users.university_id VARCHAR(30)
 */
if (!defined('UKN_MAX_FULL_NAME')) {
    define('UKN_MAX_FULL_NAME', 120);
}
if (!defined('UKN_MAX_EMAIL')) {
    define('UKN_MAX_EMAIL', 160);
}
if (!defined('UKN_MAX_UNIVERSITY_ID')) {
    define('UKN_MAX_UNIVERSITY_ID', 30);
}
if (!defined('UKN_MIN_PASSWORD')) {
    define('UKN_MIN_PASSWORD', 8);
}

/**
 * bcrypt — which is what password_hash(PASSWORD_DEFAULT) uses — ignores
 * everything past 72 BYTES. A longer password is silently truncated, so
 * two different passwords sharing their first 72 bytes would both log in.
 * Rejecting them outright is the honest behaviour.
 */
if (!defined('UKN_MAX_PASSWORD_BYTES')) {
    define('UKN_MAX_PASSWORD_BYTES', 72);
}

if (!function_exists('validateRequired')) {
    /**
     * Is a value actually present?
     *
     * Not empty() — empty() calls the string '0' empty, and '0' is a
     * perfectly good answer to a required field. This only treats null,
     * a whitespace-only string, and an empty array as missing.
     *
     * @param mixed $value
     */
    function validateRequired($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        if (is_bool($value)) {
            return $value;
        }

        return trim((string) $value) !== '';
    }
}

if (!function_exists('validateEmail')) {
    /**
     * Is this a syntactically valid email address?
     *
     * filter_var() with FILTER_VALIDATE_EMAIL is stricter than the
     * browser-side regex in assets/js/core/validation.js
     * (/^[^\s@]+@[^\s@]+\.[^\s@]+$/), which accepts things like
     * "a b"@x.y and double dots. A value can therefore pass in the
     * browser and fail here — that is the correct direction for the two
     * to disagree.
     *
     * Syntax only. Whether the mailbox exists, or sits on a university
     * domain, is a separate question this does not answer.
     *
     * @param mixed $email
     */
    function validateEmail($email): bool
    {
        if (!is_scalar($email)) {
            return false;
        }

        $email = trim((string) $email);

        if ($email === '') {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validateLength')) {
    /**
     * Is a string's length within range, inclusive?
     *
     * Counts CHARACTERS via mb_strlen, not bytes. That matters because
     * MySQL's VARCHAR(120) also counts characters — a name with accented
     * letters would otherwise be rejected here at a length the database
     * would have accepted quite happily.
     *
     * $max defaults to unbounded so this doubles as a minimum-only check.
     *
     * @param mixed $value
     */
    function validateLength($value, int $min = 0, int $max = PHP_INT_MAX): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        $length = mb_strlen(trim((string) $value), 'UTF-8');

        return $length >= $min && $length <= $max;
    }
}

if (!function_exists('validatePassword')) {
    /**
     * Apply the password policy.
     *
     * HARD FAILURES (land in 'errors', make valid false):
     *   - shorter than UKN_MIN_PASSWORD characters
     *   - longer than 72 bytes, which bcrypt would silently truncate
     *
     * RECOMMENDATIONS (land in 'warnings', do NOT affect valid):
     *   - no uppercase letter
     *   - no lowercase letter
     *   - no digit
     *
     * The split follows the brief, which lists 8 characters as the
     * minimum and the three character classes as recommended. The
     * browser's strength meter (assets/js/pages/auth.js) scores exactly
     * these same three classes and never blocks submission either, so
     * the two agree.
     *
     * To make the character classes mandatory instead, move the three
     * $warnings[] lines into $errors[] — nothing else needs to change.
     *
     * Passwords are never trimmed. A leading or trailing space is a
     * legitimate character, and silently stripping it here would mean
     * the password stored at registration differs from the one checked
     * at login.
     *
     * @param mixed $password
     * @return array{valid: bool, errors: string[], warnings: string[]}
     */
    function validatePassword($password): array
    {
        $errors = [];
        $warnings = [];

        if (!is_string($password)) {
            return [
                'valid'    => false,
                'errors'   => ['Please enter a password.'],
                'warnings' => [],
            ];
        }

        $length = mb_strlen($password, 'UTF-8');

        if ($length < UKN_MIN_PASSWORD) {
            $errors[] = 'Password must be at least ' . UKN_MIN_PASSWORD . ' characters.';
        }

        if (strlen($password) > UKN_MAX_PASSWORD_BYTES) {
            $errors[] = 'Password must be ' . UKN_MAX_PASSWORD_BYTES . ' characters or fewer.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $warnings[] = 'Adding an uppercase letter would make this password stronger.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $warnings[] = 'Adding a lowercase letter would make this password stronger.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $warnings[] = 'Adding a number would make this password stronger.';
        }

        return [
            'valid'    => $errors === [],
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }
}

if (!function_exists('validatePasswordMatch')) {
    /**
     * Do the password and its confirmation match?
     *
     * Compared exactly, with no trimming on either side, for the same
     * reason validatePassword() does not trim.
     *
     * @param mixed $password
     * @param mixed $confirmPassword
     */
    function validatePasswordMatch($password, $confirmPassword): bool
    {
        if (!is_string($password) || !is_string($confirmPassword)) {
            return false;
        }

        return $password === $confirmPassword;
    }
}

if (!function_exists('sanitizeInput')) {
    /**
     * Tidy a submitted value before it is validated or stored.
     *
     * Does: trims surrounding whitespace, strips NUL bytes and the other
     * non-printable control characters, normalises CRLF to LF. Tab,
     * newline and carriage return survive, because this is also used on
     * multi-line fields like a bio or a post body.
     *
     * Deliberately does NOT run htmlspecialchars(). That is the
     * "over-sanitizing" the brief warns against, and it would be wrong
     * here in a specific, checkable way: an ampersand in "Ferrari & Sons"
     * would be stored as "&amp;", then escaped AGAIN on output by the
     * htmlspecialchars() that every components/*.php already applies, and
     * the user would see "&amp;amp;" on their own profile. Escaping
     * belongs at the point of output, once, and this project already
     * does it there consistently.
     *
     * So: this makes a value safe to STORE. It does not make it safe to
     * PRINT — nothing does that except escaping at the moment of print.
     *
     * @param mixed $value
     */
    function sanitizeInput($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = (string) $value;

        // Normalise line endings first, so the control-character strip
        // below does not have to care about CR.
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        // Strip C0 controls and DEL, keeping tab (09) and newline (0A).
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        if ($clean === null) {
            // preg_replace returns null on malformed UTF-8 with /u. Retry
            // byte-wise so bad input is still cleaned rather than dropped.
            $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        }

        return trim((string) $clean);
    }
}

if (!function_exists('validateRegistrationData')) {
    /**
     * Validate a complete registration payload.
     *
     * Expected $data keys (snake_case — the endpoint is responsible for
     * mapping the form's own camelCase field names across, see the
     * mapping table in this helper's report):
     *   full_name, email, university_id, department,
     *   password, confirm_password
     *
     * Errors are keyed BY FIELD, with at most one message each, because
     * the register form renders exactly one [data-error-for="..."]
     * message per field. Reporting the first failure per field matches
     * what the page can actually display.
     *
     * NOT checked here, because both need a database query and this file
     * runs none — the endpoint must do these separately via
     * backend/models/User.php:
     *   - is the email already registered?      emailExists()
     *   - is the university ID already taken?   universityIdExists()
     *   - is 'department' a real department id? (resolve name -> id)
     *
     * Also not checked: the Terms checkbox. It is required by the form
     * but is not in this payload's field list, and there is no column to
     * store the consent in — see the authentication readiness report.
     *
     * @param array<string, mixed> $data
     * @return array{valid: bool, errors: array<string, string>}
     */
    function validateRegistrationData(array $data): array
    {
        $errors = [];

        // ---- full_name ----
        $fullName = sanitizeInput($data['full_name'] ?? '');

        if (!validateRequired($fullName)) {
            $errors['full_name'] = 'Please enter your full name.';
        } elseif (!validateLength($fullName, 2, UKN_MAX_FULL_NAME)) {
            $errors['full_name'] = 'Full name must be between 2 and ' . UKN_MAX_FULL_NAME . ' characters.';
        }

        // ---- email ----
        $email = sanitizeInput($data['email'] ?? '');

        if (!validateRequired($email)) {
            $errors['email'] = 'Please enter your university email.';
        } elseif (!validateEmail($email)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (!validateLength($email, 1, UKN_MAX_EMAIL)) {
            $errors['email'] = 'Email must be ' . UKN_MAX_EMAIL . ' characters or fewer.';
        }

        // ---- university_id ----
        $universityId = sanitizeInput($data['university_id'] ?? '');

        if (!validateRequired($universityId)) {
            $errors['university_id'] = 'Please enter your University ID.';
        } elseif (!validateLength($universityId, 3, UKN_MAX_UNIVERSITY_ID)) {
            $errors['university_id'] = 'University ID must be between 3 and ' . UKN_MAX_UNIVERSITY_ID . ' characters.';
        }

        // ---- department ----
        // Presence only. Whether the value names a real, active row in
        // `departments` is a database question, and is the endpoint's job.
        $department = sanitizeInput($data['department'] ?? '');

        if (!validateRequired($department)) {
            $errors['department'] = 'Please select your department.';
        }

        // ---- password ----
        // Not passed through sanitizeInput(): every character a user typed
        // is part of their password, including ones that function would
        // strip or trim.
        $password = $data['password'] ?? null;
        $passwordResult = validatePassword($password);

        if (!$passwordResult['valid']) {
            $errors['password'] = $passwordResult['errors'][0];
        }

        // ---- confirm_password ----
        $confirmPassword = $data['confirm_password'] ?? null;

        if (!validateRequired($confirmPassword)) {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif (!validatePasswordMatch($password, $confirmPassword)) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        return [
            'valid'  => $errors === [],
            'errors' => $errors,
        ];
    }
}
