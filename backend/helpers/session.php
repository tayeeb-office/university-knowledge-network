<?php
/**
 * Session foundation — safe session startup plus the small set of helpers
 * every other backend file uses to answer "who is making this request?".
 *
 * Including this file is enough. It configures the session cookie and
 * starts the session itself (guarded, once), so nothing else in the
 * project should ever call session_start() directly:
 *
 *     require_once __DIR__ . '/../helpers/session.php';
 *
 * NO DATABASE ACCESS HAPPENS HERE. This file only reads back what a
 * future login handler put into the session; it never looks a user up.
 * That keeps it safe to include from index.php, from admin/*.php, and
 * from a CLI script, without dragging a PDO connection along.
 *
 * WHAT IS DELIBERATELY NOT STORED IN THE SESSION:
 *   name, email, initials, department, points, avatar.
 * Only the user's id, role and admin flag live here. Everything else is
 * re-fetched per request, so a profile edit or a points change is never
 * served from a stale copy sitting in the session file.
 *
 * Requires PHP 7.3+ (the array form of session_set_cookie_params, which
 * is what allows SameSite to be set at all).
 *
 * NOTE: this file intentionally has no closing `?>` tag. Any whitespace
 * after one would be sent to the browser, which sets headers_sent() and
 * would silently break every redirect in backend/helpers/auth.php.
 */

if (!function_exists('startSecureSession')) {
    /**
     * Start the session once, with hardened cookie settings.
     *
     * Safe to call repeatedly — a session that is already active is left
     * alone rather than restarted (restarting emits a PHP notice and
     * throws away the current session data).
     */
    function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return; // already running — never start a second time
        }

        if (headers_sent()) {
            // Too late to send Set-Cookie. Starting now would only produce
            // a warning and a session the browser will not keep, so don't.
            return;
        }

        // Session ids may only travel in cookies, never in the URL, and a
        // client may not invent its own id — the two standard session
        // fixation defences. Both must be set before session_start().
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        // secure=true would stop the cookie working on plain http, which
        // is how this project runs under XAMPP, so it tracks the actual
        // connection instead of being hardcoded either way.
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        session_set_cookie_params([
            'lifetime' => 0,        // expires when the browser closes
            'path'     => '/',
            'domain'   => '',       // current host only
            'secure'   => $isHttps, // https only, when we are on https
            'httponly' => true,     // JavaScript can never read this cookie
            'samesite' => 'Lax',    // survives normal navigation, blocks cross-site POST
        ]);

        session_name('UKNSESSID');
        session_start();
    }
}

if (!function_exists('isLoggedIn')) {
    /**
     * Is there an authenticated user on this request?
     *
     * Until a real login handler calls setAuthSession(), this is always
     * false — which is exactly the safe default.
     */
    function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('getCurrentUserId')) {
    /**
     * The signed-in user's users.id, or null when nobody is signed in.
     *
     * Returning null rather than 0 keeps "no user" impossible to confuse
     * with a real id in a query.
     */
    function getCurrentUserId(): ?int
    {
        return isLoggedIn() ? (int) $_SESSION['user_id'] : null;
    }
}

if (!function_exists('getActiveRole')) {
    /**
     * Which role the user is currently acting as: 'learner' or 'mentor'.
     *
     * This is the SERVER-SIDE home of the value that the frontend
     * currently keeps only in localStorage (assets/js/core/role-switch.js).
     * Moving it here is what will let the 14 pages that branch on
     * $isMentor actually follow a role switch — see the backend
     * preparation report.
     *
     * Falls back to 'learner' for signed-out visitors, matching the
     * frontend's own default.
     */
    function getActiveRole(): string
    {
        $role = $_SESSION['active_role'] ?? 'learner';

        return $role === 'mentor' ? 'mentor' : 'learner';
    }
}

if (!function_exists('isCurrentUserAdmin')) {
    /**
     * Does this user hold the users.is_admin flag?
     *
     * Admin is a FLAG, not a value of users.role — the frontend keeps the
     * two systems separate on purpose (see admin/includes/header.php), and
     * database/schema.sql mirrors that.
     */
    function isCurrentUserAdmin(): bool
    {
        return isLoggedIn() && !empty($_SESSION['is_admin']);
    }
}

if (!function_exists('setAuthSession')) {
    /**
     * Mark this session as authenticated. Called by the future
     * backend/auth/login.php once a password has been verified.
     *
     * @param array $user A row from the `users` table. Only id, role and
     *                    is_admin are read; anything else is ignored.
     *
     * The session id is regenerated first. That is the standard defence
     * against session fixation: an id an attacker planted before login
     * stops being valid the moment privileges change.
     */
    function setAuthSession(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $role = $user['role'] ?? 'learner';
        if (!in_array($role, ['learner', 'mentor', 'dual'], true)) {
            $role = 'learner'; // never trust a value outside the schema ENUM
        }

        // A dual-role user lands in the learner view first, matching
        // assets/js/core/role-switch.js's own default.
        $activeRole = ($role === 'mentor') ? 'mentor' : 'learner';

        $_SESSION['user_id']      = (int) ($user['id'] ?? 0);
        $_SESSION['role']         = $role;
        $_SESSION['active_role']  = $activeRole;
        $_SESSION['is_admin']     = !empty($user['is_admin']);
        $_SESSION['logged_in_at'] = time();
    }
}

if (!function_exists('destroySession')) {
    /**
     * Sign the user out completely. Called by the future
     * backend/auth/logout.php.
     *
     * Clears the data, expires the cookie, then destroys the session —
     * in that order. Skipping the cookie step is the usual logout bug:
     * the server-side session is gone but the browser keeps presenting a
     * dead id on every subsequent request.
     */
    function destroySession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        // Expire the cookie using the SAME parameters it was set with.
        // A mismatched path or domain leaves the original cookie in place.
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

// Starting on include is what lets every other backend file simply
// require this one and immediately ask isLoggedIn(). Guarded above, so
// including it twice — or after another file already started a session —
// is harmless.
startSecureSession();
