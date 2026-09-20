<?php
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