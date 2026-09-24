(function () {
  'use strict';
  var table = document.querySelector('[data-report-table]');
  if (!table) {
    return;
  }
  var tbody = table.querySelector('tbody');
  function reportRows() {
    return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-report-row]'));
  }
  var reviewModal = document.getElementById('reportReviewModal');
  var currentRow = null;
  function setText(scope, key, value) {
    var el = scope.querySelector('[data-report-detail="' + key + '"]');
    if (el) {
      el.textContent = value || '—';
    }
  }

  function setStatusBadge(el, label, positive) {
    if (!el) {
      return;
    }
    el.textContent = label;
    el.classList.remove('ukn-status-accent', 'ukn-status-neutral');
    el.classList.add(positive ? 'ukn-status-accent' : 'ukn-status-neutral');
  }
  function populateReview(row) {
    currentRow = row;
    var type = row.getAttribute('data-report-type');
    var status = row.getAttribute('data-report-status');

    setText(reviewModal, 'id', row.getAttribute('data-report-id'));
    setText(reviewModal, 'type', type.charAt(0).toUpperCase() + type.slice(1));
    setText(reviewModal, 'reportedDisplay', row.getAttribute('data-report-reported-display'));
    setText(reviewModal, 'reason', row.getAttribute('data-report-reason'));
    setText(reviewModal, 'description', row.getAttribute('data-report-description'));
    var statusLabels = { pending: 'Pending', resolved: 'Resolved', dismissed: 'Dismissed' };
    setStatusBadge(reviewModal.querySelector('[data-report-detail-status-badge]'), statusLabels[status], status === 'resolved');
    var reporterLink = reviewModal.querySelector('[data-report-detail-link="reporter"]');
    if (reporterLink) {
      reporterLink.textContent = row.getAttribute('data-report-reporter-name');
      reporterLink.href = 'user-details.php?id=' + row.getAttribute('data-report-reporter-id');
      reporterLink.setAttribute('aria-label', 'View ' + row.getAttribute('data-report-reporter-name') + ' in Admin');
    }
    setText(reviewModal, 'reporterDepartment', row.getAttribute('data-report-reporter-department'));
    ['post', 'comment', 'user'].forEach(function (sectionType) {
      var section = reviewModal.querySelector('[data-report-section="' + sectionType + '"]');
      if (section) {
        section.hidden = sectionType !== type;
      }
    });

    var moderationLink = reviewModal.querySelector('[data-report-detail-link="moderation"]');
    var appLink = reviewModal.querySelector('[data-report-detail-link="app"]');
    if (type === 'post') {
      setText(reviewModal, 'postTitle', row.getAttribute('data-report-post-title'));
      setText(reviewModal, 'postId', row.getAttribute('data-report-post-id'));
      setText(reviewModal, 'postExcerpt', row.getAttribute('data-report-post-excerpt'));
      var postAuthorLink = reviewModal.querySelector('[data-report-detail-link="postAuthor"]');
      if (postAuthorLink) {
        postAuthorLink.textContent = row.getAttribute('data-report-post-author-name');
        postAuthorLink.href = 'user-details.php?id=' + row.getAttribute('data-report-post-author-id');
        postAuthorLink.setAttribute('aria-label', 'View ' + row.getAttribute('data-report-post-author-name') + ' in Admin');
      }
      var postStatus = row.getAttribute('data-report-post-status');
      setStatusBadge(reviewModal.querySelector('[data-report-detail-post-status-badge]'), postStatus === 'visible' ? 'Visible' : 'Hidden', postStatus === 'visible');
      if (moderationLink) {
        moderationLink.textContent = 'View Post Moderation';
        moderationLink.href = 'posts.php';
      }
      if (appLink) {
        appLink.hidden = false;
        appLink.textContent = 'View in Application';
        appLink.href = '../index.php?page=post-details&id=' + row.getAttribute('data-report-post-public-id');
      }
    } else if (type === 'comment') {
      setText(reviewModal, 'commentText', row.getAttribute('data-report-comment-text'));
      setText(reviewModal, 'commentPost', row.getAttribute('data-report-comment-post-title'));
      var commentAuthorLink = reviewModal.querySelector('[data-report-detail-link="commentAuthor"]');
      if (commentAuthorLink) {
        commentAuthorLink.textContent = row.getAttribute('data-report-comment-author-name');
        commentAuthorLink.href = 'user-details.php?id=' + row.getAttribute('data-report-comment-author-id');
        commentAuthorLink.setAttribute('aria-label', 'View ' + row.getAttribute('data-report-comment-author-name') + ' in Admin');
      }
      var commentStatus = row.getAttribute('data-report-comment-status');
      setStatusBadge(reviewModal.querySelector('[data-report-detail-comment-status-badge]'), commentStatus === 'visible' ? 'Visible' : 'Hidden', commentStatus === 'visible');
      if (moderationLink) {
        moderationLink.textContent = 'View Comment Moderation';
        moderationLink.href = 'comments.php';
      }
      if (appLink) {
        appLink.hidden = false;
        appLink.textContent = 'View Discussion';
        appLink.href = '../index.php?page=post-details&id=' + row.getAttribute('data-report-comment-post-id');
      }
    } else {
      setText(reviewModal, 'userRole', row.getAttribute('data-report-user-role'));
      setText(reviewModal, 'userDepartment', row.getAttribute('data-report-user-department'));
      var reportedUserLink = reviewModal.querySelector('[data-report-detail-link="reportedUser"]');
      if (reportedUserLink) {
        reportedUserLink.textContent = row.getAttribute('data-report-user-name');
        reportedUserLink.href = 'user-details.php?id=' + row.getAttribute('data-report-user-id');
        reportedUserLink.setAttribute('aria-label', 'View ' + row.getAttribute('data-report-user-name') + ' in Admin');
      }
      var userStatus = row.getAttribute('data-report-user-status');
      setStatusBadge(reviewModal.querySelector('[data-report-detail-user-status-badge]'), userStatus, userStatus === 'Active');
      if (moderationLink) {
        moderationLink.textContent = 'View User';
        moderationLink.href = 'user-details.php?id=' + row.getAttribute('data-report-user-id');
      }
      if (appLink) {
        appLink.hidden = true;
      }
    }
    var contentReports = parseInt(row.getAttribute('data-report-content-reports'), 10) || 1;
    var multiEl = reviewModal.querySelector('[data-report-detail-multi]');
    if (multiEl) {
      multiEl.hidden = !(type !== 'user' && contentReports > 1);
      if (!multiEl.hidden) {
        multiEl.textContent = 'This content has ' + contentReports + ' reports in total.';
      }
    }
    var decisionRow = reviewModal.querySelector('[data-report-decision-row]');
    var isPending = status === 'pending';
    if (decisionRow) {
      decisionRow.hidden = isPending;
      if (!isPending) {
        setText(reviewModal, 'decision', row.getAttribute('data-report-decision'));
        setText(reviewModal, 'resolvedDisplay', row.getAttribute('data-report-resolved-display'));
      }
    }
    // Step 51: Resolve / Dismiss submit a real form for this report. The optional target
    // action (hide the post/comment, suspend the user) is offered only when it can apply.
    var form = reviewModal.querySelector('[data-report-review-form]');
    if (form) {
      form.hidden = !isPending;
      form.reset();
      form.querySelector('[data-report-review-id]').value = row.getAttribute('data-report-db-id') || '';
      var actionWrap = form.querySelector('[data-report-review-action-wrap]');
      var actionLabel = form.querySelector('[data-report-review-action-label]');
      var targetStatus = type === 'post' ? row.getAttribute('data-report-post-status')
        : (type === 'comment' ? row.getAttribute('data-report-comment-status') : row.getAttribute('data-report-user-status'));
      var canAct = row.getAttribute('data-report-target-available') === '1'
        && row.getAttribute('data-report-target-actionable') === '1'
        && (type === 'user' ? targetStatus !== 'Suspended' : targetStatus === 'visible');
      if (actionWrap) {
        actionWrap.hidden = !canAct;
      }
      if (actionLabel) {
        actionLabel.textContent = type === 'user' ? 'Also suspend this user' : 'Also hide this ' + type;
      }
    }
  }
  if (reviewModal) {
    reviewModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      var row = trigger ? trigger.closest('[data-report-row]') : null;
      if (row) {
        populateReview(row);
      }
    });
    // Dismissing never moderates the target, so the option is cleared before that submit.
    reviewModal.addEventListener('click', function (event) {
      if (event.target.closest('[data-report-dismiss]')) {
        var action = reviewModal.querySelector('[data-report-review-action]');
        if (action) {
          action.checked = false;
        }
      }
    });
  }
  var searchInput = document.querySelector('[data-report-search]');
  var typeFilter = document.querySelector('[data-report-filter="type"]');
  var statusFilter = document.querySelector('[data-report-filter="status"]');
  var reasonFilter = document.querySelector('[data-report-filter="reason"]');
  var dateFilter = document.querySelector('[data-report-filter="date"]');
  var sortSelect = document.querySelector('[data-report-sort]');
  var resultCountEl = document.querySelector('[data-report-result-count]');
  var emptyStateEl = document.querySelector('[data-report-empty]');
  var emptyPendingEl = document.querySelector('[data-report-empty-pending]');
  var paginationEl = document.querySelector('[data-report-pagination]');
  var paginationSummaryEl = document.querySelector('[data-report-pagination-summary]');
  var paginationPagesEl = document.querySelector('[data-report-pagination-pages]');
  var PAGE_SIZE = 10;
  var currentPage = 1;
  var now = new Date();
  var todayIso = now.toISOString().slice(0, 10);
  var weekStart = new Date(now);
  weekStart.setDate(now.getDate() - ((now.getDay() + 6) % 7));
  weekStart.setHours(0, 0, 0, 0);
  var monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

  function sortRows() {
    var key = sortSelect ? sortSelect.value : 'newest';
    var rows = reportRows();
    var sorted = rows.slice().sort(function (a, b) {
      switch (key) {
        case 'oldest':
          return parseInt(a.getAttribute('data-report-date-sort'), 10) - parseInt(b.getAttribute('data-report-date-sort'), 10);
        case 'most-reports':
          return parseInt(b.getAttribute('data-report-content-reports'), 10) - parseInt(a.getAttribute('data-report-content-reports'), 10);
        case 'newest':
        default:
          return parseInt(b.getAttribute('data-report-date-sort'), 10) - parseInt(a.getAttribute('data-report-date-sort'), 10);
      }
    });
    sorted.forEach(function (row) { tbody.appendChild(row); });
  }

  function matchesFilters(row) {
    var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    if (query && row.getAttribute('data-report-search').indexOf(query) === -1) {
      return false;
    }
    if (typeFilter && typeFilter.value && row.getAttribute('data-report-type') !== typeFilter.value) {
      return false;
    }
    if (statusFilter && statusFilter.value && row.getAttribute('data-report-status') !== statusFilter.value) {
      return false;
    }
    if (reasonFilter && reasonFilter.value && row.getAttribute('data-report-reason-slug') !== reasonFilter.value) {
      return false;
    }
    if (dateFilter && dateFilter.value) {
      var iso = row.getAttribute('data-report-date-iso');
      if (dateFilter.value === 'today' && iso !== todayIso) {
        return false;
      }
      if (dateFilter.value === 'week' && new Date(iso + 'T00:00:00') < weekStart) {
        return false;
      }
      if (dateFilter.value === 'month' && new Date(iso + 'T00:00:00') < monthStart) {
        return false;
      }
    }
    return true;
  }
  function renderPagination(total) {
    if (!paginationEl) {
      return;
    }
    var totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (currentPage > totalPages) {
      currentPage = totalPages;
    }
    if (paginationSummaryEl) {
      paginationSummaryEl.textContent = total === 0 ? '' :
        'Showing ' + ((currentPage - 1) * PAGE_SIZE + 1) + '–' + Math.min(total, currentPage * PAGE_SIZE) + ' of ' + total;
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
              applyReportsView();
            });
          })(p);
          paginationPagesEl.appendChild(btn);
        }
      }
    }
    paginationEl.hidden = total === 0;
  }
  window.applyReportsView = function applyReportsView() {
    sortRows();
    var rows = reportRows();
    var matched = rows.filter(matchesFilters);
    var pageStart = (currentPage - 1) * PAGE_SIZE;
    var visibleSet = new Set(matched.slice(pageStart, pageStart + PAGE_SIZE));
    rows.forEach(function (row) {
      row.hidden = !visibleSet.has(row);
    });
    if (resultCountEl) {
      resultCountEl.textContent = matched.length + (matched.length === 1 ? ' report found' : ' reports found');
    }
    var isPendingOnly = statusFilter && statusFilter.value === 'pending';
    if (emptyPendingEl) { emptyPendingEl.hidden = !(matched.length === 0 && isPendingOnly); }
    if (emptyStateEl) { emptyStateEl.hidden = matched.length !== 0 || isPendingOnly; }
    table.closest('.card').querySelector('.table-responsive').hidden = matched.length === 0;
    renderPagination(matched.length);
  };
  function resetToFirstPage() {
    currentPage = 1;
    applyReportsView();
  }
  if (searchInput) { searchInput.addEventListener('input', resetToFirstPage); }
  [typeFilter, statusFilter, reasonFilter, dateFilter, sortSelect].forEach(function (control) {
    if (control) { control.addEventListener('change', resetToFirstPage); }
  });
  document.addEventListener('click', function (event) {
    if (event.target.closest('[data-report-clear-filters]')) {
      event.preventDefault();
      if (searchInput) { searchInput.value = ''; }
      [typeFilter, statusFilter, reasonFilter, dateFilter].forEach(function (control) {
        if (control) { control.value = ''; }
      });
      if (sortSelect) { sortSelect.value = 'newest'; }
      resetToFirstPage();
      return;
    }
    var summaryBtn = event.target.closest('[data-report-summary-filter]');
    if (summaryBtn && statusFilter) {
      var parts = summaryBtn.getAttribute('data-report-summary-filter').split(':');
      statusFilter.value = parts[1] || '';
      resetToFirstPage();
    }
  });

  applyReportsView();
})();