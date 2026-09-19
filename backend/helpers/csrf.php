<?php
/**
 * CSRF protection — one token per session, checked on every state-changing
 * request.
 *
 *     require_once __DIR__ . '/backend/helpers/csrf.php';
 *
 *     <form method="post" action="...">
 *         <?= csrfField() ?>
 *         ...
 *     </form>
 *
 *     // in the handler
 *     if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
 *         http_response_code(403);
 *         exit('Your session expired. Please reload the page and try again.');
 *     }
 *
 * WHY THIS IS NEEDED
 * ------------------
 * Without it, any other site can make a visitor's browser submit a POST to
 * this app carrying their session cookie — creating a post, cancelling a
 * session, suspending a user. The SameSite=Lax cookie set in
 * backend/helpers/session.php already blocks the simplest version of that
 * attack, but SameSite is a browser-side mitigation, not a guarantee: it
 * is weaker on older browsers and does not cover every case. The token is
 * the actual defence; SameSite is the seatbelt on top of it.
 *
 * SCOPE
 * -----
 * One token per session, not one per form. It is reused for the life of
 * the session, which is standard and keeps multiple open tabs working —
 * a per-form token would invalidate every other tab the moment one of
 * them submitted.
 *
 * No database access, and no frontend file is touched. This only reads
 * and writes $_SESSION, via the session system already established in
 * backend/helpers/session.php.
 *
 * NOTE: no closing `?>` tag, deliberately — matching session.php and
 * auth.php. Whitespace after one would be sent to the browser and break
 * every redirect in auth.php.
 */

require_once __DIR__ . '/session.php';

if (!defined('UKN_CSRF_SESSION_KEY')) {
    define('UKN_CSRF_SESSION_KEY', 'csrf_token');
}

if (!defined('UKN_CSRF_FIELD_NAME')) {
    define('UKN_CSRF_FIELD_NAME', 'csrf_token');
}

if (!function_exists('csrfToken')) {
    /**
     * The current session's CSRF token, creating one on first use.
     *
     * Returns the SAME token for the life of the session — calling this
     * twice while rendering two forms on one page gives both the same
     * value, which is what makes multiple open tabs work.
     *
     * 32 random bytes rendered as 64 hex characters. random_bytes() is
     * cryptographically secure; it is deliberately NOT wrapped in a
     * try/catch, because the only fallback would be a weaker source of
     * randomness, and a predictable CSRF token is worse than no token at
     * all. If the platform genuinely cannot produce randomness, this
     * should fail loudly rather than quietly degrade.
     *
     * @throws RuntimeException if no session is active, since a token
     *         that cannot be stored could never verify.
     */
    function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // session.php normally starts this on include; try once more
            // in case this was reached before that ran.
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
    /**
     * Does a submitted token match the one in this session?
     *
     * FAILS CLOSED. Returns false — never throws — for a missing session,
     * a missing stored token, a null/array/non-string submission, or an
     * empty string. A bad token is an expected condition on a public
     * endpoint, not an exceptional one, so this never interrupts the
     * caller's own error handling.
     *
     * Comparison is hash_equals(), which takes constant time regardless
     * of where the two strings first differ. A plain === leaks, through
     * timing, how much of a guess was correct, which is enough to
     * reconstruct a token byte by byte.
     *
     * @param mixed $token Usually $_POST['csrf_token'] — deliberately
     *                     typed loosely, because that value is whatever
     *                     the client sent and may not be a string at all.
     */
    function verifyCsrf($token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $stored = $_SESSION[UKN_CSRF_SESSION_KEY] ?? null;

        if (!is_string($stored) || $stored === '') {
            return false; // nothing issued yet — nothing can match it
        }

        if (!is_string($token) || $token === '') {
            return false; // missing, or an array from ?csrf_token[]=...
        }

        // Known-good value first, submitted value second.
        return hash_equals($stored, $token);
    }
}

if (!function_exists('csrfField')) {
    /**
     * The hidden input to drop inside any POST form.
     *
     *     <?= csrfField() ?>
     *
     * Returns the markup rather than echoing it, so the caller stays in
     * control of where it lands.
     *
     * The token is hex, so htmlspecialchars() can never actually change
     * it — it is applied anyway, because "escape on output, every time,
     * without checking whether this particular value needs it" is the
     * habit that prevents the one case where it does. The rest of this
     * project escapes the same way (see components/*.php).
     */
    function csrfField(): string
    {
        $token = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . UKN_CSRF_FIELD_NAME . '" value="' . $token . '">';
    }
}
