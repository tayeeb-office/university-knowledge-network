/**
 * Ratings — page-specific frontend behavior only. Review cards themselves
 * reuse components/rating-item.php as-is (via its data-review-rating/
 * -skill/-date attributes); this file only filters/sorts/counts those
 * pre-rendered cards. Submitting a rating is a completely separate,
 * pre-existing flow (modals/rating-modal.php from a completed session) —
 * nothing here touches that.
 *
 * Frontend-only client-side filtering/sorting — no backend, no real
 * pagination.
 */
(function () {
  'use strict';

  var list = document.querySelector('[data-ratings-list]');
  if (!list) {
    return;
  }

  var filterBar = document.querySelector('[data-rating-filters]');
  var skillSelect = document.getElementById('ratingsSkillFilter');
  var sortSelect = document.getElementById('ratingsSort');
  var countEl = document.querySelector('[data-ratings-count]');
  var emptyState = document.querySelector('[data-ratings-empty]');
  var items = Array.prototype.slice.call(list.querySelectorAll('[data-review-rating]'));

  var applyFilters = function () {
    var activeStarBtn = filterBar ? filterBar.querySelector('[data-rating-filter].is-active') : null;
    var starFilter = activeStarBtn ? activeStarBtn.getAttribute('data-rating-filter') : '0';
    var skillFilter = skillSelect ? skillSelect.value : '';

    var visibleCount = 0;
    items.forEach(function (item) {
      var matchesStar = starFilter === '0' || item.getAttribute('data-review-rating') === starFilter;
      var matchesSkill = !skillFilter || item.getAttribute('data-review-skill') === skillFilter;
      var matches = matchesStar && matchesSkill;
      item.hidden = !matches;
      if (matches) {
        visibleCount++;
      }
    });

    list.hidden = visibleCount === 0;
    if (emptyState) {
      emptyState.hidden = visibleCount !== 0;
    }
    if (countEl) {
      countEl.textContent = visibleCount + (visibleCount === 1 ? ' Review' : ' Reviews');
    }
  };

  var applySort = function () {
    var order = sortSelect ? sortSelect.value : 'newest';
    var sorted = items.slice().sort(function (a, b) {
      if (order === 'highest') {
        return (parseInt(b.getAttribute('data-review-rating'), 10) || 0) - (parseInt(a.getAttribute('data-review-rating'), 10) || 0);
      }
      if (order === 'lowest') {
        return (parseInt(a.getAttribute('data-review-rating'), 10) || 0) - (parseInt(b.getAttribute('data-review-rating'), 10) || 0);
      }
      // 'newest' — ISO dates (YYYY-MM-DD) sort correctly as plain strings
      return (b.getAttribute('data-review-date') || '').localeCompare(a.getAttribute('data-review-date') || '');
    });
    sorted.forEach(function (item) {
      list.appendChild(item);
    });
  };

  if (filterBar) {
    filterBar.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-rating-filter]');
      if (!btn) {
        return;
      }
      filterBar.querySelectorAll('[data-rating-filter]').forEach(function (pill) {
        pill.classList.toggle('is-active', pill === btn);
      });
      applyFilters();
    });
  }

  if (skillSelect) {
    skillSelect.addEventListener('change', applyFilters);
  }
  if (sortSelect) {
    sortSelect.addEventListener('change', applySort);
  }

  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-ratings-clear-filters]')) {
      return;
    }
    event.preventDefault(); // this is a components/empty-state.php link, not a real destination
    if (filterBar) {
      filterBar.querySelectorAll('[data-rating-filter]').forEach(function (pill) {
        pill.classList.toggle('is-active', pill.getAttribute('data-rating-filter') === '0');
      });
    }
    if (skillSelect) {
      skillSelect.selectedIndex = 0;
    }
    applyFilters();
  });

  applySort();
})();
