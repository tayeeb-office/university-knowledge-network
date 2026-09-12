<?php
/**
 * Mobile navigation — Bootstrap 5 Offcanvas, opened by the hamburger
 * button in includes/header.php (data-bs-target="#uknMobileNav").
 *
 * Mirrors the same role-aware navigation as includes/left-sidebar.php
 * (via ukn_nav_groups_for_role() / ukn_route_href(), defined there) so
 * the desktop sidebar and the mobile menu never drift apart. This file is
 * always included after left-sidebar.php within the same request (see
 * includes/header.php and includes/footer.php), so those helpers already
 * exist by the time this runs.
 *
 * There is no permanent right sidebar on mobile — contextual content
 * moves into the page itself where a page needs it (brand guide section 12).
 */
$currentUser = $currentUser ?? [
    'loggedIn'   => true,
    'role'       => 'learner',
    'dualRole'   => true,
    'activeRole' => 'learner',
    'name'       => 'Nabila Rahman',
    'initials'   => 'NR',
    'meta'       => 'Learner · Computer Science',
];
$activeNav = $activeNav ?? 'home';

$navRole = $currentUser['loggedIn']
    ? ($currentUser['dualRole'] ? $currentUser['activeRole'] : $currentUser['role'])
    : 'visitor';
$navGroups = ukn_nav_groups_for_role($navRole);
$otherRole = $currentUser['activeRole'] === 'mentor' ? 'Learner' : 'Mentor';
?>
<div class="offcanvas offcanvas-start ukn-mobile-nav" tabindex="-1" id="uknMobileNav" aria-labelledby="uknMobileNavLabel">
  <div class="offcanvas-header">
    <?php if ($currentUser['loggedIn']): ?>
      <div class="ukn-cluster">
        <span class="ukn-avatar" aria-hidden="true"><?= htmlspecialchars($currentUser['initials']) ?></span>
        <span>
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
    <?php foreach ($navGroups as $group): ?>
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
              <span class="ukn-count-pill"><?= (int) $item['count'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <?php if ($currentUser['loggedIn'] && $currentUser['dualRole']): ?>
      <button
        type="button"
        class="ukn-nav-link ukn-nav-link--footer mt-auto"
        data-bs-toggle="modal"
        data-bs-target="#roleSwitchModal"
      >
        <span class="ms" aria-hidden="true">swap_horiz</span>
        <span class="ukn-nav-text">Switch to <?= htmlspecialchars($otherRole) ?></span>
      </button>
    <?php endif; ?>
  </div>
</div>
