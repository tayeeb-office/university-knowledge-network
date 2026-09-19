<?php
/**
 * Access guards — the three checks a protected page runs before it
 * renders anything.
 *
 *     require_once __DIR__ . '/backend/helpers/auth.php';
 *     requireLogin();
 *
 * PLACEHOLDER STATUS
 * ------------------
 * These are fully written, but they are INERT until a real login handler
 * exists: nothing calls setAuthSession() yet, so isLoggedIn() is always
 * false and requireLogin() would bounce every single request straight to
 * the login page.
 *
 *   >> DO NOT WIRE THESE INTO index.php OR admin/*.php YET. <<
 *
 * Wire them up in the same change that adds backend/auth/login.php, not
 * before, or the app locks itself out.
 *
 * Every guard ends in exit. A guard that returns after sending a Location
 * header is not a guard — PHP would carry on and render the protected
 * page underneath the redirect.
 *
 * NOTE: no closing `?>` tag, deliberately. Trailing whitespace after one
 * would be sent to the browser, set headers_sent(), and make every
 * redirect below fail silently.
 */

require_once __DIR__ . '/session.php';

if (!function_exists('uknBaseUrl')) {
    /**
     * The app's base path as the browser sees it, e.g.
     * "/university-knowledge-network-frontend" under XAMPP, or "" when
     * the project is served from the web root.
     *
     * This exists because a bare "index.php?page=login" redirect resolves
     * against the REQUESTING url, not this file. From /index.php that is
     * correct; from /admin/users.php it would resolve to
     * /admin/index.php?page=login, which does not exist. Every redirect
     * below therefore goes through here.
     *
     * Derived from DOCUMENT_ROOT. If your setup makes that unreliable
     * (a symlinked vhost, or a drive-letter case mismatch on Windows),
     * define UKN_BASE_URL before including this file and it wins:
     *
     *     define('UKN_BASE_URL', '/university-knowledge-network-frontend');
     */
    function uknBaseUrl(): string
    {
        if (defined('UKN_BASE_URL')) {
            return rtrim((string) UKN_BASE_URL, '/');
        }

        // backend/helpers/ -> backend/ -> project root
        $appRoot = str_replace('\\', '/', dirname(__DIR__, 2));
        $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));

        if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
            return rtrim(substr($appRoot, strlen($docRoot)), '/');
        }

        return ''; // assume the project IS the web root
    }
}

if (!function_exists('uknRouteUrl')) {
    /**
     * Build a URL for one of index.php's whitelisted route slugs.
     *
     * The slug set is owned by index.php's $routes map — this only ever
     * needs to know the slug, exactly like the frontend's own
     * ukn_route_href() in includes/left-sidebar.php.
     */
    function uknRouteUrl(string $route): string
    {
        return uknBaseUrl() . '/index.php?page=' . rawurlencode($route);
    }
}

if (!function_exists('uknRedirectToRoute')) {
    /**
     * Send a 302 to a whitelisted route and stop. Always stops, even if
     * the header could not be sent, so a caller can never accidentally
     * continue past a failed guard.
     */
    function uknRedirectToRoute(string $route): void
    {
        if (!headers_sent()) {
            header('Location: ' . uknRouteUrl($route), true, 302);
        }

        exit;
    }
}

if (!function_exists('requireLogin')) {
    /**
     * Gate a page behind authentication.
     *
     * Intended for every route except the public ones the frontend
     * already treats as visitor-reachable — home, skills, find-mentors,
     * login, register and the error pages. That list is not invented
     * here: it is exactly ukn_nav_groups_for_role('visitor') in
     * includes/left-sidebar.php.
     *
     * Remembers where the user was heading so login can return them
     * there instead of always dropping them on a dashboard.
     */
    function requireLogin(): void
    {
        if (isLoggedIn()) {
            return;
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['redirect_after_login'] = (string) $_SERVER['REQUEST_URI'];
        }

        uknRedirectToRoute('login');
    }
}

if (!function_exists('redirectIfLoggedIn')) {
    /**
     * The inverse guard, for pages/auth/login.php and register.php: an
     * already-signed-in user has no business on either.
     *
     * Sends them to the dashboard for whichever role they are currently
     * acting as, matching includes/profile-dropdown.php's own Dashboard
     * link behaviour.
     */
    function redirectIfLoggedIn(): void
    {
        if (!isLoggedIn()) {
            return;
        }

        uknRedirectToRoute(
            getActiveRole() === 'mentor' ? 'mentor-dashboard' : 'learner-dashboard'
        );
    }
}

if (!function_exists('requireAdmin')) {
    /**
     * Gate the admin panel. Intended as line 1 of all 11 admin/*.php
     * files, which currently have NO access check of any kind.
     *
     * Two distinct outcomes on purpose:
     *   - not signed in at all -> login (they may well be an admin)
     *   - signed in, not admin -> 403 (they are, and the answer is no)
     *
     * Note that '403' is currently a preview-only route in index.php and
     * still returns HTTP 200. Once this guard is live, that route should
     * send a real 403 status the way '404' already does.
     */
    function requireAdmin(): void
    {
        if (!isLoggedIn()) {
            if (!empty($_SERVER['REQUEST_URI'])) {
                $_SESSION['redirect_after_login'] = (string) $_SERVER['REQUEST_URI'];
            }

            uknRedirectToRoute('login');
        }

        if (!isCurrentUserAdmin()) {
            uknRedirectToRoute('403');
        }
    }
}
