/**
 * Post Details / My Posts / Saved Posts — page-specific frontend behavior
 * only. Voting, Save/Unsave and the Edit/Delete modals themselves are all
 * already generic (assets/js/components/voting.js, save-post.js,
 * assets/js/core/modal.js) and not duplicated here; the new top-level
 * comment composer and existing comment/reply interactions are handled by
 * assets/js/components/comments.js. This file only has three
 * responsibilities:
 *   1. the comment composer on Post Details (adds a comment, not a reply)
 *   2. removing a components/post-card.php card once its Delete is
 *      confirmed (data-remove-post-card, same convention as skill/goal/
 *      session removal elsewhere in this project) — works on any page
 *      that renders post-card.php, including Home, since this file loads
 *      on every page
 *   3. Saved Posts' Unsave-removes-the-card behavior, and the My Posts /
 *      Saved Posts search + skill filter row from the approved design
 *
 * All frontend-only mock state, kept in the DOM — nothing here persists
 * anywhere or survives a refresh.
 */
(function () {
  'use strict';

  /* ---- 1. Comment composer (Post Details) ---- */

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-comment-form]');
    if (!form) {
      return;
    }
    event.preventDefault(); // frontend-only mock — never a real comment

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

  /* ---- 2. Remove a post card once its Delete is confirmed ---- */
  /* Mirrors the exact [data-remove-skill-card] / [data-remove-goal-card]
     pending-reference pattern already used by assets/js/pages/skills.js
     and learning.js on this same shared modal (sessions.js uses the same
     idea again for [data-reject-session]/[data-cancel-session]) — a
     distinct marker attribute means none of these listeners interfere
     with each other. */

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
      // Deleting the post whose own page we're viewing — nothing sensible
      // to remove it FROM, so send the (mock) owner back to their posts.
      window.location.href = 'index.php?page=my-posts';
      return;
    }

    var list = card.closest('[data-post-list]');
    card.remove();
    if (list) {
      updatePostListEmptyState(list);
    }
  });

  /* ---- 3. Saved Posts: Unsave removes the card; search + skill filter ---- */

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
      // assets/js/components/save-post.js's own delegated listener already
      // ran (registered first — it loads earlier in index.php) and
      // flipped data-saved on this same click, so this reads the
      // POST-toggle value.
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
