(function () {
  'use strict';
  var EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  function isEmpty(field) {
    return !field.value || !field.value.trim();
  }
  function isValidEmail(value) {
    return EMAIL_PATTERN.test(value.trim());
  }
  function failingRule(field, rules) {
    if (rules.indexOf('required') !== -1 && isEmpty(field)) {
      return 'required';
    }
    if (rules.indexOf('email') !== -1 && !isEmpty(field) && !isValidEmail(field.value)) {
      return 'email';
    }
    return null;
  }
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
  document.addEventListener('input', function (event) {
    var form = event.target.closest('[data-mock-form], [data-auth-form], [data-validated-form]');
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
    var form = event.target.closest('[data-mock-form], [data-auth-form], [data-validated-form]');
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