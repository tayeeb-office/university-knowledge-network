<?php
// Shared plumbing for form-POST write endpoints (backend/<feature>/<action>.php): method check,
// CSRF check, flash message and redirect back to the page the form was on.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validation.php';

if (!function_exists('uknRequirePostMethod')) {
    function uknRequirePostMethod(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            return;
        }
        if (!headers_sent()) {
            http_response_code(405);
            header('Allow: POST');
            header('Content-Type: text/plain; charset=utf-8');
        }
        exit("Method Not Allowed. This endpoint accepts POST only.\n");
    }
}
if (!function_exists('uknFlashToast')) {
    /** Queues a toast shown on the next page (index.php renders it; toast.js displays it). */
    function uknFlashToast(string $type, string $message): void
    {
        $_SESSION['flash_toast'] = ['type' => $type === 'danger' ? 'danger' : 'success', 'message' => $message];
    }
}
if (!function_exists('uknRedirectBack')) {
    /** Back to the posted return_to path if it is a same-site app path, else to $fallbackRoute. */
    function uknRedirectBack(string $fallbackRoute): void
    {
        $target = uknPostString('return_to');
        if (uknIsSafeRedirectPath($target) && !headers_sent()) {
            header('Location: ' . $target, true, 302);
            exit;
        }
        uknRedirectToRoute($fallbackRoute);
    }
}
if (!function_exists('uknFailAction')) {
    function uknFailAction(string $message, string $fallbackRoute): void
    {
        uknFlashToast('danger', $message);
        uknRedirectBack($fallbackRoute);
    }
}
if (!function_exists('uknRequireActionCsrf')) {
    /**
     * Invalid or missing token: nothing is changed, the user is sent back with a message.
     * Step 53: the same gate rejects any POST carrying invalid UTF-8, before an endpoint reads
     * its input, so malformed bytes can never be stored in a converted form.
     */
    function uknRequireActionCsrf(string $fallbackRoute): void
    {
        if (!verifyCsrf(uknPostString('csrf_token'))) {
            uknFailAction('Your form expired. Please try again.', $fallbackRoute);
        }
        if (uknInputHasInvalidUtf8($_POST)) {
            uknFailAction('Your input contains invalid characters. Please check it and try again.', $fallbackRoute);
        }
    }
}
