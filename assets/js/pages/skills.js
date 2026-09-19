/**
 * Skills Directory / My Learning Skills / My Teaching Skills / Skill
 * Details — page-specific frontend behavior only. Generic component
 * behavior (modals, toasts, validation, theme, role switching) already
 * has its own dedicated module and is not duplicated here — this file
 * only handles what's unique to the skills pages:
 *   1. Skills Directory search + category filtering (client-side only,
 *      completely separate from includes/header.php's global search —
 *      different element ids, no shared state)
 *   2. Mock Add to Learning / Add to Teaching toggle
 *      (components/skill-card.php's [data-skill-toggle] buttons — the
 *      markup already existed, this is the behavior for it)
 *   3. Mock Remove from Learning/Teaching Skills — reuses the one shared
 *      modals/delete-confirmation-modal.php instead of a second
 *      confirmation UI; assets/js/core/modal.js already drives that
 *      modal's own toast+close, this only additionally hides the
 *      specific skill card that triggered it and reveals the page's
 *      empty state once none are left
 *
 * All frontend-only mock state, kept in memory — nothing here persists
 * anywhere or survives a refresh, matching every other mock interaction
 * in this project (follow, save, vote, session accept/reject, ...).
 */
(function () {
  'use strict';

  /* ---- 1. Skills Directory search + category filter ---- */

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
      event.preventDefault(); // this is a components/empty-state.php link, not a real destination
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

  /* ---- 2. Mock Add to Learning / Add to Teaching ---- */

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-skill-toggle]');
    if (!btn) {
      return;
    }

    var kind = btn.getAttribute('data-skill-toggle'); // 'learning' | 'teaching'
    var kindLabel = kind === 'teaching' ? 'Teaching' : 'Learning';
    var next = btn.getAttribute('data-state') !== 'added';

    btn.setAttribute('data-state', next ? 'added' : 'add');
    btn.classList.toggle('btn-outline-primary', !next);
    btn.classList.toggle('btn-outline-secondary', next);

    var icon = btn.querySelector('.ms');
    if (icon) {
      icon.textContent = next ? 'check' : 'add';
    }
    var label = btn.querySelector('[data-skill-toggle-label]');
    if (label) {
      label.textContent = next ? kindLabel : ('Add to ' + kindLabel);
    }

    var card = btn.closest('[data-skill-name]');
    var skillName = card ? card.getAttribute('data-skill-name') : 'This skill';
    var verb = next ? 'added to' : 'removed from';
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(skillName + ' ' + verb + ' your ' + kindLabel.toLowerCase() + ' skills.', 'success');
    }
  });

  /* ---- 3. Mock Remove from Learning/Teaching Skills ---- */

  var pendingRemoveCard = null;
  var deleteModal = document.getElementById('deleteConfirmationModal');

  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      pendingRemoveCard = trigger && trigger.hasAttribute('data-remove-skill-card')
        ? trigger.closest('[data-skill-name]')
        : null;
    });
  }

  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-delete-confirm]') || !pendingRemoveCard) {
      return;
    }
    var card = pendingRemoveCard;
    var list = card.closest('[data-skill-list]');
    pendingRemoveCard = null;

    card.remove();

    if (list && !list.querySelector('[data-skill-name]')) {
      list.hidden = true;
      var listEmptyState = document.querySelector('[data-skill-list-empty]');
      if (listEmptyState) {
        listEmptyState.hidden = false;
      }
    }
  });
})();
