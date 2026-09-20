(function () {
  'use strict';
  var filterBar = document.querySelector('[data-search-filters]');
  var resultsContainer = document.querySelector('[data-search-results]');
  if (!filterBar || !resultsContainer) {
    return;
  }
  var items = Array.prototype.slice.call(resultsContainer.querySelectorAll('[data-search-result]'));
  var summary = document.querySelector('[data-search-summary]');
  var emptyStates = {};
  document.querySelectorAll('[data-search-empty]').forEach(function (el) {
    emptyStates[el.getAttribute('data-search-empty')] = el;
  });
  var query = new URLSearchParams(window.location.search).get('q') || '';
  var typeLabel = { all: 'result', skill: 'skill', mentor: 'mentor', learner: 'learner', post: 'post' };
  function applyFilter(type) {
    var visibleCount = 0;
    items.forEach(function (item) {
      var isMatch = type === 'all' || item.getAttribute('data-result-type') === type;
      item.hidden = !isMatch;
      if (isMatch) {
        visibleCount++;
      }
    });
    resultsContainer.hidden = visibleCount === 0;

    Object.keys(emptyStates).forEach(function (key) {
      emptyStates[key].hidden = !(type === key && visibleCount === 0);
    });
    if (summary) {
      var label = typeLabel[type] || 'result';
      summary.textContent = visibleCount + ' ' + label + (visibleCount === 1 ? '' : 's') + ' for “' + query + '”';
    }
  }
  filterBar.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-search-filter]');
    if (!btn) {
      return;
    }
    var type = btn.getAttribute('data-search-filter');
    filterBar.querySelectorAll('[data-search-filter]').forEach(function (pill) {
      var isActive = pill === btn;
      pill.classList.toggle('is-active', isActive);
      pill.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    applyFilter(type);
  });
  applyFilter('all');
})();