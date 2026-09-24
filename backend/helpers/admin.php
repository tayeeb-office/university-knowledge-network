<?php
// Phase H (Steps 45–51): shared plumbing for admin write endpoints (backend/admin/<area>/<action>.php).
// Order for every mutation: POST only → requireAdmin() (DB-backed is_admin) → CSRF → validation.
require_once __DIR__ . '/actions.php';

if (!function_exists('uknAdminUrl')) {
    function uknAdminUrl(string $page): string
    {
        return uknBaseUrl() . '/admin/' . $page;
    }
}
if (!function_exists('uknAdminRedirect')) {
    /** Back to the posted return_to when it is an admin page of this app, else to $page. */
    function uknAdminRedirect(string $page): void
    {
        $target = uknPostString('return_to');
        $location = (uknIsSafeRedirectPath($target) && strpos((string) $target, uknBaseUrl() . '/admin/') === 0)
            ? (string) $target
            : uknAdminUrl($page);
        if (!headers_sent()) {
            header('Location: ' . $location, true, 302);
        }
        exit;
    }
}
if (!function_exists('uknAdminFail')) {
    function uknAdminFail(string $message, string $page): void
    {
        uknFlashToast('danger', $message);
        uknAdminRedirect($page);
    }
}
if (!function_exists('uknAdminDone')) {
    function uknAdminDone(string $message, string $page): void
    {
        uknFlashToast('success', $message);
        uknAdminRedirect($page);
    }
}
if (!function_exists('uknAdminBeginAction')) {
    /** Guards an admin write endpoint; returns the acting admin's id (from the DB-backed session user). */
    function uknAdminBeginAction(string $page): int
    {
        uknRequirePostMethod();
        requireAdmin();
        if (!verifyCsrf(uknPostString('csrf_token'))) {
            uknAdminFail('Your form expired. Please try again.', $page);
        }
        // Invalid UTF-8 needs no extra gate here (unlike uknRequireActionCsrf()): every admin
        // field is read through uknAdminText() / uknPostId() / uknAdminEnum(), which reject it.
        return (int) getCurrentUser()['id'];
    }
}
if (!function_exists('uknAdminText')) {
    /**
     * A single-line text field: string only (arrays rejected), valid UTF-8, no control
     * characters, whitespace collapsed, 1..$max characters (0 allowed when !$required).
     * Returns the clean value, or null when invalid.
     */
    function uknAdminText(string $key, int $max, bool $required = true): ?string
    {
        $raw = uknPostString($key);
        if ($raw === null) {
            return $required ? null : '';
        }
        if (!mb_check_encoding($raw, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $raw)) {
            return null;
        }
        $value = trim((string) preg_replace('/\s+/u', ' ', $raw));
        $length = mb_strlen($value, 'UTF-8');
        if (($required && $length === 0) || $length > $max) {
            return null;
        }
        return $value;
    }
}
if (!function_exists('uknAdminEnum')) {
    /** A POST value that must be one of $allowed (strict string match), else null. */
    function uknAdminEnum(string $key, array $allowed): ?string
    {
        $value = uknPostString($key);
        return $value !== null && in_array($value, $allowed, true) ? $value : null;
    }
}
if (!function_exists('uknIsDuplicateKeyError')) {
    function uknIsDuplicateKeyError(Throwable $e): bool
    {
        return $e instanceof PDOException && ($e->errorInfo[1] ?? null) === 1062;
    }
}
