(function () {
  'use strict';
  var deleteModal = document.getElementById('deleteConfirmationModal');
  var pendingAction = null;
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      pendingAction = null;
      if (!trigger) {
        return;
      }
      var row = trigger.closest('[data-user-row]');
      if (!row) {
        return;
      }
      if (trigger.hasAttribute('data-suspend-user')) {
        pendingAction = { type: 'suspend', row: row };
      } else if (trigger.hasAttribute('data-restore-user')) {
        pendingAction = { type: 'restore', row: row };
      }
    });
  }
  function setRowStatus(row, status) {
    var label = status === 'active' ? 'Active' : (status === 'suspended' ? 'Suspended' : 'Inactive');
    var statusClass = status === 'active' ? 'ukn-status-accent' : 'ukn-status-neutral';
    row.dataset.userStatus = status;
    var badge = row.querySelector('[data-user-status-badge]');
    if (badge) {
      badge.textContent = label;
      badge.classList.remove('ukn-status-accent', 'ukn-status-neutral');
      badge.classList.add(statusClass);
    }
    var suspendBtn = row.querySelector('[data-suspend-user]');
    var restoreBtn = row.querySelector('[data-restore-user]');
    if (suspendBtn) {
      suspendBtn.hidden = status === 'suspended';
    }
    if (restoreBtn) {
      restoreBtn.hidden = status !== 'suspended';
    }
  }

  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-delete-confirm]') || !pendingAction) {
      return;
    }
    var action = pendingAction;
    pendingAction = null;
    setRowStatus(action.row, action.type === 'suspend' ? 'suspended' : 'active');
    if (typeof applyUsersView === 'function') {
      applyUsersView();
    }
  });

  var table = document.querySelector('[data-user-table]');
  if (!table) {
    return;
  }
  var tbody = table.querySelector('tbody');
  var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-user-row]'));
  var searchInput = document.querySelector('[data-user-search]');
  var roleFilter = document.querySelector('[data-user-filter="role"]');
  var deptFilter = document.querySelector('[data-user-filter="department"]');
  var statusFilter = document.querySelector('[data-user-filter="status"]');
  var sortSelect = document.querySelector('[data-user-sort]');
  var resultCountEl = document.querySelector('[data-user-result-count]');
  var emptyStateEl = document.querySelector('[data-user-empty]');
  var paginationEl = document.querySelector('[data-user-pagination]');
  var paginationSummaryEl = document.querySelector('[data-user-pagination-summary]');
  var paginationPagesEl = document.querySelector('[data-user-pagination-pages]');
  var PAGE_SIZE = 10;
  var currentPage = 1;
  function sortRows() {
    var key = sortSelect ? sortSelect.value : 'newest';
    var sorted = rows.slice().sort(function (a, b) {
      switch (key) {
        case 'oldest':
          return a.getAttribute('data-user-joined-sort').localeCompare(b.getAttribute('data-user-joined-sort'));
        case 'name-asc':
          return a.getAttribute('data-user-name').localeCompare(b.getAttribute('data-user-name'));
        case 'name-desc':
          return b.getAttribute('data-user-name').localeCompare(a.getAttribute('data-user-name'));
        case 'active':
          return parseInt(b.getAttribute('data-user-sessions'), 10) - parseInt(a.getAttribute('data-user-sessions'), 10);
        case 'newest':
        default:
          return b.getAttribute('data-user-joined-sort').localeCompare(a.getAttribute('data-user-joined-sort'));
      }
    });
    sorted.forEach(function (row) { tbody.appendChild(row); });
    rows = sorted;
  }
  function matchesFilters(row) {
    var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    if (query) {
      var haystack = row.getAttribute('data-user-name') + ' ' + row.getAttribute('data-user-email') + ' ' + row.getAttribute('data-user-uid');
      if (haystack.indexOf(query) === -1) {
        return false;
      }
    }
    if (roleFilter && roleFilter.value && row.getAttribute('data-user-role') !== roleFilter.value) {
      return false;
    }
    if (deptFilter && deptFilter.value && row.getAttribute('data-user-department') !== deptFilter.value) {
      return false;
    }
    if (statusFilter && statusFilter.value && row.getAttribute('data-user-status') !== statusFilter.value) {
      return false;
    }
    return true;
  }
  function renderPagination(totalMatched) {
    if (!paginationEl) {
      return;
    }
    var totalPages = Math.max(1, Math.ceil(totalMatched / PAGE_SIZE));
    if (currentPage > totalPages) {
      currentPage = totalPages;
    }
    if (paginationSummaryEl) {
      if (totalMatched === 0) {
        paginationSummaryEl.textContent = '';
      } else {
        var start = (currentPage - 1) * PAGE_SIZE + 1;
        var end = Math.min(totalMatched, currentPage * PAGE_SIZE);
        paginationSummaryEl.textContent = 'Showing ' + start + '–' + end + ' of ' + totalMatched;
      }
    }
    if (paginationPagesEl) {
      paginationPagesEl.innerHTML = '';
      if (totalPages > 1) {
        for (var p = 1; p <= totalPages; p++) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn btn-sm ' + (p === currentPage ? 'btn-primary' : 'btn-outline-secondary');
          btn.textContent = String(p);
          btn.setAttribute('aria-label', 'Page ' + p);
          if (p === currentPage) {
            btn.setAttribute('aria-current', 'page');
          }
          (function (page) {
            btn.addEventListener('click', function () {
              currentPage = page;
              applyUsersView();
            });
          })(p);
          paginationPagesEl.appendChild(btn);
        }
      }
    }
    paginationEl.hidden = totalMatched === 0;
  }
  window.applyUsersView = function applyUsersView() {
    sortRows();
    var matched = rows.filter(matchesFilters);
    var pageStart = (currentPage - 1) * PAGE_SIZE;
    var pageEnd = pageStart + PAGE_SIZE;
    var visibleThisPage = matched.slice(pageStart, pageEnd);
    var visibleSet = new Set(visibleThisPage);
    rows.forEach(function (row) {
      row.hidden = !visibleSet.has(row);
    });
    if (resultCountEl) {
      resultCountEl.textContent = matched.length + (matched.length === 1 ? ' user found' : ' users found');
    }
    if (emptyStateEl) {
      emptyStateEl.hidden = matched.length !== 0;
    }
    table.closest('.card').querySelector('.table-responsive').hidden = matched.length === 0;
    renderPagination(matched.length);
  };
  function resetToFirstPage() {
    currentPage = 1;
    applyUsersView();
  }
  if (searchInput) {
    searchInput.addEventListener('input', resetToFirstPage);
  }
  [roleFilter, deptFilter, statusFilter, sortSelect].forEach(function (control) {
    if (control) {
      control.addEventListener('change', resetToFirstPage);
    }
  });


  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-user-clear-filters]');
    if (!trigger) {
      return;
    }
    event.preventDefault();
    if (searchInput) { searchInput.value = ''; }
    [roleFilter, deptFilter, statusFilter].forEach(function (control) {
      if (control) { control.value = ''; }
    });
    if (sortSelect) { sortSelect.value = 'newest'; }
    resetToFirstPage();
  });
  applyUsersView();
})();