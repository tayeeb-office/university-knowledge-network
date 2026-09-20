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