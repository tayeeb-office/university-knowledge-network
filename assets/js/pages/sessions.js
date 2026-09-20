(function () {
  'use strict';
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

    applyTabFilter(tablist);
  });

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