(function () {
  'use strict';
  function updateTabCounts(tablist) {
    var list = document.querySelector('[data-session-list]');
    if (!list) {
      return;
    }
    tablist.querySelectorAll('[data-session-tab]').forEach(function (tab) {
      var status = tab.getAttribute('data-session-tab');
      var countEl = tab.querySelector('[data-session-tab-count]');
      if (!countEl) {
        return;
      }
      var selector = status === 'all' ? '[data-session-id]' : '[data-session-id][data-session-status="' + status + '"]';
      countEl.textContent = String(list.querySelectorAll(selector).length);
    });
  }
  function applyTabFilter(tablist) {
    var activeTab = tablist.querySelector('[data-session-tab].active');
    var filter = activeTab ? activeTab.getAttribute('data-session-tab') : 'all';
    var list = document.querySelector('[data-session-list]');
    var emptyState = document.querySelector('[data-session-empty]');
    if (!list) {
      return;
    }
    var visibleCount = 0;
    list.querySelectorAll('[data-session-id]').forEach(function (card) {
      var matches = filter === 'all' || card.getAttribute('data-session-status') === filter;
      card.hidden = !matches;
      if (matches) {
        visibleCount++;
      }
    });
    if (emptyState) {
      emptyState.hidden = visibleCount !== 0;
    }
    updateTabCounts(tablist);
  }
  document.addEventListener('click', function (event) {
    var tabBtn = event.target.closest('[data-session-tab]');
    if (!tabBtn) {
      return;
    }
    var tablist = tabBtn.closest('[data-session-tablist]');
    if (!tablist) {
      return;
    }
    tablist.querySelectorAll('[data-session-tab]').forEach(function (tab) {
      var isActive = tab === tabBtn;
      tab.classList.toggle('active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    applyTabFilter(tablist);
  });
  document.querySelectorAll('[data-session-tablist]').forEach(function (tablist) {

    applyTabFilter(tablist);
  });
  // Accept / reject / cancel / complete / rate are server actions (backend/sessions/*.php):
  // real forms on the cards and the details page; this file only filters the tabs.
})();