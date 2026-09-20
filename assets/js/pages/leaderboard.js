(function () {
  'use strict';
  var viewTabs = document.querySelector('[data-leaderboard-views]');
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
    });
  }
  var periodTabs = document.querySelector('[data-leaderboard-periods]');
  if (periodTabs) {
    periodTabs.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-leaderboard-period]');
      if (!btn) {
        return;
      }
      periodTabs.querySelectorAll('[data-leaderboard-period]').forEach(function (pill) {
        var isActive = pill === btn;
        pill.classList.toggle('is-active', isActive);
        pill.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    });
  }
})();