<?php
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/helpers/format.php';

// TODO(auth): replace with the real session user id; mirrors this file's own hardcoded
// demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

$routes = [
    'home'              => ['file' => 'pages/home.php', 'title' => 'Home'],
    'login'             => ['file' => 'pages/auth/login.php', 'title' => 'Log In'],
    'register'          => ['file' => 'pages/auth/register.php', 'title' => 'Register'],
    'learner-dashboard' => ['file' => 'pages/dashboard/learner-dashboard.php', 'title' => 'Learner Dashboard'],
    'mentor-dashboard'  => ['file' => 'pages/dashboard/mentor-dashboard.php', 'title' => 'Mentor Dashboard'],
    'my-profile'        => ['file' => 'pages/profile/my-profile.php', 'title' => 'My Profile'],
    'edit-profile'      => ['file' => 'pages/profile/edit-profile.php', 'title' => 'Edit Profile'],
    'learner-profile'   => ['file' => 'pages/profile/learner-profile.php', 'title' => 'Learner Profile'],
    'mentor-profile'    => ['file' => 'pages/profile/mentor-profile.php', 'title' => 'Mentor Profile'],
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
    'skill-network'     => ['file' => 'pages/network/skill-network.php', 'title' => 'Skill Network'],
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
];

$requestedPage = (isset($_GET['page']) && is_string($_GET['page'])) ? $_GET['page'] : 'home';
$page = array_key_exists($requestedPage, $routes) ? $requestedPage : '404';
$route = $routes[$page];
if ($page === '404') {
    http_response_code(404);
}
$currentUser = [
    'loggedIn'   => true,
    'role'       => 'learner',
    'dualRole'   => true,
    'activeRole' => 'learner',
    'name'       => 'Nabila Rahman',
    'initials'   => 'NR',
    'meta'       => 'Learner · Computer Science',
];
$authOnlyPages = ['login', 'register'];
if (in_array($page, $authOnlyPages, true)) {
    $currentUser = [
        'loggedIn'   => false,
        'role'       => 'visitor',
        'dualRole'   => false,
        'activeRole' => 'visitor',
        'name'       => '',
        'initials'   => '',
        'meta'       => '',
    ];
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
            "SELECT icon, message, type, is_read, link_url, created_at
             FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
        );
        $notifStmt->execute([UKN_DEMO_USER_ID]);
        $notifications = array_map(static function (array $row) use ($kindLabels): array {
            return [
                'icon' => $row['icon'],
                'text' => $row['message'],
                'time' => ukn_time_ago($row['created_at']),
                'kind' => $kindLabels[$row['type']] ?? ucfirst($row['type']),
                'unread' => !$row['is_read'],
                'href' => $row['link_url'],
            ];
        }, $notifStmt->fetchAll());

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $countStmt->execute([UKN_DEMO_USER_ID]);
        $notificationCount = (int) $countStmt->fetchColumn();

        $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM mentoring_sessions WHERE mentor_id = ? AND status = 'pending'");
        $pendingStmt->execute([UKN_DEMO_USER_ID]);
        $pendingRequestCount = (int) $pendingStmt->fetchColumn();

        // Left-sidebar/mobile-nav "Sessions" badge (learner nav item only, see
        // includes/left-sidebar.php) = the learner's own upcoming accepted future sessions —
        // the same metric already used for the Learner Dashboard's "Upcoming Sessions" stat.
        $upcomingStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM mentoring_sessions
             WHERE learner_id = ? AND status = 'accepted' AND scheduled_date >= CURDATE()"
        );
        $upcomingStmt->execute([UKN_DEMO_USER_ID]);
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
  <link rel="stylesheet" href="assets/css/pages/network.css">
  <link rel="stylesheet" href="assets/css/pages/errors.css">
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>

  <?php include __DIR__ . '/' . $route['file']; ?>
  <?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/cytoscape@3.30.2/dist/cytoscape.min.js"></script>
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
  <script src="assets/js/components/voting.js"></script>
  <script src="assets/js/components/save-post.js"></script>
  <script src="assets/js/components/follow.js"></script>
  <script src="assets/js/components/comments.js"></script>
  <script src="assets/js/components/notifications.js"></script>
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
  <script src="assets/js/pages/network.js"></script>
</body>
</html>