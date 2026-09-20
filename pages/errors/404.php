<?php
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