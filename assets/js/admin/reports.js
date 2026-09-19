/**
 * Admin Reports — page-specific behavior only (search/filter/sort/
 * pagination, the Report Review modal, and Resolve/Dismiss). Deliberately
 * separate from assets/js/admin/moderation.js: Reports never calls that
 * file's setPostStatus()/setCommentStatus() helpers, because resolving or
 * dismissing a report must NEVER automatically hide a Post/Comment or
 * suspend a User (that stays a distinct, explicit action an admin takes
 * on posts.php/comments.php/user-details.php — see admin/reports.php's
 * own docblock). This file only reuses the same conventions those files
 * already established: window.UKN.showToast() for the mock success
 * notice, the "read context from data-* attributes on the clicked row"
 * pattern for populating one reusable detail modal instead of a second
 * admin/report-details.php page, and one static (not re-bound-per-open)
 * document-level click listener so Resolve/Dismiss can never fire twice
 * for one click.
 *
 * Guarded by a single page-detection check so loading this file on any
 * other Admin page (it isn't, today — only admin/reports.php enqueues
 * it) would simply no-op rather than throw.
 */
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

  /* ---- Summary counts (Pending/Resolved/Dismissed) ---- */
  /* These mirror admin/dashboard.php's own network-wide totals and are
     independent of how many sample rows actually exist in the table below
     — the same "summary vs. sample" split admin/posts.php and
     admin/comments.php already use for their own stat cards. Resolving or
     dismissing a report only moves these three numbers between each
     other; Total never changes. */

  var statValueEls = {
    pending: document.querySelector('[data-report-stat="pending"] .ukn-display'),
    resolved: document.querySelector('[data-report-stat="resolved"] .ukn-display'),
    dismissed: document.querySelector('[data-report-stat="dismissed"] .ukn-display'),
  };
  var counts = {
    pending: parseInt((statValueEls.pending && statValueEls.pending.textContent) || '0', 10) || 0,
    resolved: parseInt((statValueEls.resolved && statValueEls.resolved.textContent) || '0', 10) || 0,
    dismissed: parseInt((statValueEls.dismissed && statValueEls.dismissed.textContent) || '0', 10) || 0,
  };

  function moveCount(fromKey, toKey) {
    if (counts[fromKey] > 0) {
      counts[fromKey] -= 1;
    }
    counts[toKey] += 1;
    if (statValueEls[fromKey]) { statValueEls[fromKey].textContent = String(counts[fromKey]); }
    if (statValueEls[toKey]) { statValueEls[toKey].textContent = String(counts[toKey]); }
  }

  /* ---- Report status transition (Pending -> Resolved | Dismissed) ---- */

  function setReportStatus(row, status, decisionText) {
    var fromStatus = row.getAttribute('data-report-status');
    row.setAttribute('data-report-status', status);
    row.setAttribute('data-report-decision', decisionText);
    row.setAttribute('data-report-resolved-display', 'Just now (demo)');

    var badge = row.querySelector('[data-report-status-badge]');
    if (badge) {
      badge.textContent = status === 'resolved' ? 'Resolved' : 'Dismissed';
      badge.classList.remove('ukn-status-accent', 'ukn-status-neutral');
      badge.classList.add(status === 'resolved' ? 'ukn-status-accent' : 'ukn-status-neutral');
    }

    if (fromStatus === 'pending') {
      moveCount('pending', status === 'resolved' ? 'resolved' : 'dismissed');
    }
  }

  /* ---- Report Review modal (no admin/report-details.php page) ---- */

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
    var resolveBtn = reviewModal.querySelector('[data-report-resolve]');
    var dismissBtn = reviewModal.querySelector('[data-report-dismiss]');
    var isPending = status === 'pending';

    if (resolveBtn) { resolveBtn.hidden = !isPending; }
    if (dismissBtn) { dismissBtn.hidden = !isPending; }
    if (decisionRow) {
      decisionRow.hidden = isPending;
      if (!isPending) {
        setText(reviewModal, 'decision', row.getAttribute('data-report-decision'));
        setText(reviewModal, 'resolvedDisplay', row.getAttribute('data-report-resolved-display'));
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
  }

  document.addEventListener('click', function (event) {
    var resolveBtn = event.target.closest('[data-report-resolve]');
    var dismissBtn = event.target.closest('[data-report-dismiss]');
    if ((!resolveBtn && !dismissBtn) || !currentRow) {
      return;
    }

    var row = currentRow;
    var status = resolveBtn ? 'resolved' : 'dismissed';
    var decisionText = resolveBtn
      ? 'Reviewed by admin. Report resolved in demo mode.'
      : 'Reviewed by admin. No further action required.';

    setReportStatus(row, status, decisionText);
    populateReview(row); // re-render so the still-open modal reflects the new state, not stale Pending actions

    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(status === 'resolved' ? 'Report resolved in demo mode.' : 'Report dismissed in demo mode.', 'success');
    }

    if (typeof applyReportsView === 'function') {
      applyReportsView();
    }

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      var instance = bootstrap.Modal.getInstance(reviewModal);
      if (instance) {
        instance.hide();
      }
    }
  });

  /* ---- Search / filter / sort / pagination ---- */

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
