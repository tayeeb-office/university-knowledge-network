(function () {
  'use strict';
  // Comments, post delete and unsave are server actions (backend/comments, backend/posts);
  // the comment form only needs a non-empty check before it posts.
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-comment-form]');
    if (!form) {
      return;
    }
    var textarea = form.querySelector('textarea');
    var errorEl = form.querySelector('[data-comment-error]');
    var empty = !textarea || !textarea.value.trim();
    if (errorEl) {
      errorEl.hidden = !empty;
    }
    if (empty) {
      event.preventDefault();
    }
  });

  document.querySelectorAll('[data-post-filters]').forEach(function (filterBar) {
    var searchInput = filterBar.querySelector('[data-post-search-input]');
    var skillSelect = filterBar.querySelector('[data-post-skill-filter]');
    var list = document.querySelector('[data-post-list="' + filterBar.getAttribute('data-post-filters') + '"]');
    if (!list) {
      return;
    }
    var items = Array.prototype.slice.call(list.querySelectorAll('article[data-post-id]'));
    var applyFilters = function () {
      var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
      var skill = skillSelect ? skillSelect.value : '';
      var visibleCount = 0;
      items.forEach(function (item) {
        var matchesQuery = !query || (item.getAttribute('data-post-search') || '').indexOf(query) !== -1;
        var matchesSkill = !skill || (item.getAttribute('data-post-skills') || '').split('|').indexOf(skill) !== -1;
        var matches = matchesQuery && matchesSkill;
        item.hidden = !matches;
        if (matches) {
          visibleCount++;
        }
      });

      var emptyState = list.parentElement ? list.parentElement.querySelector('[data-post-list-empty]') : null;
      if (emptyState && items.length) {
        emptyState.hidden = visibleCount !== 0;
      }
    };
    if (searchInput) {
      searchInput.addEventListener('input', applyFilters);
    }
    if (skillSelect) {
      skillSelect.addEventListener('change', applyFilters);
    }
  });
})();