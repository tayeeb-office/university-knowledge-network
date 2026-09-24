<?php
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/auth.php';

// Changes the session's active role (learner|mentor). The requested role is only a request:
// the logged-in user's capability is re-read from the database before anything changes.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit("Method Not Allowed. This endpoint accepts POST only.\n");
}
requireLogin();
if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    // Stale form: nothing changes; back to the dashboard of the current role.
    uknRedirectToRoute(uknDashboardRoute());
}

$requestedRole = $_POST['role'] ?? null;
$user = getCurrentUser();
if (!is_string($requestedRole) || !uknUserCanActAs($user, $requestedRole)) {
    uknRedirectToRoute('403');
}

// No new privileges are granted here (the user can already act as either role), so the
// session id is kept; it was regenerated at login.
$_SESSION['active_role'] = $requestedRole;
uknRedirectToRoute($requestedRole === 'mentor' ? 'mentor-dashboard' : 'learner-dashboard');
