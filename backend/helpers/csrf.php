<?php
require_once __DIR__ . '/session.php';
if (!defined('UKN_CSRF_SESSION_KEY')) {
    define('UKN_CSRF_SESSION_KEY', 'csrf_token');
}
if (!defined('UKN_CSRF_FIELD_NAME')) {
    define('UKN_CSRF_FIELD_NAME', 'csrf_token');
}
if (!function_exists('csrfToken')) {
    function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            startSecureSession();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException(
                'csrfToken() needs an active session. Include '
                . 'backend/helpers/session.php before any output is sent.'
            );
        }
        $existing = $_SESSION[UKN_CSRF_SESSION_KEY] ?? null;
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION[UKN_CSRF_SESSION_KEY] = $token;
        return $token;
    }
}
if (!function_exists('verifyCsrf')) {
    function verifyCsrf($token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        $stored = $_SESSION[UKN_CSRF_SESSION_KEY] ?? null;
        if (!is_string($stored) || $stored === '') {
            return false;
        }
        if (!is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }
}
if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        $token = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . UKN_CSRF_FIELD_NAME . '" value="' . $token . '">';
    }
}