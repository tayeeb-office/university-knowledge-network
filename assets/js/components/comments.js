(function () {
  'use strict';
  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
  }
  function buildCommentCard(data) {
    var wrap = document.createElement('div');
    wrap.setAttribute('data-comment', '');
    wrap.className = 'mb-2';
    wrap.innerHTML =
      '<div class="card">' +
        '<div class="card-body">' +
          '<div class="ukn-cluster mb-2">' +
            '<span class="ukn-avatar ukn-avatar-sm" aria-hidden="true">' + escapeHtml(data.initials) + '</span>' +
            '<div>' +
              '<strong class="text-body">' + escapeHtml(data.name) + '</strong> ' +
              '<span class="ukn-role-chip">' + escapeHtml(data.role) + '</span>' +
              '<div class="ukn-body-sm">' + escapeHtml(data.time) + '</div>' +
            '</div>' +
          '</div>' +
          '<p class="ukn-body-sm mb-2">' + escapeHtml(data.text) + '</p>' +
          '<div class="d-flex align-items-center gap-3 flex-wrap">' +
            '<button type="button" class="btn-ghost" data-comment-reply-toggle>Reply</button>' +
            '<button type="button" class="btn-ghost" data-comment-report>Report</button>' +
          '</div>' +
          '<form data-reply-form hidden class="mt-3" novalidate>' +
            '<label class="ukn-visually-hidden">Reply to ' + escapeHtml(data.name) + '</label>' +
            '<textarea class="form-control form-control-sm" placeholder="Write a reply..."></textarea>' +
            '<div class="ukn-field-message is-invalid mt-1" data-reply-error hidden><span class="ms" aria-hidden="true">error</span>Write a reply before posting.</div>' +
            '<div class="d-flex gap-2 mt-2">' +
              '<button type="button" class="btn btn-outline-secondary btn-sm" data-reply-cancel>Cancel</button>' +
              '<button type="submit" class="btn btn-primary btn-sm">Reply</button>' +
            '</div>' +
          '</form>' +
        '</div>' +
      '</div>' +
      '<div data-replies-list class="mt-2"></div>';
    return wrap;
  }
  function buildReplyCard(data) {
    var wrap = document.createElement('div');
    wrap.setAttribute('data-reply', '');
    wrap.className = 'card mt-2 ms-2 ms-md-4';
    wrap.innerHTML =
      '<div class="card-body">' +
        '<div class="ukn-cluster mb-2">' +
          '<span class="ukn-avatar ukn-avatar-sm" aria-hidden="true">' + escapeHtml(data.initials) + '</span>' +
          '<div>' +
            '<strong class="text-body">' + escapeHtml(data.name) + '</strong> ' +
            '<span class="ukn-role-chip">' + escapeHtml(data.role) + '</span>' +
            '<div class="ukn-body-sm">' + escapeHtml(data.time) + '</div>' +
          '</div>' +
        '</div>' +
        '<p class="ukn-body-sm mb-0">' + escapeHtml(data.text) + '</p>' +
      '</div>';
    return wrap;
  }
  window.UKN = window.UKN || {};
  window.UKN.buildCommentCard = buildCommentCard;
  window.UKN.buildReplyCard = buildReplyCard;
  document.addEventListener('click', function (event) {
    var toggleBtn = event.target.closest('[data-comment-reply-toggle]');
    if (toggleBtn) {
      var comment = toggleBtn.closest('[data-comment]');
      var form = comment ? comment.querySelector('[data-reply-form]') : null;
      if (form) {
        form.hidden = !form.hidden;
        if (!form.hidden) {
          var textarea = form.querySelector('textarea');
          if (textarea) {
            textarea.focus();
          }
        }
      }
      return;
    }
    var cancelBtn = event.target.closest('[data-reply-cancel]');
    if (cancelBtn) {
      var replyForm = cancelBtn.closest('[data-reply-form]');
      if (replyForm) {
        replyForm.hidden = true;
        var replyTextarea = replyForm.querySelector('textarea');
        if (replyTextarea) {
          replyTextarea.value = '';
        }
        var replyError = replyForm.querySelector('[data-reply-error]');
        if (replyError) {
          replyError.hidden = true;
        }
      }
      return;
    }
    var reportBtn = event.target.closest('[data-comment-report]');
    if (reportBtn && window.UKN && window.UKN.showToast) {
      window.UKN.showToast('Thanks — this comment has been reported for review.', 'info');
    }
  });
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-reply-form]');
    if (!form) {
      return;
    }
    event.preventDefault();
    var textarea = form.querySelector('textarea');
    var errorEl = form.querySelector('[data-reply-error]');
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
    var comment = form.closest('[data-comment]');
    var repliesList = comment ? comment.querySelector('[data-replies-list]') : null;
    var composer = document.querySelector('[data-comment-composer]');
    if (repliesList) {
      repliesList.appendChild(window.UKN.buildReplyCard({
        initials: composer ? composer.getAttribute('data-current-user-initials') : 'NR',
        name: composer ? composer.getAttribute('data-current-user-name') : 'Nabila Rahman',
        role: 'Learner',
        time: 'Just now',
        text: text,
      }));
    }
    textarea.value = '';
    form.hidden = true;
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast('Reply added.', 'success');
    }
  });
})();