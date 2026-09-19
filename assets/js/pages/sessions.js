/**
 * Learner Requests / Sessions / Session Details — page-specific frontend
 * behavior only. Generic behavior (the shared modal engine, its Delete
 * Confirmation / Session Request / Rating contextual population, toasts,
 * validation) already has its own dedicated module and is not duplicated
 * here — this file only handles what's unique to the session workflow:
 *   - tab/filter switching (shared by Learner Requests' Pending/Accepted/
 *     Rejected/All tabs and Sessions' Upcoming/Completed/Cancelled tabs —
 *     same markup convention, so one mechanism serves both)
 *   - Accept / Reject / Cancel / Mark Complete — all just update the
 *     triggering components/session-card.php card's own status badge and
 *     actions in place (or pages/sessions/session-details.php's single
 *     status header, which uses the exact same data-session-* markup
 *     convention so this same code works there too)
 *
 * Reject and Cancel reuse the one shared modals/delete-confirmation-modal.php
 * — assets/js/core/modal.js already populates its title/message from the
 * data-delete-* attributes (unchanged); this file only additionally tracks
 * *which* transition to apply once confirmed, mirroring the exact
 * [data-remove-skill-card] / [data-remove-goal-card] pending-reference
 * pattern already used by assets/js/pages/skills.js and learning.js on
 * this same shared modal — distinct marker attributes mean none of these
 * listeners interfere with each other.
 *
 * All frontend-only mock state, kept in the DOM — nothing here persists
 * anywhere or survives a refresh.
 */
(function () {
  'use strict';

  /* ---- Shared: apply the active tab as a status filter ---- */

  function updateTabCounts(tablist) {
    var list = document.querySelector('[data-session-list]');
    if (!list) {
      return;
    }
    tablist.querySelectorAll('[data-session-tab]').forEach(function (tab) {
      var status = tab.getAttribute('data-session-tab');
      var countEl = tab.querySelector('[data-session-tab-count]');
      if (!countEl) {
        return;
      }
      var selector = status === 'all' ? '[data-session-id]' : '[data-session-id][data-session-status="' + status + '"]';
      countEl.textContent = String(list.querySelectorAll(selector).length);
    });
  }

  function applyTabFilter(tablist) {
    var activeTab = tablist.querySelector('[data-session-tab].active');
    var filter = activeTab ? activeTab.getAttribute('data-session-tab') : 'all';
    var list = document.querySelector('[data-session-list]');
    var emptyState = document.querySelector('[data-session-empty]');
    if (!list) {
      return;
    }

    var visibleCount = 0;
    list.querySelectorAll('[data-session-id]').forEach(function (card) {
      var matches = filter === 'all' || card.getAttribute('data-session-status') === filter;
      card.hidden = !matches;
      if (matches) {
        visibleCount++;
      }
    });

    if (emptyState) {
      emptyState.hidden = visibleCount !== 0;
    }
    updateTabCounts(tablist);
  }

  document.addEventListener('click', function (event) {
    var tabBtn = event.target.closest('[data-session-tab]');
    if (!tabBtn) {
      return;
    }
    var tablist = tabBtn.closest('[data-session-tablist]');
    if (!tablist) {
      return;
    }
    tablist.querySelectorAll('[data-session-tab]').forEach(function (tab) {
      var isActive = tab === tabBtn;
      tab.classList.toggle('active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    applyTabFilter(tablist);
  });

  document.querySelectorAll('[data-session-tablist]').forEach(function (tablist) {
    // Cards render server-side with no hidden state of their own — this
    // establishes the correct initial visibility for whichever tab is
    // already marked .active, using the exact same code path clicking a
    // tab uses, rather than a second "which cards start hidden" mechanism.
    applyTabFilter(tablist);
  });

  /* ---- Shared: transition one session card's status in place ---- */

  function transitionSessionStatus(card, status, label, badgeClass) {
    card.setAttribute('data-session-status', status);

    var badge = card.querySelector('[data-session-status-badge]');
    if (badge) {
      badge.className = 'ukn-status ' + badgeClass;
      badge.textContent = label;
    }

    var actions = card.querySelector('[data-session-actions]');
    if (actions) {
      var detailsHref = card.getAttribute('data-session-details-href');
      actions.innerHTML = '';
      if (detailsHref) {
        var link = document.createElement('a');
        link.className = 'btn btn-outline-secondary btn-sm';
        link.href = detailsHref;
        link.textContent = 'View Details';
        actions.appendChild(link);
      }
    }

    var tablist = document.querySelector('[data-session-tablist]');
    if (tablist) {
      applyTabFilter(tablist);
    }
  }

  /* ---- Accept (immediate, no confirmation) ---- */

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-session-accept]');
    if (!btn) {
      return;
    }
    var card = btn.closest('[data-session-id]');
    if (!card) {
      return;
    }
    transitionSessionStatus(card, 'accepted', 'Accepted', 'ukn-status-success');
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast('Session request accepted.', 'success');
    }
  });

  /* ---- Reject / Cancel (both confirm via the shared Delete Confirmation modal) ---- */

  var pendingSessionAction = null;
  var deleteModal = document.getElementById('deleteConfirmationModal');

  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      pendingSessionAction = null;
      if (!trigger) {
        return;
      }
      var card = trigger.closest('[data-session-id]');
      if (!card) {
        return;
      }
      if (trigger.hasAttribute('data-reject-session')) {
        pendingSessionAction = { card: card, status: 'rejected', label: 'Rejected', badgeClass: 'ukn-status-danger' };
      } else if (trigger.hasAttribute('data-cancel-session')) {
        pendingSessionAction = { card: card, status: 'cancelled', label: 'Cancelled', badgeClass: 'ukn-status-danger' };
      }
    });
  }

  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-delete-confirm]') || !pendingSessionAction) {
      return;
    }
    var action = pendingSessionAction;
    pendingSessionAction = null;
    transitionSessionStatus(action.card, action.status, action.label, action.badgeClass);
  });

  /* ---- Mark Session Complete (Mentor, Session Details only) ---- */

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-session-mark-complete]');
    if (!btn) {
      return;
    }
    var card = btn.closest('[data-session-id]');
    if (card) {
      transitionSessionStatus(card, 'completed', 'Completed', 'ukn-status-success');
    }
    btn.remove();
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast('Session marked as completed.', 'success');
    }
  });
})();
