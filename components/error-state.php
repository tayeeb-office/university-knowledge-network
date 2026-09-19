<?php
/**
 * Error state — reusable "couldn't load this" inline notice.
 * Used by: any area where a (mock) fetch failed — e.g. "Unable to load
 * mentors."
 *
 * Usage:
 *   require_once __DIR__ . '/../components/error-state.php';
 *   ukn_error_state([
 *     'title'   => 'Unable to load mentors',
 *     'message' => 'The feed did not respond. Your draft posts are safe.',
 *     'action'  => ['label' => 'Retry', 'href' => '#'],   // optional
 *   ]);
 *
 * "Retry" has no real retry logic — it's a visual affordance only.
 */
if (!function_exists('ukn_error_state')) {
    function ukn_error_state(array $state): void
    {
        $state += ['message' => '', 'action' => null];
        ?>
        <div class="ukn-state-inline" role="alert">
          <span class="ms" aria-hidden="true">error</span>
          <div>
            <div class="ukn-h4 mb-1"><?= htmlspecialchars($state['title']) ?></div>
            <?php if ($state['message']): ?>
              <p class="ukn-body-sm mb-2"><?= htmlspecialchars($state['message']) ?></p>
            <?php endif; ?>
            <?php if ($state['action']): ?>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-error-retry><?= htmlspecialchars($state['action']['label']) ?></button>
            <?php endif; ?>
          </div>
        </div>
        <?php
    }
}
