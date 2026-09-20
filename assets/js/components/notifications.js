(function () {
  'use strict';
  var filterBar = document.querySelector('[data-notification-filters]');
  var list = document.querySelector('[data-notification-list]');
  if (!filterBar || !list) {
    return;
  }
  var items = Array.prototype.slice.call(list.querySelectorAll('[data-notification-item]'));
  var emptyState = document.querySelector('[data-notification-empty]');
  var activeFilter = 'all';
  function matches(item, filter) {
    if (filter === 'all') {
      return true;
    }
    if (filter === 'unread') {
      return item.classList.contains('is-unread');
    }
    return item.getAttribute('data-notification-category') === filter;
  }
  function applyFilter() {
    var visibleCount = 0;
    items.forEach(function (item) {
      var isMatch = matches(item, activeFilter);
      item.hidden = !isMatch;
      if (isMatch) {
        visibleCount++;
      }
    });
    if (emptyState) {
      emptyState.hidden = visibleCount !== 0;
    }
    if (list.parentElement) {
      list.parentElement.hidden = visibleCount === 0;
    }
  }
  filterBar.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-notification-filter]');
    if (!btn) {
      return;
    }
    activeFilter = btn.getAttribute('data-notification-filter');
    filterBar.querySelectorAll('[data-notification-filter]').forEach(function (pill) {
      var isActive = pill === btn;
      pill.classList.toggle('is-active', isActive);
      pill.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    applyFilter();
  });
  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-action="mark-all-read"]')) {
      return;
    }
    var tabCount = document.querySelector('[data-notification-tab-count]');
    if (tabCount) {
      tabCount.textContent = '';
    }
    applyFilter();
  });
})();