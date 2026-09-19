/**
 * Notifications page — filter tabs only (All / Unread / Sessions /
 * Community). "Mark all as read" is NOT handled here — both this page's
 * button and the header dropdown's button share the same
 * data-action="mark-all-read" convention and are both already wired to
 * the one document-wide window.UKN.markAllNotificationsRead() helper in
 * assets/js/core/dropdown.js, so nothing about marking-as-read is
 * duplicated in this file.
 *
 * Each row is components/notification-item.php's own root element
 * ([data-notification-item], carrying data-notification-category derived
 * from 'kind') — filtering toggles `hidden` directly on that element,
 * never on an outer wrapper div (see assets/js/pages/sessions.js and
 * post-card.php for why that distinction matters: a JS-toggled wrapper
 * around an already-visible inner element desyncs the two).
 */
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

  // Re-apply the current filter after a "Mark all as read" click — if the
  // Unread filter is active, everything just marked read should disappear
  // from view immediately rather than sitting there still visually unread.
  // Also clears the "Unread (N)" count in the filter tab's own label,
  // same zero-state idea as the header badge/dropdown count disappearing.
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
