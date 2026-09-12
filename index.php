<?php
/**
 * Temporary frontend preview entry point.
 *
 * Assembles the reusable application shell (includes/header.php +
 * includes/footer.php) around a small placeholder main-content block, so
 * the header, role-aware left nav, contextual right sidebar and mobile
 * offcanvas can all be checked in a browser before pages/home.php (the
 * real community feed) exists. This file intentionally holds no shell
 * markup of its own — see includes/header.php for how the shell opens
 * and includes/footer.php for how it closes.
 *
 * $currentUser / $activeNav / $notificationCount / $showRightSidebar are
 * mock frontend state for this preview (a logged-in, dual-role Learner
 * viewing Home) — replace with real session + routing data later.
 * $sidebarModules is intentionally left unset so includes/right-sidebar.php
 * falls back to its realistic Home-context modules (Top Skills, Top
 * Mentors, Top Learners, Trending Discussions).
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
$activeNav = 'home';
$notificationCount = 3;
$showRightSidebar = true;
$pageTitle = 'University Knowledge Network';
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
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="ukn-page-header">
      <div>
        <h1>Application shell preview</h1>
        <p class="ukn-page-header__sub">Temporary placeholder content for checking the header, navigation and sidebars. <code>pages/home.php</code> will replace this with the real community feed.</p>
      </div>
      <span class="ukn-mono">index.php</span>
    </div>

    <div class="card ukn-card-interactive mb-4">
      <div class="card-body">
        <h2 class="ukn-h3">Shell smoke test</h2>
        <p class="ukn-body ukn-prose mb-0">If the header, the role-aware left navigation, this card, and — on desktop — the contextual right sidebar all render with consistent spacing, borders and typography, the reusable application shell is wired up correctly. Resize the window to check the tablet icon-rail and the mobile offcanvas menu.</p>
      </div>
    </div>

    <div class="ukn-grid-3">
      <div class="card ukn-card-marked-top">
        <div class="card-body">
          <span class="ukn-eyebrow">Learning Points</span>
          <div class="ukn-display mt-2 mb-0">412</div>
        </div>
      </div>
      <div class="card ukn-card-marked-top">
        <div class="card-body">
          <span class="ukn-eyebrow">Upcoming Sessions</span>
          <div class="ukn-display mt-2 mb-0">2</div>
        </div>
      </div>
      <div class="card ukn-card-marked-top">
        <div class="card-body">
          <span class="ukn-eyebrow">Skills Learning</span>
          <div class="ukn-display mt-2 mb-0">4</div>
        </div>
      </div>
    </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
  <script src="assets/js/core/sidebar.js"></script>
  <script src="assets/js/core/mobile-nav.js"></script>
</body>
</html>
