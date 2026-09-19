<?php
/**
 * 404 Not Found — main center content only. Reached two ways, both via
 * index.php's normal $routes whitelist (never a dynamic include): the
 * explicit preview route index.php?page=404, and automatically whenever
 * index.php's router falls back here for a $_GET['page'] value that
 * isn't in $routes (see index.php's own routing comment). Either way
 * index.php already set http_response_code(404) before this file loads.
 *
 * The header and left sidebar still render around this (docs/ui keeps
 * the full application shell for its error-state screens); this route is
 * absent from index.php's $sidebarContextByPage map, so there's no right
 * sidebar and no normal left-sidebar item is marked active (no nav item's
 * route matches '404').
 */
?>
<div class="ukn-error-page">
  <p class="ukn-error-page__code">404</p>
  <hr class="ukn-error-page__divider">
  <h1 class="ukn-error-page__heading">Page Not Found</h1>
  <p class="ukn-error-page__message">The page you're looking for doesn't exist or may have been moved.</p>
  <div class="ukn-error-page__actions">
    <a href="<?= htmlspecialchars(ukn_route_href('home')) ?>" class="btn btn-primary btn-sm">Go Home</a>
  </div>
</div>
