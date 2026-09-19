<?php
/**
 * Admin shell opener — shared by every admin/*.php page, mirroring the
 * exact same include-then-set-variables pattern includes/header.php uses
 * for the user-facing app (see that file's own docblock).
 *
 * A page sets these BEFORE including this file:
 *   $adminActiveNav   = 'dashboard';           // matches one $adminNavItems slug below
 *   $adminPageTitle   = 'Admin Dashboard';
 *   $adminPageSub     = 'Optional supporting line under the H1.';   // optional
 *   $adminPageStyles  = ['../assets/css/admin/dashboard.css'];      // optional, page-specific CSS
 *   $adminPageScripts = ['https://.../chart.js', '../assets/js/admin/dashboard.js']; // optional
 *
 * ADMIN IS DELIBERATELY NOT PART OF THE USER-FACING APP:
 *   - no $_GET['page'] router — each admin/*.php file is requested
 *     directly, and every nav link below points straight at one of the
 *     locked filenames (dashboard.php, users.php, ...), never through a
 *     query-string page dispatcher.
 *   - no Learner/Mentor role state of any kind — "Admin" is not a value
 *     ukn_active_role (or its user-facing role-switch modal) ever holds.
 *     Admin identity here is a single static frontend mock ("Admin User")
 *     with no real authentication, no credentials, no session.
 *
 * Still deliberately REUSED from the user-facing app rather than
 * duplicated: variables.css/theme.css/base.css/typography.css/
 * components.css/forms.css/grunge.css/utilities.css (design tokens +
 * generic components), and — per layout.css's own comment anticipating
 * this — the exact .ukn-shell/.ukn-shell--no-right-sidebar/.ukn-header/
 * .ukn-sidebar-left/.ukn-nav-link/.ukn-main/.ukn-page-header/.ukn-footer
 * shell classes, plus the global theme.js singleton and the existing
 * #uknMobileNav offcanvas + mobile-nav.js close-on-click behavior. Admin
 * does NOT load layout.css's user-shell-specific responsive.css (that
 * file's breakpoints assume the 3-column Learner/Mentor shell); its own
 * mobile collapse lives in assets/css/admin/admin-layout.css instead.
 */
$adminActiveNav = $adminActiveNav ?? '';
$adminPageTitle = $adminPageTitle ?? 'Admin';
$adminPageSub = $adminPageSub ?? '';
$adminPageStyles = $adminPageStyles ?? [];
$adminPageScripts = $adminPageScripts ?? [];

$adminNavItems = [
    ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'slug' => 'dashboard'],
    ['icon' => 'group', 'label' => 'Users', 'href' => 'users.php', 'slug' => 'users'],
    ['icon' => 'apartment', 'label' => 'Departments', 'href' => 'departments.php', 'slug' => 'departments'],
    ['icon' => 'category', 'label' => 'Skill Categories', 'href' => 'skill-categories.php', 'slug' => 'skill-categories'],
    ['icon' => 'workspaces', 'label' => 'Skills', 'href' => 'skills.php', 'slug' => 'skills'],
    ['icon' => 'event', 'label' => 'Sessions', 'href' => 'sessions.php', 'slug' => 'sessions'],
    ['icon' => 'article', 'label' => 'Posts', 'href' => 'posts.php', 'slug' => 'posts'],
    ['icon' => 'chat_bubble', 'label' => 'Comments', 'href' => 'comments.php', 'slug' => 'comments'],
    ['icon' => 'flag', 'label' => 'Reports', 'href' => 'reports.php', 'slug' => 'reports'],
    ['icon' => 'settings', 'label' => 'Settings', 'href' => 'settings.php', 'slug' => 'settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($adminPageTitle) ?> · UKN Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,300,0,0" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- Same global design tokens/components the user-facing app uses -->
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/typography.css">
  <link rel="stylesheet" href="../assets/css/layout.css">
  <link rel="stylesheet" href="../assets/css/components.css">
  <link rel="stylesheet" href="../assets/css/forms.css">
  <link rel="stylesheet" href="../assets/css/grunge.css">
  <link rel="stylesheet" href="../assets/css/utilities.css">

  <!-- Admin-only layout + this page's own styles -->
  <link rel="stylesheet" href="../assets/css/admin/admin-layout.css">
  <?php foreach ($adminPageStyles as $href): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
  <?php endforeach; ?>
</head>
<body>
  <header class="ukn-header ukn-admin-header">
    <button
      type="button"
      class="btn-icon d-lg-none"
      data-bs-toggle="offcanvas"
      data-bs-target="#uknMobileNav"
      aria-controls="uknMobileNav"
      aria-label="Open admin navigation"
    >
      <span class="ms" aria-hidden="true">menu</span>
    </button>

    <a href="dashboard.php" class="ukn-header__brand">
      <?php /*
        d-none d-lg-block: below Bootstrap's lg breakpoint (992px) the admin
        sidebar is an offcanvas, and the compact header identifies the page
        with the "Admin" badge ALONE — no "University", no "Knowledge Network".
        The full brand still renders inside the open offcanvas
        (admin/includes/footer.php), which is untouched.

        This lives in the MARKUP rather than only in
        assets/css/admin/admin-layout.css on purpose. That stylesheet is
        served with no Cache-Control and no version query string, so a
        browser that cached an earlier copy keeps applying the OLD rule and
        goes on showing "University" — which is exactly the bug the manual
        screenshot caught, while a fresh browser (and Playwright) saw the
        fixed version. Bootstrap's utilities come from a versioned CDN URL
        and the PHP page itself is not cached, so this takes effect
        immediately for everyone, stale stylesheet or not.

        .ukn-header__brand is display:flex, so the title is already a
        blockified flex item — d-lg-block is visually identical to the
        current desktop rendering.
      */ ?>
      <span class="ukn-header__title d-none d-lg-block">
        <strong>University</strong>
        <span>Knowledge Network</span>
      </span>
      <span class="ukn-admin-badge">Admin</span>
    </a>

    <div class="ukn-admin-header-spacer"></div>

    <div class="ukn-header__actions">
      <a href="../index.php?page=home" class="btn btn-outline-secondary btn-sm d-none d-sm-inline-flex">View Application</a>
      <button type="button" id="uknThemeToggle" class="btn-icon" aria-pressed="false">
        <span class="ms" aria-hidden="true">dark_mode</span>
        <span class="ukn-visually-hidden" data-theme-label>Theme: Light</span>
      </button>
      <div class="ukn-admin-identity">
        <span class="ukn-avatar" aria-hidden="true">AU</span>
        <span class="ukn-admin-identity__text d-none d-md-block">
          <span class="d-block fw-bold">Admin User</span>
          <span class="d-block ukn-body-sm ukn-text-muted">Administrator</span>
        </span>
      </div>
    </div>
  </header>

  <div class="ukn-shell ukn-shell--no-right-sidebar">
    <nav class="ukn-sidebar-left" aria-label="Admin navigation">
      <div class="ukn-nav-group">
        <?php foreach ($adminNavItems as $item): $isActive = $item['slug'] === $adminActiveNav; ?>
          <a
            href="<?= htmlspecialchars($item['href']) ?>"
            class="ukn-nav-link<?= $isActive ? ' is-active' : '' ?>"
            <?= $isActive ? 'aria-current="page"' : '' ?>
          >
            <span class="ms" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
            <span class="ukn-nav-text"><?= htmlspecialchars($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>

    <main class="ukn-main"><!-- closed in admin/includes/footer.php -->
      <div class="ukn-page-header">
        <div>
          <h1><?= htmlspecialchars($adminPageTitle) ?></h1>
          <?php if ($adminPageSub): ?>
            <p class="ukn-page-header__sub"><?= htmlspecialchars($adminPageSub) ?></p>
          <?php endif; ?>
        </div>
      </div>
