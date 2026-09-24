(function () {
  'use strict';
  // Replies and comment edits are real forms (backend/comments/*.php). This file only shows /
  // hides the inline forms and blocks empty submissions; the server saves and re-renders.
  function toggleInlineForm(button, formSelector) {
    var holder = button.closest('[data-reply]') || button.closest('[data-comment]');
    var form = holder ? holder.querySelector(formSelector) : null;
    if (!form) {
      return;
    }
    form.hidden = !form.hidden;
    if (!form.hidden) {
      var textarea = form.querySelector('textarea');
      if (textarea) {
        textarea.focus();
      }
    }
  }
  document.addEventListener('click', function (event) {
    var toggleBtn = event.target.closest('[data-comment-reply-toggle]');
    if (toggleBtn) {
      toggleInlineForm(toggleBtn, '[data-reply-form]');
      return;
    }
    var editBtn = event.target.closest('[data-comment-edit-toggle]');
    if (editBtn) {
      toggleInlineForm(editBtn, '[data-comment-edit-form]');
      return;
    }
    var cancelBtn = event.target.closest('[data-reply-cancel]');
    if (cancelBtn) {
      var inlineForm = cancelBtn.closest('[data-reply-form], [data-comment-edit-form]');
      if (inlineForm) {
        inlineForm.hidden = true;
        var inlineError = inlineForm.querySelector('[data-reply-error]');
        if (inlineError) {
          inlineError.hidden = true;
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
    var form = event.target.closest('[data-reply-form], [data-comment-edit-form]');
    if (!form) {
      return;
    }
    var textarea = form.querySelector('textarea');
    var errorEl = form.querySelector('[data-reply-error]');
    var empty = !textarea || !textarea.value.trim();
    if (errorEl) {
      errorEl.hidden = !empty;
    }
    if (empty) {
      event.preventDefault();
    }
  });
})();
