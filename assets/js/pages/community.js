(function () {
  'use strict';
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-comment-form]');
    if (!form) {
      return;
    }
    event.preventDefault();
    var textarea = form.querySelector('textarea');
    var errorEl = form.querySelector('[data-comment-error]');
    var text = textarea ? textarea.value.trim() : '';
    if (!text) {
      if (errorEl) {
        errorEl.hidden = false;
      }
      return;
    }
    if (errorEl) {
      errorEl.hidden = true;
    }
    var list = document.querySelector('[data-comments-list]');
    var composer = document.querySelector('[data-comment-composer]');
    if (list && window.UKN && window.UKN.buildCommentCard) {
      list.appendChild(window.UKN.buildCommentCard({
        initials: (composer && composer.getAttribute('data-current-user-initials')) || 'NR',
        name: (composer && composer.getAttribute('data-current-user-name')) || 'Nabila Rahman',
        role: 'Learner',
        time: 'Just now',
        text: text,
      }));
    }
    document.querySelectorAll('[data-post-comment-count]').forEach(function (el) {
      el.textContent = String((parseInt(el.textContent, 10) || 0) + 1);
    });
    var headingCount = document.querySelector('[data-comments-heading-count]');
    if (headingCount) {
      headingCount.textContent = String((parseInt(headingCount.textContent, 10) || 0) + 1);
    }
    textarea.value = '';
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast('Comment added.', 'success');
    }
  });
  var pendingRemovePostCard = null;
  var deleteModal = document.getElementById('deleteConfirmationModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      pendingRemovePostCard = trigger && trigger.hasAttribute('data-remove-post-card')
        ? trigger.closest('article[data-post-id]')
        : null;
    });
  }
  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-delete-confirm]') || !pendingRemovePostCard) {
      return;
    }
    var card = pendingRemovePostCard;
    pendingRemovePostCard = null;
    if (document.querySelector('[data-post-detail]')) {
      window.location.href = 'index.php?page=my-posts';
      return;
    }
    var list = card.closest('[data-post-list]');
    card.remove();
    if (list) {
      updatePostListEmptyState(list);
    }
  });
  function updatePostListEmptyState(list) {
    var emptyState = list.parentElement ? list.parentElement.querySelector('[data-post-list-empty]') : null;
    if (emptyState) {
      emptyState.hidden = !!list.querySelector('[data-post-id]');
    }
  }
  var savedList = document.querySelector('[data-post-list="saved"]');
  if (savedList) {
    document.addEventListener('click', function (event) {
      var saveBtn = event.target.closest('[data-save-post]');
      if (!saveBtn || !savedList.contains(saveBtn)) {
        return;
      }

      if (saveBtn.getAttribute('data-saved') === 'false') {
        var card = saveBtn.closest('article[data-post-id]');
        if (card) {
          card.remove();
          updatePostListEmptyState(savedList);
          if (window.UKN && window.UKN.showToast) {
            window.UKN.showToast('Post removed from saved items.', 'success');
          }
        }
      }
    });
  }

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