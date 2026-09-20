<?php
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