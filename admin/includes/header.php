<?php
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
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/typography.css">
  <link rel="stylesheet" href="../assets/css/layout.css">
  <link rel="stylesheet" href="../assets/css/components.css">
  <link rel="stylesheet" href="../assets/css/forms.css">
  <link rel="stylesheet" href="../assets/css/grunge.css">
  <link rel="stylesheet" href="../assets/css/utilities.css">
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
      <?php  ?>
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
    <main class="ukn-main">
      <div class="ukn-page-header">
        <div>
          <h1><?= htmlspecialchars($adminPageTitle) ?></h1>
          <?php if ($adminPageSub): ?>
            <p class="ukn-page-header__sub"><?= htmlspecialchars($adminPageSub) ?></p>
          <?php endif; ?>
        </div>
      </div>