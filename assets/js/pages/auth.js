(function () {
  'use strict';
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-toggle-password]');
    if (!btn) {
      return;
    }
    var field = btn.parentElement ? btn.parentElement.querySelector('input') : null;
    if (!field) {
      return;
    }
    var willShow = field.type === 'password';
    field.type = willShow ? 'text' : 'password';
    var icon = btn.querySelector('.ms');
    if (icon) {
      icon.textContent = willShow ? 'visibility_off' : 'visibility';
    }
    btn.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
    btn.setAttribute('aria-pressed', willShow ? 'true' : 'false');
  });
  function strengthLevel(value) {
    var score = 0;
    if (value.length >= 8) {
      score++;
    }
    if (/[0-9]/.test(value)) {
      score++;
    }
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
      score++;
    }
    if (score <= 1) {
      return 'weak';
    }
    return score === 2 ? 'medium' : 'strong';
  }
  document.addEventListener('input', function (event) {
    var field = event.target.closest('[data-password-strength-input]');
    if (!field) {
      return;
    }
    var meter = field.closest('.ukn-form-group');
    meter = meter ? meter.querySelector('[data-password-strength]') : null;
    if (!meter) {
      return;
    }
    if (!field.value) {
      meter.hidden = true;
      return;
    }

    var level = strengthLevel(field.value);
    meter.hidden = false;
    meter.setAttribute('data-level', level);
    var label = meter.querySelector('[data-password-strength-label]');
    if (label) {
      label.textContent = level.charAt(0).toUpperCase() + level.slice(1);
    }
  });

  function checkPasswordsMatch(form) {
    var password = form.querySelector('[data-password-strength-input]');
    var confirm = form.querySelector('[data-confirm-password-input]');
    if (!password || !confirm) {
      return true;
    }
    if (!confirm.value) {
      return true;
    }
    var mismatch = password.value !== confirm.value;
    if (window.UKN && window.UKN.setFieldError) {
      window.UKN.setFieldError(form, confirm.name, mismatch, confirm, mismatch ? 'mismatch' : null);
    }
    return !mismatch;
  }
  document.addEventListener('input', function (event) {
    var confirm = event.target.closest('[data-confirm-password-input]');
    if (!confirm) {
      return;
    }
    var form = confirm.closest('[data-mock-auth-form]');
    if (form) {
      checkPasswordsMatch(form);
    }
  });
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-mock-auth-form]');
    if (!form) {
      return;
    }
    event.preventDefault();
    var kind = form.getAttribute('data-mock-auth-form');
    var requiredValid = window.UKN && window.UKN.validateForm ? window.UKN.validateForm(form) : true;
    var passwordsOk = kind === 'register' ? checkPasswordsMatch(form) : true;
    if (!requiredValid || !passwordsOk) {
      return;
    }
    var message = form.getAttribute('data-success-message') || 'Success. Demo mode only.';
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(message, 'success');
    }
    var continueLink = form.querySelector('[data-continue-to-login]');
    if (continueLink) {
      continueLink.hidden = false;
    }
    form.reset();
    var strengthMeter = form.querySelector('[data-password-strength]');
    if (strengthMeter) {
      strengthMeter.hidden = true;
    }
  });
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-forgot-password]');
    if (!btn) {
      return;
    }
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast('Password reset is not available in this demo yet.', 'info');
    }
  });
})();