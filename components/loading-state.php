<?php
/**
 * Loading state — reusable while-content-loads placeholders.
 * Used by: any list/card area before its (mock) data is ready.
 *
 * Three variants, matching the approved design's loading patterns:
 *   ukn_loading_skeleton('Loading mentors…')  — a few skeleton lines + spinner + label
 *   ukn_loading_spinner('Loading…')           — compact inline spinner + label only
 *   ukn_loading_card_skeleton(3)              — N stacked card-shaped skeleton blocks
 *
 * Pure CSS/markup — no real async loading is implemented; a page decides
 * when to show these versus its real (mock) content.
 */
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
    /** Compact inline loading indicator — a single row, no card wrapper. */
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
    /** N stacked card-shaped skeletons — for a list that hasn't loaded yet. */
    function ukn_loading_card_skeleton(int $count = 3): void
    {
        for ($i = 0; $i < max(1, $count); $i++) {
            ukn_loading_skeleton();
        }
    }
}
