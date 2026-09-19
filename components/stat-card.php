<?php
/**
 * Stat card — compact single-metric card for dashboards.
 * Used by: Learner Dashboard, Mentor Dashboard, Points, Sessions,
 * Profile summaries — anywhere a labeled number needs a card.
 *
 * Usage:
 *   require_once __DIR__ . '/../components/stat-card.php';
 *   ukn_stat_card(['label' => 'Learning Points', 'value' => '412', 'icon' => 'military_tech', 'trend' => '+64 this month']);
 *
 * $stat shape:
 *   [
 *     'label' => 'Learning Points',
 *     'value' => '412',
 *     'icon'  => 'military_tech',   // optional Material Symbol name
 *     'trend' => '+64 this month',  // optional small helper/trend line
 *     'helper'=> null,              // optional secondary helper text (alternative to trend)
 *   ]
 */
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
