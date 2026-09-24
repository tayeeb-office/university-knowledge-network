<?php
require_once __DIR__ . '/../backend/helpers/search.php';
$currentUser = $currentUser ?? [
    'loggedIn'   => false,
    'role'       => 'visitor',
    'dualRole'   => false,
    'activeRole' => 'visitor',
    'name'       => '',
    'initials'   => '',
    'meta'       => '',
];
// index.php always computes real $notifications (from the `notifications` table) before
// including this file, so this fallback only matters if header.php is ever included without
// that upstream data already in scope — same pattern includes/notification-dropdown.php uses.
$notifications = $notifications ?? [];
$notificationCount = $notificationCount ?? count(array_filter($notifications, static fn (array $n): bool => !empty($n['unread'])));
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
  <a href="index.php?page=home" class="ukn-header__brand">
    <span class="ukn-header__title">
      <strong>University</strong>
      <span>Knowledge Network</span>
    </span>
  </a>
  <form class="ukn-header__search ukn-search ukn-search--header" role="search" action="index.php" method="get">
    <input type="hidden" name="page" value="search">
    <span class="ms" aria-hidden="true">search</span>
    <label for="uknGlobalSearch" class="ukn-visually-hidden">Search skills, mentors, discussions</label>
    <input
      type="search"
      id="uknGlobalSearch"
      name="q"
      class="form-control"
      placeholder="Search skills, mentors, discussions"
      maxlength="<?= UKN_SEARCH_MAX_LENGTH ?>"
      value="<?= ($page ?? '') === 'search' ? htmlspecialchars(uknSearchQueryFromRequest()) : '' ?>"
    >
  </form>
  <div class="ukn-header__actions">
    <?php if (!$currentUser['loggedIn']): ?>
      <a href="index.php?page=login" class="btn btn-outline-secondary btn-sm">Log in</a>
      <a href="index.php?page=register" class="btn btn-primary btn-sm">Register</a>
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
          id="uknNotifToggle"
          class="btn-icon position-relative"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-controls="uknNotifDropdown"
          aria-label="Notifications"
        >
          <span class="ms" aria-hidden="true">notifications</span>
          <?php if ($notificationCount > 0): ?>
            <span class="ukn-count-pill ukn-count-pill--corner" data-notification-badge><?= (int) $notificationCount ?></span>
          <?php endif; ?>
        </button>
        <div id="uknNotifDropdown" class="dropdown-menu dropdown-menu-end ukn-dropdown-panel p-0" aria-labelledby="uknNotifToggle">
          <?php include __DIR__ . '/notification-dropdown.php'; ?>
        </div>
      </div>
      <div class="dropdown">
        <button
          type="button"
          id="uknProfileToggle"
          class="ukn-profile-trigger"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-controls="uknProfileDropdown"
          aria-label="Account menu"
        >
          <span class="ukn-avatar ukn-avatar-sm" aria-hidden="true"><?= htmlspecialchars($currentUser['initials']) ?></span>
          <span class="ms" aria-hidden="true">expand_more</span>
        </button>
        <div id="uknProfileDropdown" class="dropdown-menu dropdown-menu-end ukn-dropdown-panel p-0" aria-labelledby="uknProfileToggle">
          <?php include __DIR__ . '/profile-dropdown.php'; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</header>
<div class="ukn-shell<?= $showRightSidebar ? '' : ' ukn-shell--no-right-sidebar' ?>">
  <?php include __DIR__ . '/left-sidebar.php'; ?>
  <main class="ukn-main">