<?php
$currentUser = $currentUser ?? [
    'loggedIn'   => false,
    'role'       => 'visitor',
    'dualRole'   => false,
    'activeRole' => 'visitor',
    'name'       => '',
    'initials'   => '',
    'meta'       => '',
];
$activeNav = $activeNav ?? 'home';
$navRole = $currentUser['loggedIn']
    ? ($currentUser['dualRole'] ? $currentUser['activeRole'] : $currentUser['role'])
    : 'visitor';
$otherRole = $currentUser['activeRole'] === 'mentor' ? 'Learner' : 'Mentor';
$isDualRoleUser = $currentUser['loggedIn'] && $currentUser['dualRole'];
$rolesToRender = $isDualRoleUser ? ['learner', 'mentor'] : [$navRole];
// Real counts computed once in index.php (same values includes/left-sidebar.php uses),
// so desktop and mobile navigation always show identical badges without re-querying.
$badgeCounts = [
    'learnerRequests' => $pendingRequestCount ?? 0,
    'notifications' => $notificationCount ?? 0,
    'sessions' => $upcomingSessionCount ?? 0,
];
?>
<div class="offcanvas offcanvas-start ukn-mobile-nav" tabindex="-1" id="uknMobileNav" aria-labelledby="uknMobileNavLabel">
  <div class="offcanvas-header">
    <?php if ($currentUser['loggedIn']): ?>
      <div class="ukn-cluster">
        <span class="ukn-avatar" aria-hidden="true"><?= htmlspecialchars($currentUser['initials']) ?></span>
        <span class="ukn-mobile-nav__user-text">
          <span class="d-block fw-bold" id="uknMobileNavLabel"><?= htmlspecialchars($currentUser['name']) ?></span>
          <span class="ukn-body-sm"><?= htmlspecialchars($currentUser['meta']) ?></span>
        </span>
      </div>
    <?php else: ?>
      <h2 class="ukn-h4 mb-0" id="uknMobileNavLabel">Menu</h2>
    <?php endif; ?>
    <button type="button" class="btn-icon" data-bs-dismiss="offcanvas" aria-label="Close navigation">
      <span class="ms" aria-hidden="true">close</span>
    </button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <?php foreach ($rolesToRender as $roleKey): ?>
      <div<?= $isDualRoleUser ? ' data-role="' . htmlspecialchars($roleKey) . '"' . ($roleKey === $navRole ? '' : ' hidden') : '' ?>>
        <?php foreach (ukn_nav_groups_for_role($roleKey, $badgeCounts) as $group): ?>
          <div class="ukn-nav-group">
            <span class="ukn-eyebrow ukn-nav-group__title"><?= htmlspecialchars($group['title']) ?></span>
            <?php foreach ($group['items'] as $item):
              $isActive = $item['route'] === $activeNav;
            ?>
              <a
                href="<?= htmlspecialchars(ukn_route_href($item['route'])) ?>"
                class="ukn-nav-link<?= $isActive ? ' is-active' : '' ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
              >
                <span class="ms" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
                <span class="ukn-nav-text"><?= htmlspecialchars($item['label']) ?></span>
                <?php if (!empty($item['count'])): ?>
                  <span class="ukn-count-pill"<?= $item['route'] === 'notifications' ? ' data-notification-badge' : '' ?>><?= (int) $item['count'] ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <?php if ($currentUser['loggedIn']): ?>
      <div class="mt-auto">
        <?php if ($isDualRoleUser): ?>
          <button
            type="button"
            class="ukn-nav-link ukn-nav-link--footer"
            data-bs-toggle="modal"
            data-bs-target="#roleSwitchModal"
          >
            <span class="ms" aria-hidden="true">swap_horiz</span>
            <span class="ukn-nav-text" data-role-switch-label>Switch to <?= htmlspecialchars($otherRole) ?></span>
          </button>
        <?php elseif (($currentUser['mentorApplication'] ?? null) === 'pending'): ?>
          <a href="index.php?page=mentor-application" class="ukn-nav-link ukn-nav-link--footer">
            <span class="ms" aria-hidden="true">hourglass_top</span>
            <span class="ukn-nav-text">Mentor Application Pending</span>
          </a>
        <?php else: ?>
          <a href="index.php?page=mentor-application" class="ukn-nav-link ukn-nav-link--footer">
            <span class="ms" aria-hidden="true">volunteer_activism</span>
            <span class="ukn-nav-text">Apply to Become a Mentor</span>
          </a>
        <?php endif; ?>
        <?php if (!empty($currentUser['isAdmin'])): ?>
          <a href="admin/dashboard.php" class="ukn-nav-link ukn-nav-link--footer">
            <span class="ms" aria-hidden="true">admin_panel_settings</span>
            <span class="ukn-nav-text">Admin Panel</span>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>