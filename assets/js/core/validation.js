/**
 * Shared frontend-only validation utility for modal forms
 * (modals/create-post-modal.php, edit-post-modal.php,
 * session-request-modal.php, rating-modal.php) and the auth pages
 * (pages/auth/login.php, register.php — see assets/js/pages/auth.js).
 *
 * This module only exposes functions — it does not listen for 'submit'
 * itself (assets/js/core/modal.js and assets/js/pages/auth.js are the
 * two places that do, each calling UKN.validateForm() before deciding
 * whether to show the mock success flow). Keeping validation and
 * submit-handling in separate files, with only the form-specific file
 * bound to 'submit', avoids the duplicated-listener trap of two files
 * both reacting to the same event.
 *
 * Marking a field required / format-checked:
 *   - plain input/textarea/select: `data-validate="required"`, or
 *     `data-validate="required email"` to also check email format
 *     (space-separated rule list; "email" alone skips the required check)
 *   - a radio group (e.g. a star rating) or a single required checkbox
 *     (e.g. a Terms agreement — a lone checkbox is just a "group" of one):
 *     wrap it in an element with
 *     `data-validate-group="required" data-group-name="<the shared name>"`
 *
 * Each field/group should have a sibling `[data-error-for="<name>"]`
 * element (see .ukn-field-message in assets/css/forms.css) that this
 * shows/hides — the field's `name` attribute for plain fields, or
 * `data-group-name` for grouped ones. A field that can fail for more
 * than one reason (e.g. required vs. bad email format) can give that
 * message element `data-message-required="..."` / `data-message-email="..."`
 * attributes plus one inner `<span data-message-text>` — this swaps that
 * span's text to match whichever rule actually failed. A field with only
 * one possible failure reason (every pre-existing modal field) can skip
 * all of that and just keep its one static hardcoded message, exactly as
 * before.
 *
 * No backend validation happens anywhere — this only gates each mock
 * success flow.
 */
(function () {
  'use strict';

  var EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function isEmpty(field) {
    return !field.value || !field.value.trim();
  }

  function isValidEmail(value) {
    return EMAIL_PATTERN.test(value.trim());
  }

  /** Returns null when the field passes, or the name of the rule that failed. */
  function failingRule(field, rules) {
    if (rules.indexOf('required') !== -1 && isEmpty(field)) {
      return 'required';
    }
    if (rules.indexOf('email') !== -1 && !isEmpty(field) && !isValidEmail(field.value)) {
      return 'email';
    }
    return null;
  }

  /**
   * Shows/hides the `[data-error-for="name"]` message and toggles
   * `.is-invalid` on the field. `rule` (optional) picks which
   * `data-message-<rule>` text a multi-reason field should display —
   * see file docblock. Exposed as UKN.setFieldError so a page-specific
   * script (assets/js/pages/auth.js's Confirm Password match check) can
   * reuse the exact same error-display convention instead of a second
   * implementation of it.
   */
  function toggleError(form, name, isInvalid, invalidEl, rule) {
    var message = form.querySelector('[data-error-for="' + name + '"]');
    if (message) {
      if (isInvalid && rule) {
        var text = message.getAttribute('data-message-' + rule);
        if (text) {
          var textEl = message.querySelector('[data-message-text]');
          if (textEl) {
            textEl.textContent = text;
          }
        }
      }
      message.hidden = !isInvalid;
    }
    if (invalidEl && invalidEl.classList) {
      invalidEl.classList.toggle('is-invalid', isInvalid);
    }
  }

  function validateForm(form) {
    var valid = true;

    form.querySelectorAll('[data-validate]').forEach(function (field) {
      var rules = field.getAttribute('data-validate').split(/\s+/);
      var rule = failingRule(field, rules);
      toggleError(form, field.name, !!rule, field, rule);
      if (rule) {
        valid = false;
      }
    });

    form.querySelectorAll('[data-validate-group="required"]').forEach(function (group) {
      var name = group.getAttribute('data-group-name');
      var hasSelection = !!form.querySelector('input[name="' + name + '"]:checked');
      toggleError(form, name, !hasSelection, null);
      if (!hasSelection) {
        valid = false;
      }
    });

    return valid;
  }

  // Clear a field's error the moment the user fixes it, rather than only
  // re-checking on the next submit attempt.
  document.addEventListener('input', function (event) {
    var form = event.target.closest('[data-mock-form], [data-mock-auth-form]');
    var field = event.target;
    if (!form || !field.matches('[data-validate]')) {
      return;
    }
    var rules = field.getAttribute('data-validate').split(/\s+/);
    if (!failingRule(field, rules)) {
      toggleError(form, field.name, false, field);
    }
  });

  document.addEventListener('change', function (event) {
    var form = event.target.closest('[data-mock-form], [data-mock-auth-form]');
    if (!form || (event.target.type !== 'radio' && event.target.type !== 'checkbox')) {
      return;
    }
    var group = event.target.closest('[data-validate-group="required"]');
    if (group) {
      toggleError(form, group.getAttribute('data-group-name'), false, null);
    }
  });

  window.UKN = window.UKN || {};
  window.UKN.validateForm = validateForm;
  window.UKN.setFieldError = toggleError;
})();
