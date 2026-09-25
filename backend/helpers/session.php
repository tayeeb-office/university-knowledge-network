<?php
require_once __DIR__ . '/error-handling.php';
if (!function_exists('startSecureSession')) {
    function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (headers_sent()) {
            return;
        }
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('UKNSESSID');
        session_start();
    }
}
if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId(): ?int
    {
        return isLoggedIn() ? (int) $_SESSION['user_id'] : null;
    }
}
if (!function_exists('getActiveRole')) {
    function getActiveRole(): string
    {
        $role = $_SESSION['active_role'] ?? 'learner';
        return $role === 'mentor' ? 'mentor' : 'learner';
    }
}
if (!function_exists('isCurrentUserAdmin')) {

    function isCurrentUserAdmin(): bool
    {
        return isLoggedIn() && !empty($_SESSION['is_admin']);
    }
}
if (!function_exists('setAuthSession')) {
    function setAuthSession(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $role = $user['role'] ?? 'learner';
        if (!in_array($role, ['learner', 'dual'], true)) {
            $role = 'learner';
        }
        // Every login starts in learner mode; learner + mentor accounts switch to mentor mode.
        $activeRole = 'learner';
        $_SESSION['user_id']      = (int) ($user['id'] ?? 0);
        $_SESSION['role']         = $role;
        $_SESSION['active_role']  = $activeRole;
        $_SESSION['is_admin']     = !empty($user['is_admin']);
        $_SESSION['logged_in_at'] = time();
        // Binds the session to the current password: getCurrentUser() ends it once the stored
        // password hash changes (e.g. after a password reset). Only a SHA-256 digest of the hash
        // is kept, server-side.
        $_SESSION['auth_fp']      = hash('sha256', (string) ($user['password_hash'] ?? ''));
        // New privilege level, new CSRF token (csrfToken() issues a fresh one on next use).
        unset($_SESSION['csrf_token']);
    }
}
if (!function_exists('clearAuthSession')) {
    /**
     * Drops only the authentication keys (e.g. when the session's user no longer exists or
     * is no longer allowed in) and rotates the session id; unrelated session data is kept.
     */
    function clearAuthSession(): void
    {
        unset(
            $_SESSION['user_id'],
            $_SESSION['role'],
            $_SESSION['active_role'],
            $_SESSION['is_admin'],
            $_SESSION['logged_in_at'],
            $_SESSION['auth_fp']
        );
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }
}
if (!function_exists('uknTakeFlash')) {
    /**
     * Reads a one-time session value (flash message, form errors, old input) and removes it.
     */
    function uknTakeFlash(string $key, $default = null)
    {
        if (!array_key_exists($key, $_SESSION ?? [])) {
            return $default;
        }
        $value = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $value;
    }
}

if (!function_exists('destroySession')) {
    function destroySession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
startSecureSession();
