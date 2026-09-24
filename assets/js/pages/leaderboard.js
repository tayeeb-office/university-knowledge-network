(function () {
  'use strict';
  var viewTabs = document.querySelector('[data-leaderboard-views]');
  var periodLinks = document.querySelectorAll('[data-leaderboard-period]');
  // Period tabs are server links (?period=week|month|all). Keep the chosen view when the
  // period changes by carrying it in their ?view= parameter.
  function syncPeriodLinks(view) {
    periodLinks.forEach(function (link) {
      try {
        var url = new URL(link.getAttribute('href'), window.location.href);
        url.searchParams.set('view', view);
        link.setAttribute('href', url.pathname.split('/').pop() + url.search);
      } catch (e) {
        // Leave the server-rendered href as is.
      }
    });
  }
  if (viewTabs) {
    viewTabs.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-leaderboard-view-tab]');
      if (!btn) {
        return;
      }
      var target = btn.getAttribute('data-leaderboard-view-tab');
      viewTabs.querySelectorAll('[data-leaderboard-view-tab]').forEach(function (pill) {
        var isActive = pill === btn;
        pill.classList.toggle('is-active', isActive);
        pill.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      document.querySelectorAll('[data-leaderboard-view]').forEach(function (section) {
        section.hidden = section.getAttribute('data-leaderboard-view') !== target;
      });
      syncPeriodLinks(target);
    });
  }
})();
