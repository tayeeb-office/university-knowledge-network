(function () {
  'use strict';
  var grid = document.querySelector('[data-mentor-grid]');
  if (!grid) {
    return;
  }
  var searchInput = document.getElementById('mentorSearchInput');
  var skillFilter = document.getElementById('mentorSkillFilter');
  var departmentFilter = document.getElementById('mentorDepartmentFilter');
  var ratingFilter = document.getElementById('mentorRatingFilter');
  var availabilityFilter = document.getElementById('mentorAvailabilityFilter');
  var filtersBar = document.querySelector('[data-mentor-filters]');
  var countEl = document.querySelector('[data-mentor-count]');
  var emptyState = document.querySelector('[data-mentor-empty]');
  var items = Array.prototype.slice.call(grid.querySelectorAll('[data-mentor-item]'));

  var applyFilters = function () {
    var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
    var skill = skillFilter ? skillFilter.value : '';
    var department = departmentFilter ? departmentFilter.value : '';
    var minRating = ratingFilter && ratingFilter.value ? parseFloat(ratingFilter.value) : 0;
    var availability = availabilityFilter ? availabilityFilter.value : '';
    var visibleCount = 0;
    items.forEach(function (item) {
      var matchesQuery = !query || item.getAttribute('data-mentor-search').indexOf(query) !== -1;
      var matchesSkill = !skill || item.getAttribute('data-mentor-skills').split('|').indexOf(skill) !== -1;
      var matchesDepartment = !department || item.getAttribute('data-mentor-department') === department;
      var matchesRating = (parseFloat(item.getAttribute('data-mentor-rating')) || 0) >= minRating;
      var matchesAvailability = !availability || item.getAttribute('data-mentor-availability') === availability;
      var matches = matchesQuery && matchesSkill && matchesDepartment && matchesRating && matchesAvailability;
      item.hidden = !matches;
      if (matches) {
        visibleCount++;
      }
    });

    grid.hidden = visibleCount === 0;
    if (emptyState) {
      emptyState.hidden = visibleCount !== 0;
    }
    if (countEl) {
      countEl.textContent = visibleCount + (visibleCount === 1 ? ' mentor found' : ' mentors found');
    }
  };

  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
  }
  if (filtersBar) {
    filtersBar.addEventListener('change', function (event) {
      if (event.target.matches('select')) {
        applyFilters();
      }
    });
  }
  document.addEventListener('click', function (event) {
    if (event.target.closest('[data-mentor-apply]')) {
      applyFilters();
      return;
    }
    var clearBtn = event.target.closest('[data-mentor-clear-filters]');
    if (!clearBtn) {
      return;
    }
    event.preventDefault();
    if (searchInput) {
      searchInput.value = '';
    }
    [skillFilter, departmentFilter, ratingFilter, availabilityFilter].forEach(function (select) {
      if (select) {
        select.selectedIndex = 0;
      }
    });
    applyFilters();
  });
})();