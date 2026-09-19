<?php
/**
 * Frontend application entry point / simple template front controller.
 *
 * All internal navigation routes through this one file:
 *
 *   index.php?page=<slug>
 *
 * $routes below is an explicit WHITELIST of slug => page file + title.
 * $_GET['page'] is only ever used to look up a key in that whitelist — its
 * raw value is never concatenated into a path or passed to include()
 * directly, so there is no arbitrary file inclusion / path traversal risk.
 * A missing 'page' falls back to 'home'; an unrecognized one falls back
 * to the whitelisted '404' entry (with a real http_response_code(404))
 * rather than silently loading Home or exposing a filesystem error.
 * '403'/'500' are ordinary whitelisted preview-only entries for their
 * own error-state UI — nothing here performs real authorization or
 * triggers a real server error.
 *
 * Files under pages/ contain ONLY page-specific main content. This file
 * (together with includes/header.php and includes/footer.php) owns the
 * header, role-aware left sidebar, contextual right sidebar, mobile
 * offcanvas and footer — nothing under pages/ repeats any of that shell.
 *
 * Frontend-only: no database, no PHP sessions, no real authentication.
 * The mock $currentUser below matches the shape every includes/*.php file
 * already defaults to independently.
 */

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

/**
 * page slug -> right-sidebar context (see includes/right-sidebar.php's
 * ukn_sidebar_modules_for_context()). Deliberately a separate map, not a
 * 1:1 assumption — several page slugs share one context (all the
 * profile-variant pages use 'profile'; skill-details/learning-skills/
 * teaching-skills use 'skills'), and 'find-mentors' maps to the
 * differently-named 'mentors' context. Any page slug NOT listed here gets
 * no right sidebar at all (Settings, Notifications, auth forms,
 * session-details — which uses its own dedicated Mentor info panel
 * instead, see pages/sessions/session-details.php — brand guide section
 * 11: "Some pages may have no right sidebar").
 */
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

// Whitelist lookup only — $_GET['page']'s raw value never reaches
// include(). is_string() guards against ?page[]=... (an array would
// otherwise trigger a PHP warning / never match array_key_exists sanely).
// An unrecognized page (never a filesystem path, never an include target
// built from user input) falls back to the 404 page, not silently to
// Home — '403'/'404'/'500' are themselves ordinary whitelisted entries
// above, so this never needs to construct a path from $requestedPage.
$requestedPage = (isset($_GET['page']) && is_string($_GET['page'])) ? $_GET['page'] : 'home';
$page = array_key_exists($requestedPage, $routes) ? $requestedPage : '404';
$route = $routes[$page];
if ($page === '404') {
    http_response_code(404);
}

/**
 * Mock frontend state for this preview — a logged-in, dual-role Learner,
 * so role switching and the Learner/Mentor nav split can both be
 * exercised from any route. Replace with real session data later.
 */
$currentUser = [
    'loggedIn'   => true,
    'role'       => 'learner',
    'dualRole'   => true,
    'activeRole' => 'learner',
    'name'       => 'Nabila Rahman',
    'initials'   => 'NR',
    'meta'       => 'Learner · Computer Science',
];
/**
 * Login/Register are the two pages a signed-out Visitor would actually
 * reach, so — unlike every other route, which previews the logged-in
 * mock Learner — these two override $currentUser to the signed-out
 * shape. That flips includes/header.php back to its "Log in / Register"
 * actions and makes includes/left-sidebar.php render the 'visitor' nav
 * group (Home / Skills / Find Mentors / Log in / Register) instead of
 * the Learner/Mentor one, so $activeNav below highlights exactly one of
 * "Log in" / "Register" — never a Learner-only item that wouldn't exist
 * in that nav at all.
 */
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
$notificationCount = 3;
$showRightSidebar = array_key_exists($page, $sidebarContextByPage);
$rightSidebarContext = $sidebarContextByPage[$page] ?? 'home';
$pageTitle = $route['title'] . ' · University Knowledge Network';

/**
 * 'sessions' and 'learner-requests' are two distinct routes that both
 * load pages/sessions/sessions.php — rather than duplicating that file,
 * $route['session_view'] tells it which section to render. Any other
 * route simply has no 'session_view' key, so this stays unset for them.
 */
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

  <!-- Global design foundation — load order matters, see assets/css/variables.css -->
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

  <!-- Page-specific composition — only pages with a finished design load one -->
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
