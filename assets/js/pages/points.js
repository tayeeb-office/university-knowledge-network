/**
 * Points / Mentor Points — page-specific frontend behavior only. The
 * transaction rows themselves reuse components/point-transaction.php
 * as-is; this file only filters which of those pre-rendered rows are
 * visible. No chart here — the approved design's Points screen doesn't
 * include one, so none was added (assets/js/pages/dashboard.js already
 * owns the one chart pattern this project uses, for the dashboards that
 * do have one).
 *
 * Frontend-only client-side filtering — no backend, no real pagination.
 */
(function () {
  'use strict';

  var filterSelect = document.getElementById('pointsTransactionFilter');
  var list = document.querySelector('[data-points-list]');
  if (!filterSelect || !list) {
    return;
  }

  var items = Array.prototype.slice.call(list.querySelectorAll('[data-points-item]'));
  var emptyState = document.querySelector('[data-points-empty]');

  var applyFilter = function () {
    var category = filterSelect.value;
    var visibleCount = 0;

    items.forEach(function (item) {
      var matches = category === 'all' || item.getAttribute('data-points-category') === category;
      item.hidden = !matches;
      if (matches) {
        visibleCount++;
      }
    });

    list.hidden = visibleCount === 0;
    if (emptyState) {
      emptyState.hidden = visibleCount !== 0;
    }
  };

  filterSelect.addEventListener('change', applyFilter);
})();
