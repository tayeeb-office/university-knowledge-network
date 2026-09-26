<?php
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/helpers/format.php';
require_once __DIR__ . '/backend/helpers/session.php';
require_once __DIR__ . '/backend/helpers/csrf.php';
require_once __DIR__ . '/backend/helpers/auth.php';
require_once __DIR__ . '/backend/helpers/avatars.php';

$routes = [
    'home'              => ['file' => 'pages/home.php', 'title' => 'Home'],
    'login'             => ['file' => 'pages/auth/login.php', 'title' => 'Log In'],
    'register'          => ['file' => 'pages/auth/register.php', 'title' => 'Register'],
    'verify-email'      => ['file' => 'pages/auth/verify-email.php', 'title' => 'Verify Email'],
    'forgot-password'   => ['file' => 'pages/auth/forgot-password.php', 'title' => 'Forgot Password'],
    'reset-password'    => ['file' => 'pages/auth/reset-password.php', 'title' => 'Reset Password'],
    'learner-dashboard' => ['file' => 'pages/dashboard/learner-dashboard.php', 'title' => 'Learner Dashboard'],
    'mentor-dashboard'  => ['file' => 'pages/dashboard/mentor-dashboard.php', 'title' => 'Mentor Dashboard'],
    'my-profile'        => ['file' => 'pages/profile/my-profile.php', 'title' => 'My Profile'],
    'edit-profile'      => ['file' => 'pages/profile/edit-profile.php', 'title' => 'Edit Profile'],
    'learner-profile'   => ['file' => 'pages/profile/learner-profile.php', 'title' => 'Learner Profile'],
    'mentor-profile'    => ['file' => 'pages/profile/mentor-profile.php', 'title' => 'Mentor Profile'],
    'mentor-application' => ['file' => 'pages/profile/mentor-application.php', 'title' => 'Apply to Become a Mentor'],
    'skills'            => ['file' => 'pages/skills/skills.php', 'title' => 'Skills'],
    'learning-skills'   => ['file' => 'pages/skills/learning-skills.php', 'title' => 'My Learning'],
    'teaching-skills'   => ['file' => 'pages/skills/teaching-skills.php', 'title' => 'Teaching Skills'],
    'skill-details'     => ['file' => 'pages/skills/skill-details.php', 'title' => 'Skill Details'],
    'learning-goals'    => ['file' => 'pages/learning/learning-goals.php', 'title' => 'Learning Goals'],
    'availability'      => ['file' => 'pages/learning/availability.php', 'title' => 'Availability'],
    'find-mentors'      => ['file' => 'pages/mentors/find-mentors.php', 'title' => 'Find Mentors'],
    'recommendations'   => ['file' => 'pages/mentors/recommendations.php', 'title' => 'Recommendations'],
    'sessions'          => ['file' => 'pages/sessions/sessions.php', 'title' => 'Sessions', 'session_view' => 'sessions'],
    'learner-requests'  => ['file' => 'pages/sessions/sessions.php', 'title' => 'Learner Requests', 'session_view' => 'requests'],
    'session-details'   => ['file' => 'pages/sessions/session-details.php', 'title' => 'Session Details'],
    'points'            => ['file' => 'pages/points/points.php', 'title' => 'Points'],
    'ratings'           => ['file' => 'pages/ratings/ratings.php', 'title' => 'Ratings'],
    'post-details'      => ['file' => 'pages/community/post-details.php', 'title' => 'Post Details'],
    'my-posts'          => ['file' => 'pages/community/my-posts.php', 'title' => 'My Posts'],
    'saved-posts'       => ['file' => 'pages/community/saved-posts.php', 'title' => 'Saved Posts'],
    'notifications'     => ['file' => 'pages/notifications/notifications.php', 'title' => 'Notifications'],
    'search'            => ['file' => 'pages/search/search-results.php', 'title' => 'Search Results'],
    'leaderboard'       => ['file' => 'pages/leaderboard/leaderboard.php', 'title' => 'Leaderboard'],
    'settings'          => ['file' => 'pages/settings/settings.php', 'title' => 'Settings'],
    '403'               => ['file' => 'pages/errors/403.php', 'title' => 'Access Restricted'],
    '404'               => ['file' => 'pages/errors/404.php', 'title' => 'Page Not Found'],
    '500'               => ['file' => 'pages/errors/500.php', 'title' => 'Something Went Wrong'],
];
$sidebarContextByPage = [
    'home'              => 'home',
    'learner-dashboard' => 'dashboard-learner',
    'mentor-dashboard'  => 'dashboard-mentor',
    'skills'            => 'skills',
    'learning-skills'   => 'skills',
    'teaching-skills'   => 'skills',
    'skill-details'     => 'skills',
    'find-mentors'      => 'mentors',
    'recommendations'   => 'recommendations',
    'sessions'          => 'sessions',
    'learner-requests'  => 'sessions',
    'points'            => 'points',
    'post-details'      => 'post',
    'leaderboard'       => 'leaderboard',
    'my-profile'        => 'profile',
    'learner-profile'   => 'profile',
    'mentor-profile'    => 'profile',
    'mentor-application' => 'profile',
];
// Who may open each route; anything not listed is public. 'login' = any logged-in user;
// 'learner' / 'mentor' = logged in AND currently acting in that role (the active role,
// not just the capability — a dual-role user switches to reach the other side).
$routeAccess = [
    'learner-dashboard' => 'learner',
    'recommendations'   => 'learner',
    'learning-skills'   => 'learner',
    'learning-goals'    => 'learner',
    'mentor-dashboard'  => 'mentor',
    'learner-requests'  => 'mentor',
    'teaching-skills'   => 'mentor',
    'availability'      => 'mentor',
    'ratings'           => 'mentor',
    'my-profile'        => 'login',
    'edit-profile'      => 'login',
    'sessions'          => 'login',
    'session-details'   => 'login',
    'points'            => 'login',
    'my-posts'          => 'login',
    'saved-posts'       => 'login',
    'notifications'     => 'login',
    'leaderboard'       => 'login',
    'settings'          => 'login',
    'mentor-application' => 'login',
];

$requestedPage = (isset($_GET['page']) && is_string($_GET['page'])) ? $_GET['page'] : 'home';
$page = array_key_exists($requestedPage, $routes) ? $requestedPage : '404';
$route = $routes[$page];
if ($page === '404') {
    http_response_code(404);
}
// Route guards (before any output or page query). Guests are sent to login and brought
// back afterwards; a logged-in user in the wrong active role gets the 403 page in place.
$access = $routeAccess[$page] ?? 'public';
if ($access !== 'public') {
    requireLogin();
    if (($access === 'learner' || $access === 'mentor') && getCurrentActiveRole() !== $access) {
        $page = '403';
        $route = $routes['403'];
    }
}
if ($page === '403') {
    http_response_code(403);
}
// Current user: server session → user id → users row (getCurrentUser()). Guests get the
// visitor shape the shared includes already understand.
$authUser = getCurrentUser();
if ($authUser !== null) {
    $dbRole = $authUser['role'];
    $activeRole = getCurrentActiveRole();
    // Learner-only accounts: status of their latest mentor application (null = never applied),
    // used by the profile dropdown and mobile navigation.
    $mentorApplicationStatus = null;
    if ($dbRole === 'learner') {
        require_once __DIR__ . '/backend/helpers/mentor-applications.php';
        try {
            $latestApplication = uknLatestMentorApplication(getDatabaseConnection(), (int) $authUser['id']);
            $mentorApplicationStatus = $latestApplication['status'] ?? null;
        } catch (Throwable $e) {
            error_log('[UKN mentor-application status] ' . $e->getMessage());
        }
    }
    $currentUser = [
        'loggedIn'   => true,
        'id'         => (int) $authUser['id'],
        'email'      => $authUser['email'],
        'isAdmin'    => !empty($authUser['is_admin']),
        'isLearner'  => uknUserCanActAs($authUser, 'learner'),
        'isMentor'   => uknUserCanActAs($authUser, 'mentor'),
        // Navigation role for accounts without a mode switch (learner-only accounts).
        'role'       => 'learner',
        'dualRole'   => $dbRole === 'dual',
        'mentorApplication' => $mentorApplicationStatus,
        'activeRole' => $activeRole,
        'name'       => $authUser['full_name'],
        'initials'   => $authUser['initials'],
        'avatarPath' => $authUser['avatar_path'] ?? null,
        'department' => (string) ($authUser['department_name'] ?? ''),
        'meta'       => ucfirst($activeRole) . ($authUser['department_name'] ? ' · ' . $authUser['department_name'] : ''),
    ];
} else {
    $currentUser = [
        'loggedIn'   => false,
        'id'         => 0,
        'email'      => '',
        'isAdmin'    => false,
        'isLearner'  => false,
        'isMentor'   => false,
        'role'       => 'visitor',
        'dualRole'   => false,
        'mentorApplication' => null,
        'activeRole' => 'visitor',
        'name'       => '',
        'initials'   => '',
        'avatarPath' => null,
        'department' => '',
        'meta'       => '',
    ];
}
// Id every page's "my …" queries use; 0 for guests (matches no rows).
define('UKN_CURRENT_USER_ID', $currentUser['id']);

if (in_array($page, ['login', 'register', 'forgot-password', 'reset-password'], true)) {
    redirectIfLoggedIn();
}
// Password reset link (?page=reset-password&token=…): only checks whether the token can still
// be used, so the page can show the form or a generic invalid-link message. Opening the link
// never consumes the token (backend/auth/reset-password.php does, on POST).
if ($page === 'reset-password') {
    require_once __DIR__ . '/backend/helpers/password-reset.php';
    header('Cache-Control: no-store');
    $resetTokenValid = false;
    $resetTokenHash = uknHashPasswordResetToken($_GET['token'] ?? null);
    if ($resetTokenHash !== null) {
        try {
            $resetTokenValid = (new User(getDatabaseConnection()))->passwordResetTokenStatus($resetTokenHash) === 'valid';
        } catch (Throwable $e) {
            error_log('[UKN reset-password page] ' . $e->getMessage());
        }
    }
    $resetToken = $resetTokenValid ? (string) $_GET['token'] : '';
    unset($resetTokenHash);
    if (!$resetTokenValid) {
        http_response_code(400);
    }
}
// Email verification link (?page=verify-email&token=…). Handled before any output so the
// response carries a real status code; pages/auth/verify-email.php renders $verificationResult.
if ($page === 'verify-email') {
    require_once __DIR__ . '/backend/helpers/verification.php';
    $verificationResult = 'invalid';
    $tokenHash = uknHashVerificationToken($_GET['token'] ?? null);
    if ($tokenHash !== null) {
        try {
            $verificationResult = (new User(getDatabaseConnection()))->verifyEmailByTokenHash($tokenHash);
        } catch (Throwable $e) {
            error_log('[UKN verify-email] ' . $e->getMessage());
            $verificationResult = 'error';
        }
    }
    $verificationStatusCodes = ['verified' => 200, 'invalid' => 400, 'expired' => 410, 'error' => 500];
    http_response_code($verificationStatusCodes[$verificationResult]);
}
$activeNav = $page;

// Real shared-layout badge data (notification list/count, mentor's pending learner-requests
// count, learner's upcoming-sessions count) computed once here so header.php's bell badge,
// includes/notification-dropdown.php, and includes/left-sidebar.php / includes/mobile-nav.php's
// nav badges all reuse the same values instead of each running the same queries again.
$notifications = [];
$notificationCount = 0;
$pendingRequestCount = 0;
$upcomingSessionCount = 0;
if (!empty($currentUser['loggedIn'])) {
    $kindLabels = ['session' => 'Session', 'community' => 'Community', 'rating' => 'Rating', 'system' => 'System'];
    try {
        $pdo = getDatabaseConnection();

        $notifStmt = $pdo->prepare(
            "SELECT id, icon, message, type, is_read, link_url, created_at
             FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
        );
        $notifStmt->execute([UKN_CURRENT_USER_ID]);
        $notifications = array_map(static function (array $row) use ($kindLabels): array {
            return [
                'id' => (int) $row['id'],
                'icon' => $row['icon'],
                'text' => $row['message'],
                'time' => ukn_time_ago($row['created_at']),
                'kind' => $kindLabels[$row['type']] ?? ucfirst($row['type']),
                'unread' => !$row['is_read'],
                'href' => $row['link_url'],
            ];
        }, $notifStmt->fetchAll());

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $countStmt->execute([UKN_CURRENT_USER_ID]);
        $notificationCount = (int) $countStmt->fetchColumn();

        $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM mentoring_sessions WHERE mentor_id = ? AND status = 'pending'");
        $pendingStmt->execute([UKN_CURRENT_USER_ID]);
        $pendingRequestCount = (int) $pendingStmt->fetchColumn();

        // Left-sidebar/mobile-nav "Sessions" badge (learner nav item only, see
        // includes/left-sidebar.php) = the learner's own upcoming accepted future sessions —
        // the same metric already used for the Learner Dashboard's "Upcoming Sessions" stat.
        $upcomingStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM mentoring_sessions
             WHERE learner_id = ? AND status = 'accepted' AND scheduled_date >= CURDATE()"
        );
        $upcomingStmt->execute([UKN_CURRENT_USER_ID]);
        $upcomingSessionCount = (int) $upcomingStmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[UKN index] ' . $e->getMessage());
        $notifications = [];
        $notificationCount = 0;
        $pendingRequestCount = 0;
        $upcomingSessionCount = 0;
    }
}
$showRightSidebar = array_key_exists($page, $sidebarContextByPage);
$rightSidebarContext = $sidebarContextByPage[$page] ?? 'home';
$pageTitle = $route['title'] . ' · University Knowledge Network';
$sessionView = $route['session_view'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="A university peer-learning and mentoring community — learn a skill, teach a skill, keep the points.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,300,0,0" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/typography.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/forms.css">
  <link rel="stylesheet" href="assets/css/grunge.css">
  <link rel="stylesheet" href="assets/css/utilities.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/pages/home.css">
  <link rel="stylesheet" href="assets/css/pages/auth.css">
  <link rel="stylesheet" href="assets/css/pages/dashboard.css">
  <link rel="stylesheet" href="assets/css/pages/profile.css">
  <link rel="stylesheet" href="assets/css/pages/skills.css">
  <link rel="stylesheet" href="assets/css/pages/learning.css">
  <link rel="stylesheet" href="assets/css/pages/mentors.css">
  <link rel="stylesheet" href="assets/css/pages/sessions.css">
  <link rel="stylesheet" href="assets/css/pages/points.css">
  <link rel="stylesheet" href="assets/css/pages/ratings.css">
  <link rel="stylesheet" href="assets/css/pages/community.css">
  <link rel="stylesheet" href="assets/css/pages/settings.css">
  <link rel="stylesheet" href="assets/css/pages/notifications.css">
  <link rel="stylesheet" href="assets/css/pages/search.css">
  <link rel="stylesheet" href="assets/css/pages/leaderboard.css">
  <link rel="stylesheet" href="assets/css/pages/errors.css">
</head>
<body<?= $currentUser['loggedIn'] ? ' data-active-role="' . htmlspecialchars($currentUser['activeRole']) . '"' : '' ?>>
  <?php include __DIR__ . '/includes/header.php'; ?>

  <?php include __DIR__ . '/' . $route['file']; ?>
  <?php include __DIR__ . '/includes/footer.php'; ?>
  <?php $flashToast = uknTakeFlash('flash_toast'); if (is_array($flashToast)): ?>
    <div hidden data-flash-toast data-flash-type="<?= htmlspecialchars((string) $flashToast['type']) ?>"><?= htmlspecialchars((string) $flashToast['message']) ?></div>
  <?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <script src="assets/js/app.js"></script>
  <script src="assets/js/core/theme.js"></script>
  <script src="assets/js/core/dropdown.js"></script>
  <script src="assets/js/core/header-search.js"></script>
  <script src="assets/js/core/role-switch.js"></script>
  <script src="assets/js/core/validation.js"></script>
  <script src="assets/js/core/toast.js"></script>
  <script src="assets/js/core/modal.js"></script>
  <script src="assets/js/core/sidebar.js"></script>
  <script src="assets/js/core/mobile-nav.js"></script>
  <script src="assets/js/components/comments.js"></script>
  <script src="assets/js/components/notifications.js"></script>
  <script src="assets/js/components/post-card.js"></script>
  <script src="assets/js/pages/home.js"></script>
  <script src="assets/js/pages/auth.js"></script>
  <script src="assets/js/pages/dashboard.js"></script>
  <script src="assets/js/pages/profile.js"></script>
  <script src="assets/js/pages/skills.js"></script>
  <script src="assets/js/pages/learning.js"></script>
  <script src="assets/js/pages/mentors.js"></script>
  <script src="assets/js/pages/sessions.js"></script>
  <script src="assets/js/pages/points.js"></script>
  <script src="assets/js/pages/ratings.js"></script>
  <script src="assets/js/pages/community.js"></script>
  <script src="assets/js/pages/settings.js"></script>
  <script src="assets/js/pages/search.js"></script>
  <script src="assets/js/pages/leaderboard.js"></script>
</body>
</html>