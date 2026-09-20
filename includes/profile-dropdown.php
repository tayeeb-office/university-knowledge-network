<?php
$currentUser = $currentUser ?? [
    'loggedIn'   => true,
    'role'       => 'learner',
    'dualRole'   => true,
    'activeRole' => 'learner',
    'name'       => 'Nabila Rahman',
    'initials'   => 'NR',
    'meta'       => 'Learner · Computer Science',
];
$activeRole = $currentUser['dualRole'] ? $currentUser['activeRole'] : $currentUser['role'];
$roleLabel = ucfirst($activeRole);
$otherRole = $activeRole === 'mentor' ? 'Learner' : 'Mentor';
$dashboardHref = $activeRole === 'mentor' ? 'index.php?page=mentor-dashboard' : 'index.php?page=learner-dashboard';
?>
<div class="ukn-dropdown-panel__header">
  <span class="ukn-cluster">
    <span class="ukn-avatar ukn-avatar-lg" aria-hidden="true"><?= htmlspecialchars($currentUser['initials']) ?></span>
    <span>
      <span class="d-block fw-bold"><?= htmlspecialchars($currentUser['name']) ?></span>
      <span class="ukn-body-sm"><?= htmlspecialchars($currentUser['meta']) ?></span>
    </span>
  </span>
</div>
<?php if ($currentUser['dualRole']): ?>
<div class="ukn-dropdown-panel__item ukn-row-between">
  <span class="ukn-eyebrow">Current role</span>
  <span class="ukn-status ukn-status-accent" data-role-label><?= htmlspecialchars($roleLabel) ?></span>
</div>
<?php endif; ?>
<a href="index.php?page=my-profile" class="dropdown-item">
  <span class="ms" aria-hidden="true">person</span>View Profile
</a>
<a
  href="<?= htmlspecialchars($dashboardHref) ?>"
  class="dropdown-item"
  data-role-dashboard-link
  data-dashboard-href-learner="index.php?page=learner-dashboard"
  data-dashboard-href-mentor="index.php?page=mentor-dashboard"
>
  <span class="ms" aria-hidden="true">dashboard</span>Dashboard
</a>
<?php if ($currentUser['dualRole']): ?>
  <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#roleSwitchModal">
    <span class="ms" aria-hidden="true">swap_horiz</span><span data-role-switch-label>Switch to <?= htmlspecialchars($otherRole) ?></span>
  </button>
<?php endif; ?>
<button type="button" id="uknThemeToggle" class="dropdown-item" aria-pressed="false">
  <span class="ms" aria-hidden="true">dark_mode</span><span data-theme-label>Theme: Light</span>
</button>
<a href="index.php?page=settings" class="dropdown-item">
  <span class="ms" aria-hidden="true">settings</span>Settings
</a>
<button type="button" class="dropdown-item">
  <span class="ms" aria-hidden="true">logout</span>Log out
</button>