<?php
/**
 * 500 Server Error — main center content only. Routed via
 * index.php?page=500 (see index.php's $routes map) — a frontend preview
 * of this error state only. Nothing here throws or catches a real PHP
 * exception, and no debug information (stack traces, file paths, SQL,
 * environment values) is ever shown — just the same generic, safe
 * message a real backend failure would eventually show.
 *
 * "Try Again" is a plain link back to whatever URL was actually
 * requested (falls back to Home if that's somehow unavailable) — a
 * genuine re-request, not a JS-driven retry/API call.
 */
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
