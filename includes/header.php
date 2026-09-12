<?php
/**
 * Global application shell — top header + shell opener.
 *
 * Renders the sticky top header, then opens the 3-column
 * ".ukn-shell" grid, includes the role-aware left sidebar, and opens
 * <main class="ukn-main"> for the page's own content. The shell and
 * <main> are closed by includes/footer.php, which also renders the
 * contextual right sidebar — this keeps the full shell out of every
 * individual page file.
 *
 * Usage (from any pages/**\/*.php file):
 *   <?php
 *   $activeNav = 'find-mentors';   // highlights the matching nav item
 *   $showRightSidebar = true;      // false on Settings/Notifications/etc.
 *   include __DIR__ . '/../../includes/header.php';
 *   ?>
 *   ...page-specific main content...
 *   <?php include __DIR__ . '/../../includes/footer.php'; ?>
 *
 * $currentUser / $notificationCount are mock frontend state — replace with
 * real session data once authentication exists. Leave them unset to get
 * the logged-in learner defaults below, or set $currentUser['loggedIn'] to
 * false to preview the visitor header (Log in / Register only).
 */
$currentUser = $currentUser ?? [
    'loggedIn'   => true,
    'role'       => 'learner',   // 'visitor' | 'learner' | 'mentor'
    'dualRole'   => true,
    'activeRole' => 'learner',
    'name'       => 'Nabila Rahman',
    'initials'   => 'NR',
    'meta'       => 'Learner · Computer Science',
];
$notificationCount = $notificationCount ?? 3;
$showRightSidebar = $showRightSidebar ?? true;
?>
<header class="ukn-header">
  <button
    type="button"
    class="ukn-header__menu-toggle d-lg-none"
    data-bs-toggle="offcanvas"
    data-bs-target="#uknMobileNav"
    aria-controls="uknMobileNav"
    aria-label="Open navigation menu"
  >
    <span class="ms" aria-hidden="true">menu</span>
  </button>

  <a href="index.php" class="ukn-header__brand">
    <img src="assets/images/logo/ukn-logo.png" alt="" class="ukn-header__mark">
    <span class="ukn-header__title">
      <strong>University</strong>
      <span>Knowledge Network</span>
    </span>
  </a>

  <form class="ukn-header__search ukn-search ukn-search--header" role="search" action="pages/search/search-results.php" method="get">
    <span class="ms" aria-hidden="true">search</span>
    <label for="uknGlobalSearch" class="ukn-visually-hidden">Search skills, mentors, discussions</label>
    <input type="search" id="uknGlobalSearch" name="q" class="form-control" placeholder="Search skills, mentors, discussions">
  </form>

  <div class="ukn-header__actions">
    <?php if (!$currentUser['loggedIn']): ?>
      <a href="pages/auth/login.php" class="btn btn-outline-secondary btn-sm">Log in</a>
      <a href="pages/auth/register.php" class="btn btn-primary btn-sm">Register</a>
    <?php else: ?>
      <button
        type="button"
        class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
        data-bs-toggle="modal"
        data-bs-target="#createPostModal"
      >
        <span class="ms" aria-hidden="true">add</span><span class="d-none d-sm-inline">Create</span>
      </button>

      <div class="dropdown">
        <button
          type="button"
          class="btn-icon position-relative"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-label="Notifications"
        >
          <span class="ms" aria-hidden="true">notifications</span>
          <?php if ($notificationCount > 0): ?>
            <span class="ukn-count-pill ukn-count-pill--corner"><?= (int) $notificationCount ?></span>
          <?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end ukn-dropdown-panel p-0">
          <?php include __DIR__ . '/notification-dropdown.php'; ?>
        </div>
      </div>

      <div class="dropdown">
        <button
          type="button"
          class="ukn-profile-trigger"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-label="Account menu"
        >
          <span class="ukn-avatar ukn-avatar-sm" aria-hidden="true"><?= htmlspecialchars($currentUser['initials']) ?></span>
          <span class="ms" aria-hidden="true">expand_more</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end ukn-dropdown-panel p-0">
          <?php include __DIR__ . '/profile-dropdown.php'; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</header>

<div class="ukn-shell<?= $showRightSidebar ? '' : ' ukn-shell--no-right-sidebar' ?>">
  <?php include __DIR__ . '/left-sidebar.php'; ?>

  <main class="ukn-main"><!-- closed in includes/footer.php -->
