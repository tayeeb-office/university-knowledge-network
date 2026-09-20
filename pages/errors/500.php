<?php
$retryHref = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ukn_route_href('home');
?>
<div class="ukn-error-page">
  <p class="ukn-error-page__code">500</p>
  <hr class="ukn-error-page__divider">
  <h1 class="ukn-error-page__heading">Something Went Wrong</h1>
  <p class="ukn-error-page__message">We couldn't complete this request right now. Please try again or return to the home page.</p>
  <div class="ukn-error-page__actions">
    <a href="<?= htmlspecialchars($retryHref) ?>" class="btn btn-primary btn-sm">Try Again</a>
    <a href="<?= htmlspecialchars(ukn_route_href('home')) ?>" class="btn btn-outline-secondary btn-sm">Go Home</a>
  </div>
</div>