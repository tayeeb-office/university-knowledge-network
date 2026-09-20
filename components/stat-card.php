<?php
if (!function_exists('ukn_stat_card')) {
    function ukn_stat_card(array $stat): void
    {
        $stat += ['icon' => null, 'trend' => null, 'helper' => null];
        ?>
        <div class="card ukn-card-marked-top">
          <div class="card-body">
            <div class="d-flex align-items-center gap-2 ukn-eyebrow">
              <?php if ($stat['icon']): ?><span class="ms" aria-hidden="true"><?= htmlspecialchars($stat['icon']) ?></span><?php endif; ?>
              <?= htmlspecialchars($stat['label']) ?>
            </div>
            <div class="ukn-display mt-2 mb-1"><?= htmlspecialchars((string) $stat['value']) ?></div>
            <?php if ($stat['trend']): ?>
              <div class="ukn-body-sm"><?= htmlspecialchars($stat['trend']) ?></div>
            <?php elseif ($stat['helper']): ?>
              <div class="ukn-body-sm"><?= htmlspecialchars($stat['helper']) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php
    }
}