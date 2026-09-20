<?php
if (!function_exists('ukn_loading_skeleton')) {
    function ukn_loading_skeleton(string $label = 'Loading…'): void
    {
        ?>
        <div class="card">
          <div class="card-body d-flex flex-column gap-2">
            <div class="ukn-skeleton-line ukn-skeleton-line--lead"></div>
            <div class="ukn-skeleton-line ukn-skeleton-line--title"></div>
            <div class="ukn-skeleton-line ukn-skeleton-line--wide"></div>
            <div class="ukn-skeleton-line ukn-skeleton-line--medium"></div>
            <div class="ukn-loading-row mt-1">
              <span class="ukn-spinner" aria-hidden="true"></span>
              <span><?= htmlspecialchars($label) ?></span>
            </div>
          </div>
        </div>
        <?php
    }
}
if (!function_exists('ukn_loading_spinner')) {
    function ukn_loading_spinner(string $label = 'Loading…'): void
    {
        ?>
        <div class="ukn-loading-row" role="status">
          <span class="ukn-spinner" aria-hidden="true"></span>
          <span><?= htmlspecialchars($label) ?></span>
        </div>
        <?php
    }
}
if (!function_exists('ukn_loading_card_skeleton')) {
    function ukn_loading_card_skeleton(int $count = 3): void
    {
        for ($i = 0; $i < max(1, $count); $i++) {
            ukn_loading_skeleton();
        }
    }
}