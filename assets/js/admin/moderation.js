/**
 * Admin moderation — Sessions (Prompt 27), Posts and Comments (Prompt 28),
 * each in its own nested IIFE inside one outer module so a page missing
 * one page's table never touches that section's code at all (page
 * detection), and so none of the three sections' identically-named local
 * variables (table/tbody/searchInput/...) can collide with each other.
 *
 * Hide/Restore/Cancel all reuse the ONE shared
 * modals/delete-confirmation-modal.php + its existing generic
 * [data-delete-confirm] toast/close behavior (assets/js/core/modal.js) —
 * this file only adds the "which row triggered it" pending-reference
 * step, the same pattern admin/users.js (Suspend/Restore) and
 * admin/skills.js (Deactivate/Activate) already use independently on
 * that same shared modal. Each section's own distinct marker attributes
 * (data-session-cancel, data-post-hide/-restore, data-comment-hide/
 * -restore) mean none of these listeners ever interfere with each other,
 * and in practice only one of the three sections' code ever runs per
 * page anyway (each Admin page loads this one shared file, but only the
 * table matching that page actually exists in the DOM).
 */
(function () {
  'use strict';

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  /* =====================================================================
     Sessions (admin/sessions.php) — unchanged from Prompt 27, just moved
     into its own nested scope so Posts/Comments below can coexist in the
     same file without an early `return` in one section skipping another.
     ===================================================================== */
  (function () {
    var table = document.querySelector('[data-session-table]');
    if (!table) {
      return;
    }

    var tbody = table.querySelector('tbody');

    function sessionRows() {
      return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-session-row]'));
    }

    /* ---- Cancel (shared delete-confirmation modal) ---- */

    var deleteModal = document.getElementById('deleteConfirmationModal');
    var pendingCancelRow = null;

    if (deleteModal) {
      deleteModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        pendingCancelRow = trigger && trigger.hasAttribute('data-session-cancel')
          ? trigger.closest('[data-session-row]')
          : null;
      });
    }

    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-delete-confirm]') || !pendingCancelRow) {
        return;
      }
      var row = pendingCancelRow;
      pendingCancelRow = null;

      row.dataset.sessionStatus = 'cancelled';
      var badge = row.querySelector('[data-session-status-badge]');
      if (badge) {
        badge.textContent = 'Cancelled';
        badge.classList.remove('ukn-status-accent');
        badge.classList.add('ukn-status-neutral');
      }
      var cancelBtn = row.querySelector('[data-session-cancel]');
      if (cancelBtn) {
        var menuItem = cancelBtn.closest('li');
        if (menuItem) {
          menuItem.hidden = true;
        }
        var menu = cancelBtn.closest('.dropdown-menu');
        if (menu && !menu.querySelector('.dropdown-item-text')) {
          var note = document.createElement('li');
          note.innerHTML = '<span class="dropdown-item-text ukn-body-sm ukn-text-muted">No further action available.</span>';
          menu.appendChild(note);
        }
      }

      var detailModalEl = document.getElementById('sessionDetailModal');
      if (detailModalEl && detailModalEl.dataset.currentSessionId === row.getAttribute('data-session-id')) {
        var statusEl = detailModalEl.querySelector('[data-session-detail="status"]');
        if (statusEl) {
          statusEl.textContent = 'Cancelled';
        }
      }

      if (typeof applySessionsView === 'function') {
        applySessionsView();
      }
    });

    /* ---- Session Details modal (no admin/session-details.php page) ---- */

    var detailModal = document.getElementById('sessionDetailModal');
    if (detailModal) {
      detailModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var row = trigger ? trigger.closest('[data-session-row]') : null;
        if (!row) {
          return;
        }

        detailModal.dataset.currentSessionId = row.getAttribute('data-session-id');

        var setText = function (key, value) {
          var el = detailModal.querySelector('[data-session-detail="' + key + '"]');
          if (el) {
            el.textContent = value || '—';
          }
        };
        setText('id', row.getAttribute('data-session-id'));
        setText('status', row.querySelector('[data-session-status-badge]').textContent);
        setText('skill', row.getAttribute('data-session-skill-label'));
        setText('date', row.getAttribute('data-session-date'));
        setText('time', row.getAttribute('data-session-time'));
        setText('duration', row.getAttribute('data-session-duration'));
        setText('learnerDepartment', row.getAttribute('data-session-learner-department'));
        setText('mentorDepartment', row.getAttribute('data-session-mentor-department'));

        var learnerLink = detailModal.querySelector('[data-session-detail-link="learner"]');
        if (learnerLink) {
          learnerLink.textContent = row.getAttribute('data-session-learner-name');
          learnerLink.href = 'user-details.php?id=' + row.getAttribute('data-session-learner-id');
          learnerLink.setAttribute('aria-label', 'View ' + row.getAttribute('data-session-learner-name') + ' in Admin');
        }
        var mentorLink = detailModal.querySelector('[data-session-detail-link="mentor"]');
        if (mentorLink) {
          mentorLink.textContent = row.getAttribute('data-session-mentor-name');
          mentorLink.href = 'user-details.php?id=' + row.getAttribute('data-session-mentor-id');
          mentorLink.setAttribute('aria-label', 'View ' + row.getAttribute('data-session-mentor-name') + ' in Admin');
        }

        var rating = row.getAttribute('data-session-rating');
        var ratingRow = detailModal.querySelector('[data-session-detail-rating-row]');
        if (ratingRow) {
          ratingRow.hidden = !rating;
          if (rating) {
            setText('rating', rating + ' / 5');
          }
        }

        var message = row.getAttribute('data-session-message');
        var messageRow = detailModal.querySelector('[data-session-detail-message-row]');
        if (messageRow) {
          messageRow.hidden = !message;
          if (message) {
            setText('message', message);
          }
        }
      });
    }

    /* ---- Search / filter / sort / pagination ---- */

    var searchInput = document.querySelector('[data-session-search]');
    var statusFilter = document.querySelector('[data-session-filter="status"]');
    var skillFilter = document.querySelector('[data-session-filter="skill"]');
    var dateFilter = document.querySelector('[data-session-filter="date"]');
    var sortSelect = document.querySelector('[data-session-sort]');
    var resultCountEl = document.querySelector('[data-session-result-count]');
    var emptyStateEl = document.querySelector('[data-session-empty]');
    var paginationEl = document.querySelector('[data-session-pagination]');
    var paginationSummaryEl = document.querySelector('[data-session-pagination-summary]');
    var paginationPagesEl = document.querySelector('[data-session-pagination-pages]');
    var PAGE_SIZE = 10;
    var currentPage = 1;
    var todayIso = new Date().toISOString().slice(0, 10);

    function sortRows() {
      var key = sortSelect ? sortSelect.value : 'newest';
      var rows = sessionRows();
      var sorted = rows.slice().sort(function (a, b) {
        var aDate = a.getAttribute('data-session-date-sort');
        var bDate = b.getAttribute('data-session-date-sort');
        switch (key) {
          case 'oldest':
            return aDate.localeCompare(bDate);
          case 'upcoming-first':
            var aFuture = aDate >= todayIso ? 0 : 1;
            var bFuture = bDate >= todayIso ? 0 : 1;
            return aFuture - bFuture || aDate.localeCompare(bDate);
          case 'recently-completed':
            return (a.getAttribute('data-session-status') === 'completed' ? 0 : 1) - (b.getAttribute('data-session-status') === 'completed' ? 0 : 1) || bDate.localeCompare(aDate);
          case 'newest':
          default:
            return bDate.localeCompare(aDate);
        }
      });
      sorted.forEach(function (row) { tbody.appendChild(row); });
    }

    function matchesFilters(row) {
      var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
      if (query && row.getAttribute('data-session-search').indexOf(query) === -1) {
        return false;
      }
      if (statusFilter && statusFilter.value && row.getAttribute('data-session-status') !== statusFilter.value) {
        return false;
      }
      if (skillFilter && skillFilter.value && row.getAttribute('data-session-skill') !== skillFilter.value) {
        return false;
      }
      if (dateFilter && dateFilter.value) {
        var rowDate = row.getAttribute('data-session-date-sort');
        if (dateFilter.value === 'upcoming' && rowDate < todayIso) {
          return false;
        }
        if (dateFilter.value === 'past' && rowDate >= todayIso) {
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
                applySessionsView();
              });
            })(p);
            paginationPagesEl.appendChild(btn);
          }
        }
      }
      paginationEl.hidden = total === 0;
    }

    window.applySessionsView = function applySessionsView() {
      sortRows();
      var rows = sessionRows();
      var matched = rows.filter(matchesFilters);
      var pageStart = (currentPage - 1) * PAGE_SIZE;
      var visibleSet = new Set(matched.slice(pageStart, pageStart + PAGE_SIZE));

      rows.forEach(function (row) {
        row.hidden = !visibleSet.has(row);
      });

      if (resultCountEl) {
        resultCountEl.textContent = matched.length + (matched.length === 1 ? ' session found' : ' sessions found');
      }
      if (emptyStateEl) {
        emptyStateEl.hidden = matched.length !== 0;
      }
      table.closest('.card').querySelector('.table-responsive').hidden = matched.length === 0;
      renderPagination(matched.length);
    };

    function resetToFirstPage() {
      currentPage = 1;
      applySessionsView();
    }

    if (searchInput) { searchInput.addEventListener('input', resetToFirstPage); }
    [statusFilter, skillFilter, dateFilter, sortSelect].forEach(function (control) {
      if (control) { control.addEventListener('change', resetToFirstPage); }
    });

    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-session-clear-filters]')) {
        event.preventDefault();
        if (searchInput) { searchInput.value = ''; }
        [statusFilter, skillFilter, dateFilter].forEach(function (control) {
          if (control) { control.value = ''; }
        });
        if (sortSelect) { sortSelect.value = 'newest'; }
        resetToFirstPage();
        return;
      }
      var summaryBtn = event.target.closest('[data-session-summary-filter]');
      if (summaryBtn && statusFilter) {
        statusFilter.value = summaryBtn.getAttribute('data-session-summary-filter');
        resetToFirstPage();
      }
    });

    applySessionsView();
  })();

  /* =====================================================================
     Posts (admin/posts.php)
     ===================================================================== */
  (function () {
    var table = document.querySelector('[data-post-table]');
    if (!table) {
      return;
    }

    var tbody = table.querySelector('tbody');

    function postRows() {
      return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-post-row]'));
    }

    /* ---- Hide / Restore (shared delete-confirmation modal) ---- */

    var deleteModal = document.getElementById('deleteConfirmationModal');
    var pendingAction = null; // { action: 'hide'|'restore', row }

    if (deleteModal) {
      deleteModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        pendingAction = null;
        if (!trigger) {
          return;
        }
        if (trigger.hasAttribute('data-post-hide')) {
          pendingAction = { action: 'hide', row: trigger.closest('[data-post-row]') };
        } else if (trigger.hasAttribute('data-post-restore')) {
          pendingAction = { action: 'restore', row: trigger.closest('[data-post-row]') };
        }
      });
    }

    function setPostStatus(row, status) {
      row.dataset.postStatus = status;
      var badge = row.querySelector('[data-post-status-badge]');
      if (badge) {
        badge.textContent = status === 'visible' ? 'Visible' : 'Hidden';
        badge.classList.remove('ukn-status-accent', 'ukn-status-neutral');
        badge.classList.add(status === 'visible' ? 'ukn-status-accent' : 'ukn-status-neutral');
      }
      var hideBtn = row.querySelector('[data-post-hide]');
      var restoreBtn = row.querySelector('[data-post-restore]');
      if (hideBtn) { hideBtn.hidden = status !== 'visible'; }
      if (restoreBtn) { restoreBtn.hidden = status === 'visible'; }

      var detailModalEl = document.getElementById('postDetailModal');
      if (detailModalEl && detailModalEl.dataset.currentPostId === row.getAttribute('data-post-id')) {
        var statusEl = detailModalEl.querySelector('[data-post-detail="status"]');
        if (statusEl) {
          statusEl.textContent = status === 'visible' ? 'Visible' : 'Hidden';
        }
      }
    }

    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-delete-confirm]') || !pendingAction) {
        return;
      }
      var action = pendingAction;
      pendingAction = null;
      setPostStatus(action.row, action.action === 'hide' ? 'hidden' : 'visible');
      if (typeof applyPostsView === 'function') {
        applyPostsView();
      }
    });

    /* ---- Post Details modal (no admin/post-details.php Admin page) ---- */

    var detailModal = document.getElementById('postDetailModal');
    if (detailModal) {
      detailModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var row = trigger ? trigger.closest('[data-post-row]') : null;
        if (!row) {
          return;
        }

        detailModal.dataset.currentPostId = row.getAttribute('data-post-id');

        var setText = function (key, value) {
          var el = detailModal.querySelector('[data-post-detail="' + key + '"]');
          if (el) {
            el.textContent = value || '—';
          }
        };
        setText('id', row.getAttribute('data-post-id'));
        setText('status', row.querySelector('[data-post-status-badge]').textContent);
        setText('title', row.getAttribute('data-post-title'));
        setText('excerpt', row.getAttribute('data-post-excerpt'));
        setText('posted', row.getAttribute('data-post-posted'));
        setText('votes', row.getAttribute('data-post-votes'));
        setText('comments', row.getAttribute('data-post-comments'));
        var reports = row.getAttribute('data-post-reports') || '0';
        setText('reports', reports === '0' ? '0' : reports + ' reports');

        var authorLink = detailModal.querySelector('[data-post-detail-link="author"]');
        if (authorLink) {
          authorLink.textContent = row.getAttribute('data-post-author-name');
          authorLink.href = 'user-details.php?id=' + row.getAttribute('data-post-author-id');
          authorLink.setAttribute('aria-label', 'View ' + row.getAttribute('data-post-author-name') + ' in Admin');
        }
        var appLink = detailModal.querySelector('[data-post-detail-link="app"]');
        if (appLink) {
          appLink.href = '../index.php?page=post-details&id=' + row.getAttribute('data-post-public-id');
        }

        var tagsWrap = detailModal.querySelector('[data-post-detail-tags]');
        if (tagsWrap) {
          var tags = (row.getAttribute('data-post-tags') || '').split('|').filter(Boolean);
          tagsWrap.innerHTML = tags.map(function (tag) {
            return '<span class="ukn-tag-neutral">' + escapeHtml(tag) + '</span>';
          }).join('');
        }

        var reasons = (row.getAttribute('data-post-report-reasons') || '').split('|').filter(Boolean);
        var reportsRow = detailModal.querySelector('[data-post-detail-reports-row]');
        if (reportsRow) {
          reportsRow.hidden = reasons.length === 0;
          if (reasons.length) {
            setText('reportReasons', reasons.join('; '));
          }
        }
      });
    }

    /* ---- Search / filter / sort / pagination ---- */

    var searchInput = document.querySelector('[data-post-search]');
    var statusFilter = document.querySelector('[data-post-filter="status"]');
    var reportedFilter = document.querySelector('[data-post-filter="reported"]');
    var skillFilter = document.querySelector('[data-post-filter="skill"]');
    var sortSelect = document.querySelector('[data-post-sort]');
    var resultCountEl = document.querySelector('[data-post-result-count]');
    var emptyStateEl = document.querySelector('[data-post-empty]');
    var paginationEl = document.querySelector('[data-post-pagination]');
    var paginationSummaryEl = document.querySelector('[data-post-pagination-summary]');
    var paginationPagesEl = document.querySelector('[data-post-pagination-pages]');
    var PAGE_SIZE = 10;
    var currentPage = 1;

    function sortRows() {
      var key = sortSelect ? sortSelect.value : 'newest';
      var rows = postRows();
      var sorted = rows.slice().sort(function (a, b) {
        switch (key) {
          case 'oldest':
            return parseInt(a.getAttribute('data-post-date-sort'), 10) - parseInt(b.getAttribute('data-post-date-sort'), 10);
          case 'most-reported':
            return parseInt(b.getAttribute('data-post-reports'), 10) - parseInt(a.getAttribute('data-post-reports'), 10);
          case 'most-discussed':
            return parseInt(b.getAttribute('data-post-comments'), 10) - parseInt(a.getAttribute('data-post-comments'), 10);
          case 'newest':
          default:
            return parseInt(b.getAttribute('data-post-date-sort'), 10) - parseInt(a.getAttribute('data-post-date-sort'), 10);
        }
      });
      sorted.forEach(function (row) { tbody.appendChild(row); });
    }

    function matchesFilters(row) {
      var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
      if (query && row.getAttribute('data-post-search').indexOf(query) === -1) {
        return false;
      }
      if (statusFilter && statusFilter.value && row.getAttribute('data-post-status') !== statusFilter.value) {
        return false;
      }
      if (reportedFilter && reportedFilter.value && row.getAttribute('data-post-reported') !== reportedFilter.value) {
        return false;
      }
      if (skillFilter && skillFilter.value) {
        var skills = (row.getAttribute('data-post-skills') || '').split('|');
        if (skills.indexOf(skillFilter.value) === -1) {
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
                applyPostsView();
              });
            })(p);
            paginationPagesEl.appendChild(btn);
          }
        }
      }
      paginationEl.hidden = total === 0;
    }

    window.applyPostsView = function applyPostsView() {
      sortRows();
      var rows = postRows();
      var matched = rows.filter(matchesFilters);
      var pageStart = (currentPage - 1) * PAGE_SIZE;
      var visibleSet = new Set(matched.slice(pageStart, pageStart + PAGE_SIZE));

      rows.forEach(function (row) {
        row.hidden = !visibleSet.has(row);
      });

      if (resultCountEl) {
        resultCountEl.textContent = matched.length + (matched.length === 1 ? ' post found' : ' posts found');
      }
      if (emptyStateEl) {
        emptyStateEl.hidden = matched.length !== 0;
      }
      table.closest('.card').querySelector('.table-responsive').hidden = matched.length === 0;
      renderPagination(matched.length);
    };

    function resetToFirstPage() {
      currentPage = 1;
      applyPostsView();
    }

    if (searchInput) { searchInput.addEventListener('input', resetToFirstPage); }
    [statusFilter, reportedFilter, skillFilter, sortSelect].forEach(function (control) {
      if (control) { control.addEventListener('change', resetToFirstPage); }
    });

    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-post-clear-filters]')) {
        event.preventDefault();
        if (searchInput) { searchInput.value = ''; }
        [statusFilter, reportedFilter, skillFilter].forEach(function (control) {
          if (control) { control.value = ''; }
        });
        if (sortSelect) { sortSelect.value = 'newest'; }
        resetToFirstPage();
        return;
      }
      var summaryBtn = event.target.closest('[data-post-summary-filter]');
      if (summaryBtn) {
        var parts = summaryBtn.getAttribute('data-post-summary-filter').split(':');
        var target = parts[0] === 'reported' ? reportedFilter : statusFilter;
        var otherFilter = parts[0] === 'reported' ? statusFilter : reportedFilter;
        if (target) { target.value = parts[1] || ''; }
        if (otherFilter) { otherFilter.value = ''; }
        resetToFirstPage();
      }
    });

    applyPostsView();
  })();

  /* =====================================================================
     Comments (admin/comments.php)
     ===================================================================== */
  (function () {
    var table = document.querySelector('[data-comment-table]');
    if (!table) {
      return;
    }

    var tbody = table.querySelector('tbody');

    function commentRows() {
      return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-comment-row]'));
    }

    /* ---- Hide / Restore (shared delete-confirmation modal) ---- */

    var deleteModal = document.getElementById('deleteConfirmationModal');
    var pendingAction = null;

    if (deleteModal) {
      deleteModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        pendingAction = null;
        if (!trigger) {
          return;
        }
        if (trigger.hasAttribute('data-comment-hide')) {
          pendingAction = { action: 'hide', row: trigger.closest('[data-comment-row]') };
        } else if (trigger.hasAttribute('data-comment-restore')) {
          pendingAction = { action: 'restore', row: trigger.closest('[data-comment-row]') };
        }
      });
    }

    function setCommentStatus(row, status) {
      row.dataset.commentStatus = status;
      var badge = row.querySelector('[data-comment-status-badge]');
      if (badge) {
        badge.textContent = status === 'visible' ? 'Visible' : 'Hidden';
        badge.classList.remove('ukn-status-accent', 'ukn-status-neutral');
        badge.classList.add(status === 'visible' ? 'ukn-status-accent' : 'ukn-status-neutral');
      }
      var hideBtn = row.querySelector('[data-comment-hide]');
      var restoreBtn = row.querySelector('[data-comment-restore]');
      if (hideBtn) { hideBtn.hidden = status !== 'visible'; }
      if (restoreBtn) { restoreBtn.hidden = status === 'visible'; }

      var detailModalEl = document.getElementById('commentDetailModal');
      if (detailModalEl && detailModalEl.dataset.currentCommentId === row.getAttribute('data-comment-id')) {
        var statusEl = detailModalEl.querySelector('[data-comment-detail="status"]');
        if (statusEl) {
          statusEl.textContent = status === 'visible' ? 'Visible' : 'Hidden';
        }
      }
    }

    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-delete-confirm]') || !pendingAction) {
        return;
      }
      var action = pendingAction;
      pendingAction = null;
      setCommentStatus(action.row, action.action === 'hide' ? 'hidden' : 'visible');
      if (typeof applyCommentsView === 'function') {
        applyCommentsView();
      }
    });

    /* ---- Comment Details modal (no admin/comment-details.php page) ---- */

    var detailModal = document.getElementById('commentDetailModal');
    if (detailModal) {
      detailModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var row = trigger ? trigger.closest('[data-comment-row]') : null;
        if (!row) {
          return;
        }

        detailModal.dataset.currentCommentId = row.getAttribute('data-comment-id');

        var setText = function (key, value) {
          var el = detailModal.querySelector('[data-comment-detail="' + key + '"]');
          if (el) {
            el.textContent = value || '—';
          }
        };
        setText('id', row.getAttribute('data-comment-id'));
        setText('status', row.querySelector('[data-comment-status-badge]').textContent);
        setText('text', row.getAttribute('data-comment-text'));
        setText('posted', row.getAttribute('data-comment-posted'));
        setText('post', row.getAttribute('data-comment-post-title'));
        setText('votes', row.getAttribute('data-comment-votes'));
        setText('replies', row.getAttribute('data-comment-replies'));
        var reports = row.getAttribute('data-comment-reports') || '0';
        setText('reports', reports === '0' ? '0' : reports + ' reports');

        var authorLink = detailModal.querySelector('[data-comment-detail-link="author"]');
        if (authorLink) {
          authorLink.textContent = row.getAttribute('data-comment-author-name');
          authorLink.href = 'user-details.php?id=' + row.getAttribute('data-comment-author-id');
          authorLink.setAttribute('aria-label', 'View ' + row.getAttribute('data-comment-author-name') + ' in Admin');
        }
        var appLink = detailModal.querySelector('[data-comment-detail-link="app"]');
        if (appLink) {
          appLink.href = '../index.php?page=post-details&id=' + row.getAttribute('data-comment-post-id');
        }

        var parentId = row.getAttribute('data-comment-parent-id');
        var parentRow = detailModal.querySelector('[data-comment-detail-parent-row]');
        if (parentRow) {
          parentRow.hidden = !parentId;
          if (parentId) {
            setText('parentAuthor', row.getAttribute('data-comment-parent-author'));
            setText('parentText', row.getAttribute('data-comment-parent-text'));
          }
        }

        var reasons = (row.getAttribute('data-comment-report-reasons') || '').split('|').filter(Boolean);
        var reportsRow = detailModal.querySelector('[data-comment-detail-reports-row]');
        if (reportsRow) {
          reportsRow.hidden = reasons.length === 0;
          if (reasons.length) {
            setText('reportReasons', reasons.join('; '));
          }
        }
      });
    }

    /* ---- Search / filter / sort / pagination ---- */

    var searchInput = document.querySelector('[data-comment-search]');
    var statusFilter = document.querySelector('[data-comment-filter="status"]');
    var reportedFilter = document.querySelector('[data-comment-filter="reported"]');
    var typeFilter = document.querySelector('[data-comment-filter="type"]');
    var sortSelect = document.querySelector('[data-comment-sort]');
    var resultCountEl = document.querySelector('[data-comment-result-count]');
    var emptyStateEl = document.querySelector('[data-comment-empty]');
    var paginationEl = document.querySelector('[data-comment-pagination]');
    var paginationSummaryEl = document.querySelector('[data-comment-pagination-summary]');
    var paginationPagesEl = document.querySelector('[data-comment-pagination-pages]');
    var PAGE_SIZE = 10;
    var currentPage = 1;

    function sortRows() {
      var key = sortSelect ? sortSelect.value : 'newest';
      var rows = commentRows();
      var sorted = rows.slice().sort(function (a, b) {
        switch (key) {
          case 'oldest':
            return parseInt(a.getAttribute('data-comment-date-sort'), 10) - parseInt(b.getAttribute('data-comment-date-sort'), 10);
          case 'most-reported':
            return parseInt(b.getAttribute('data-comment-reports'), 10) - parseInt(a.getAttribute('data-comment-reports'), 10);
          case 'most-voted':
            return parseInt(b.getAttribute('data-comment-votes'), 10) - parseInt(a.getAttribute('data-comment-votes'), 10);
          case 'newest':
          default:
            return parseInt(b.getAttribute('data-comment-date-sort'), 10) - parseInt(a.getAttribute('data-comment-date-sort'), 10);
        }
      });
      sorted.forEach(function (row) { tbody.appendChild(row); });
    }

    function matchesFilters(row) {
      var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
      if (query && row.getAttribute('data-comment-search').indexOf(query) === -1) {
        return false;
      }
      if (statusFilter && statusFilter.value && row.getAttribute('data-comment-status') !== statusFilter.value) {
        return false;
      }
      if (reportedFilter && reportedFilter.value && row.getAttribute('data-comment-reported') !== reportedFilter.value) {
        return false;
      }
      if (typeFilter && typeFilter.value && row.getAttribute('data-comment-type') !== typeFilter.value) {
        return false;
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
                applyCommentsView();
              });
            })(p);
            paginationPagesEl.appendChild(btn);
          }
        }
      }
      paginationEl.hidden = total === 0;
    }

    window.applyCommentsView = function applyCommentsView() {
      sortRows();
      var rows = commentRows();
      var matched = rows.filter(matchesFilters);
      var pageStart = (currentPage - 1) * PAGE_SIZE;
      var visibleSet = new Set(matched.slice(pageStart, pageStart + PAGE_SIZE));

      rows.forEach(function (row) {
        row.hidden = !visibleSet.has(row);
      });

      if (resultCountEl) {
        resultCountEl.textContent = matched.length + (matched.length === 1 ? ' comment found' : ' comments found');
      }
      if (emptyStateEl) {
        emptyStateEl.hidden = matched.length !== 0;
      }
      table.closest('.card').querySelector('.table-responsive').hidden = matched.length === 0;
      renderPagination(matched.length);
    };

    function resetToFirstPage() {
      currentPage = 1;
      applyCommentsView();
    }

    if (searchInput) { searchInput.addEventListener('input', resetToFirstPage); }
    [statusFilter, reportedFilter, typeFilter, sortSelect].forEach(function (control) {
      if (control) { control.addEventListener('change', resetToFirstPage); }
    });

    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-comment-clear-filters]')) {
        event.preventDefault();
        if (searchInput) { searchInput.value = ''; }
        [statusFilter, reportedFilter, typeFilter].forEach(function (control) {
          if (control) { control.value = ''; }
        });
        if (sortSelect) { sortSelect.value = 'newest'; }
        resetToFirstPage();
        return;
      }
      var summaryBtn = event.target.closest('[data-comment-summary-filter]');
      if (summaryBtn) {
        var parts = summaryBtn.getAttribute('data-comment-summary-filter').split(':');
        var target = parts[0] === 'reported' ? reportedFilter : statusFilter;
        var otherFilter = parts[0] === 'reported' ? statusFilter : reportedFilter;
        if (target) { target.value = parts[1] || ''; }
        if (otherFilter) { otherFilter.value = ''; }
        resetToFirstPage();
      }
    });

    applyCommentsView();
  })();
})();
