(function () {
  'use strict';
  // Replies and comment edits are real forms (backend/comments/*.php). This file only shows /
  // hides the inline forms, blocks empty submissions and fills the report modal; the server
  // saves and re-renders.
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
  });
  // Report a post or comment: the trigger names the target; the form POSTs to
  // backend/reports/create.php, which re-checks everything and flashes the result.
  var reportModal = document.getElementById('reportModal');
  if (reportModal) {
    reportModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      var form = reportModal.querySelector('[data-report-form]');
      if (!trigger || !form || !trigger.hasAttribute('data-report-target-type')) {
        return;
      }
      var type = trigger.getAttribute('data-report-target-type');
      form.reset();
      form.querySelector('[data-report-target-type-input]').value = type;
      form.querySelector('[data-report-target-id-input]').value = trigger.getAttribute('data-report-target-id') || '';
      reportModal.querySelector('[data-report-target-label]').textContent = type;
      form.querySelectorAll('[data-error-for]').forEach(function (message) {
        message.hidden = true;
      });
      form.querySelectorAll('.is-invalid[data-validate]').forEach(function (field) {
        field.classList.remove('is-invalid');
      });
    });
  }
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
