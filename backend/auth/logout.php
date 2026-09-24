<?php
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/auth.php';

// POST + CSRF only, so another site cannot log users out with a link or image.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit("Method Not Allowed. This endpoint accepts POST only.\n");
}
if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    // Stale token: send the user back to where the logout form lives; nothing changes.
    uknRedirectToRoute(isLoggedIn() ? 'home' : 'login');
}

// Ends the server session completely (data, cookie, session file), then starts a fresh,
// empty session only to carry the confirmation message.
destroySession();
startSecureSession();
$_SESSION['flash_success'] = 'You have been logged out.';
uknRedirectToRoute('login');
