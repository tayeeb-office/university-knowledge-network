<?php
if (!function_exists('ukn_success_state')) {
    function ukn_success_state(array $state): void
    {
        $state += ['detail' => '', 'action' => null];
        ?>
        <div class="ukn-state-inline is-success" role="status">
          <span class="ms" aria-hidden="true">check_circle</span>
          <div>
            <div class="ukn-h4 mb-1"><?= htmlspecialchars($state['message']) ?></div>
            <?php if ($state['detail']): ?>
              <p class="ukn-body-sm mb-2"><?= htmlspecialchars($state['detail']) ?></p>
            <?php endif; ?>
            <?php if ($state['action']): ?>
              <a href="<?= htmlspecialchars($state['action']['href'] ?? '#') ?>" class="btn btn-outline-secondary btn-sm"><?= htmlspecialchars($state['action']['label']) ?></a>
            <?php endif; ?>
          </div>
        </div>
        <?php
    }
}