/**
 * Leaderboard — page-specific frontend behavior only.
 *   1. Ranking view tabs (Top Mentors / Top Learners / Community
 *      Contributors) — all three views are already rendered by
 *      pages/leaderboard/leaderboard.php; this just shows/hides the
 *      matching [data-leaderboard-view] section, no re-fetch.
 *   2. Time period pills (This Week / This Month / All Time) — an
 *      active-state toggle only. There is no real historical point
 *      calculation behind this mock leaderboard, so switching periods
 *      does not change any displayed numbers — it would be misleading to
 *      pretend otherwise. Deliberately NOT wired to any data change.
 *
 * Deliberately NOT here: global role switching, profile navigation,
 * theme switching — those already work everywhere via their own
 * document-wide handlers.
 */
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
