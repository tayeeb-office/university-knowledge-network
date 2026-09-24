<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../models/User.php';
if (!function_exists('getCurrentUser')) {
    /**
     * The authenticated user's database row, or null for guests.
     *
     * The session only holds the user id; the row is re-read from the database once per
     * request (cached), so name/role/status changes apply immediately. A session whose user
     * was deleted, deactivated, suspended or is unverified is logged out.
     */
    function getCurrentUser(): ?array
    {
        static $resolved = false;
        static $user = null;
        if ($resolved) {
            return $user;
        }
        $resolved = true;

        $userId = getCurrentUserId();
        if ($userId === null || $userId <= 0) {
            return null;
        }
        try {
            $row = (new User())->findSessionUser($userId);
        } catch (Throwable $e) {
            // Database unavailable: treat this request as a guest, but keep the session.
            error_log('[UKN auth] ' . $e->getMessage());
            return null;
        }
        if ($row === null || $row['status'] !== 'active' || $row['email_verified_at'] === null) {
            clearAuthSession();
            return null;
        }
        $user = $row;
        return $user;
    }
}
if (!function_exists('uknIsSafeRedirectPath')) {
    /**
     * Only same-site absolute paths inside this app may be used as post-login destinations.
     */
    function uknIsSafeRedirectPath($path): bool
    {
        if (!is_string($path) || $path === '' || $path[0] !== '/' || strpbrk($path, "\\\r\n") !== false) {
            return false;
        }
        if (strpos($path, '//') === 0) {
            return false;
        }
        return strpos($path, uknBaseUrl() . '/') === 0;
    }
}
if (!function_exists('uknBaseUrl')) {
    function uknBaseUrl(): string
    {
        if (defined('UKN_BASE_URL')) {
            return rtrim((string) UKN_BASE_URL, '/');
        }
        $appRoot = str_replace('\\', '/', dirname(__DIR__, 2));
        // Works under an Apache Alias too: strip the script's path inside the app from the
        // script's URL path, e.g. /ukn/backend/auth/login.php - /backend/auth/login.php = /ukn.
        $scriptFile = str_replace('\\', '/', (string) realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')));
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptFile !== '' && stripos($scriptFile, $appRoot . '/') === 0) {
            $inApp = substr($scriptFile, strlen($appRoot));
            if ($scriptName !== '' && strcasecmp(substr($scriptName, -strlen($inApp)), $inApp) === 0) {
                return rtrim(substr($scriptName, 0, -strlen($inApp)), '/');
            }
        }
        $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
        if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
            return rtrim(substr($appRoot, strlen($docRoot)), '/');
        }
        return '';
    }
}
if (!function_exists('uknRouteUrl')) {
    function uknRouteUrl(string $route): string
    {
        return uknBaseUrl() . '/index.php?page=' . rawurlencode($route);
    }
}
if (!function_exists('uknRedirectToRoute')) {
    function uknRedirectToRoute(string $route): void
    {
        if (!headers_sent()) {
            header('Location: ' . uknRouteUrl($route), true, 302);
        }
        exit;
    }
}
if (!function_exists('uknReturnToField')) {
    /** Hidden field that lets a write endpoint send the user back to the current page. */
    function uknReturnToField(): string
    {
        $path = (string) ($_SERVER['REQUEST_URI'] ?? '');
        return uknIsSafeRedirectPath($path)
            ? '<input type="hidden" name="return_to" value="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '">'
            : '';
    }
}
// Role model: users.role is the capability ('learner', 'mentor', or 'dual' = both) and
// users.is_admin is independent of it. The active role (learner|mentor) is per-session
// state in $_SESSION['active_role'], always re-checked against the DB capability.
if (!function_exists('uknUserCanActAs')) {
    function uknUserCanActAs(array $user, string $role): bool
    {
        if ($role === 'learner') {
            return in_array($user['role'] ?? '', ['learner', 'dual'], true);
        }
        if ($role === 'mentor') {
            return in_array($user['role'] ?? '', ['mentor', 'dual'], true);
        }
        return false;
    }
}
if (!function_exists('uknDefaultActiveRole')) {
    /**
     * Same default setAuthSession() applies at login: mentor-only users start as mentor,
     * learners and dual-role users start as learner.
     */
    function uknDefaultActiveRole(array $user): string
    {
        return ($user['role'] ?? '') === 'mentor' ? 'mentor' : 'learner';
    }
}
if (!function_exists('getCurrentActiveRole')) {
    /**
     * The logged-in user's active role, or null for guests. A session value the user's
     * current DB capability no longer allows (or a missing/garbage one) is replaced by the
     * default, so the session can never hold a role the user may not act as.
     */
    function getCurrentActiveRole(): ?string
    {
        $user = getCurrentUser();
        if ($user === null) {
            return null;
        }
        $role = $_SESSION['active_role'] ?? null;
        if (!is_string($role) || !uknUserCanActAs($user, $role)) {
            $role = uknDefaultActiveRole($user);
            $_SESSION['active_role'] = $role;
        }
        return $role;
    }
}
if (!function_exists('uknDashboardRoute')) {
    function uknDashboardRoute(): string
    {
        return getCurrentActiveRole() === 'mentor' ? 'mentor-dashboard' : 'learner-dashboard';
    }
}
if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        if (getCurrentUser() !== null) {
            return;
        }
        // Only remember page views; never replay a POST target after login.
        if (!empty($_SERVER['REQUEST_URI']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $_SESSION['redirect_after_login'] = (string) $_SERVER['REQUEST_URI'];
        }
        uknRedirectToRoute('login');
    }
}
if (!function_exists('redirectIfLoggedIn')) {

    function redirectIfLoggedIn(): void
    {
        if (getCurrentUser() === null) {
            return;
        }
        uknRedirectToRoute(uknDashboardRoute());
    }
}
if (!function_exists('requireAdmin')) {
    function requireAdmin(): void
    {
        requireLogin();
        // is_admin is read from the database row, not the login-time session snapshot.
        if (empty(getCurrentUser()['is_admin'])) {
            uknRedirectToRoute('403');
        }
    }
}
if (!function_exists('requireLearner')) {
    /** Logged in and currently acting as learner. */
    function requireLearner(): void
    {
        requireLogin();
        if (getCurrentActiveRole() !== 'learner') {
            uknRedirectToRoute('403');
        }
    }
}
if (!function_exists('requireMentor')) {
    /** Logged in and currently acting as mentor. */
    function requireMentor(): void
    {
        requireLogin();
        if (getCurrentActiveRole() !== 'mentor') {
            uknRedirectToRoute('403');
        }
    }
}
