<?php
/**
 * Point transaction row — reusable points-history line item.
 * Used by: Points (transaction history list), Profile summaries.
 *
 * Usage:
 *   require_once __DIR__ . '/../components/point-transaction.php';
 *   foreach ($transactions as $tx) { ukn_point_transaction($tx); }
 *
 * $transaction shape:
 *   [
 *     'amount'  => 10,                       // signed int; negative renders in danger color
 *     'type'    => 'Learning Points',
 *     'reason'  => 'Completed Python Session',
 *     'session' => null,                     // optional related-session label, e.g. 'S-1039'
 *     'date'    => 'Sep 12',
 *     'icon'    => 'event_available',
 *   ]
 *
 * Display only — points are never calculated or persisted here.
 */
if (!function_exists('ukn_point_transaction')) {
    function ukn_point_transaction(array $transaction): void
    {
        $transaction += ['session' => null, 'icon' => 'military_tech'];
        $amount = (int) $transaction['amount'];
        $isNegative = $amount < 0;
        $sign = $isNegative ? '' : '+';
        ?>
        <div class="ukn-transaction-row">
          <span class="ms <?= $isNegative ? 'ukn-text-danger' : 'ukn-text-accent' ?>" aria-hidden="true"><?= htmlspecialchars($transaction['icon']) ?></span>
          <div class="flex-fill ukn-min-w-0">
            <div class="ukn-nav-text ukn-truncate"><?= htmlspecialchars($transaction['reason']) ?></div>
            <div class="ukn-body-sm ukn-truncate">
              <?= htmlspecialchars($transaction['type']) ?><?php if ($transaction['session']): ?> · <?= htmlspecialchars($transaction['session']) ?><?php endif; ?> · <?= htmlspecialchars($transaction['date']) ?>
            </div>
          </div>
          <span class="ukn-transaction-row__amount <?= $isNegative ? 'ukn-text-danger' : 'ukn-text-accent' ?>"><?= $sign . $amount ?></span>
        </div>
        <?php
    }
}
