<?php
$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$dashboardHref = $activeRole === 'mentor' ? ukn_route_href('mentor-dashboard') : ukn_route_href('learner-dashboard');
?>
<div class="ukn-error-page">
  <p class="ukn-error-page__code">403</p>
  <hr class="ukn-error-page__divider">
  <h1 class="ukn-error-page__heading">Access Restricted</h1>
  <p class="ukn-error-page__message">You don't have permission to view this page. Return to a page available for your current role.</p>
  <div class="ukn-error-page__actions">
    <a href="<?= htmlspecialchars(ukn_route_href('home')) ?>" class="btn btn-primary btn-sm">Go Home</a>
    <a href="<?= htmlspecialchars($dashboardHref) ?>" class="btn btn-outline-secondary btn-sm">Go to Dashboard</a>
  </div>
</div>