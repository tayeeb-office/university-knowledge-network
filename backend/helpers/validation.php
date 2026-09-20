<?php
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
if (!defined('UKN_MAX_PASSWORD_BYTES')) {
    define('UKN_MAX_PASSWORD_BYTES', 72);
}
if (!function_exists('validateRequired')) {
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
    function validatePasswordMatch($password, $confirmPassword): bool
    {
        if (!is_string($password) || !is_string($confirmPassword)) {
            return false;
        }
        return $password === $confirmPassword;
    }
}
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = (string) $value;

        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        if ($clean === null) {
            $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        }
        return trim((string) $clean);
    }
}
if (!function_exists('validateRegistrationData')) {
    function validateRegistrationData(array $data): array
    {
        $errors = [];
        $fullName = sanitizeInput($data['full_name'] ?? '');
        if (!validateRequired($fullName)) {
            $errors['full_name'] = 'Please enter your full name.';
        } elseif (!validateLength($fullName, 2, UKN_MAX_FULL_NAME)) {
            $errors['full_name'] = 'Full name must be between 2 and ' . UKN_MAX_FULL_NAME . ' characters.';
        }
        $email = sanitizeInput($data['email'] ?? '');
        if (!validateRequired($email)) {
            $errors['email'] = 'Please enter your university email.';
        } elseif (!validateEmail($email)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (!validateLength($email, 1, UKN_MAX_EMAIL)) {
            $errors['email'] = 'Email must be ' . UKN_MAX_EMAIL . ' characters or fewer.';
        }
        $universityId = sanitizeInput($data['university_id'] ?? '');
        if (!validateRequired($universityId)) {
            $errors['university_id'] = 'Please enter your University ID.';
        } elseif (!validateLength($universityId, 3, UKN_MAX_UNIVERSITY_ID)) {
            $errors['university_id'] = 'University ID must be between 3 and ' . UKN_MAX_UNIVERSITY_ID . ' characters.';
        }

        $department = sanitizeInput($data['department'] ?? '');
        if (!validateRequired($department)) {
            $errors['department'] = 'Please select your department.';
        }

        $password = $data['password'] ?? null;
        $passwordResult = validatePassword($password);
        if (!$passwordResult['valid']) {
            $errors['password'] = $passwordResult['errors'][0];
        }
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