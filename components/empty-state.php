<?php
if (!function_exists('ukn_empty_state')) {
    function ukn_empty_state(array $state): void
    {
        $state += ['icon' => 'inbox', 'message' => '', 'action' => null, 'dashed' => false];
        $class = 'ukn-state' . ($state['dashed'] ? ' ukn-state--dashed' : '');
        ?>
        <div class="<?= $class ?>">
          <span class="ms" aria-hidden="true"><?= htmlspecialchars($state['icon']) ?></span>
          <div class="ukn-state__title"><?= htmlspecialchars($state['title']) ?></div>
          <?php if ($state['message']): ?>
            <p class="ukn-state__text"><?= htmlspecialchars($state['message']) ?></p>
          <?php endif; ?>
          <?php if ($state['action']): ?>
            <a href="<?= htmlspecialchars($state['action']['href'] ?? '#') ?>" class="btn btn-primary btn-sm"<?= !empty($state['action']['attrs']) ? ' ' . $state['action']['attrs'] : '' ?>><?= htmlspecialchars($state['action']['label']) ?></a>
          <?php endif; ?>
        </div>
        <?php
    }
}