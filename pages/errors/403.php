<?php
/**
 * 403 Forbidden — main center content only. Routed via
 * index.php?page=403 (see index.php's $routes map) — a preview/prepared
 * UI state only. Real authorization does not exist in this frontend
 * phase: nothing in the app currently redirects a role-mismatched route
 * here automatically, and this file must not become the first piece of
 * one (no $_SESSION role check, no permission middleware — see
 * index.php's own routing comment).
 *
 * "Go to Dashboard" reuses the existing global role state
 * ($currentUser/$activeRole, the same one every other page reads) rather
 * than inventing a second role concept.
 */
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
