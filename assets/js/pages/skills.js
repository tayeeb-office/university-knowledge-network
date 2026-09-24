(function () {
  'use strict';
  var searchInput = document.getElementById('uknSkillsSearch');
  var categoryBar = document.querySelector('[data-skill-categories]');
  var grid = document.querySelector('[data-skill-grid]');
  var emptyState = document.querySelector('[data-skill-empty]');
  if (grid) {
    var items = Array.prototype.slice.call(grid.querySelectorAll('[data-skill-item]'));
    var currentCategory = 'All';
    var currentQuery = '';
    var applyFilters = function () {
      var visibleCount = 0;
      items.forEach(function (item) {
        var matchesCategory = currentCategory === 'All' || item.getAttribute('data-skill-category') === currentCategory;
        var matchesQuery = currentQuery === '' || item.getAttribute('data-skill-search').indexOf(currentQuery) !== -1;
        var matches = matchesCategory && matchesQuery;
        item.hidden = !matches;
        if (matches) {
          visibleCount++;
        }
      });
      grid.hidden = visibleCount === 0;
      if (emptyState) {
        emptyState.hidden = visibleCount !== 0;
      }
    };

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        currentQuery = searchInput.value.trim().toLowerCase();
        applyFilters();
      });
    }
    if (categoryBar) {
      categoryBar.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-skill-category-filter]');
        if (!btn) {
          return;
        }
        currentCategory = btn.getAttribute('data-skill-category-filter');
        categoryBar.querySelectorAll('[data-skill-category-filter]').forEach(function (pill) {
          var isActive = pill === btn;
          pill.classList.toggle('is-active', isActive);
          pill.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        applyFilters();
      });
    }
    document.addEventListener('click', function (event) {
      var clearBtn = event.target.closest('[data-skills-clear-filters]');
      if (!clearBtn) {
        return;
      }
      event.preventDefault();
      currentCategory = 'All';
      currentQuery = '';
      if (searchInput) {
        searchInput.value = '';
      }
      if (categoryBar) {
        categoryBar.querySelectorAll('[data-skill-category-filter]').forEach(function (pill) {
          var isActive = pill.getAttribute('data-skill-category-filter') === 'All';
          pill.classList.toggle('is-active', isActive);
          pill.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
      }
      applyFilters();
    });
  }
})();