/**
 * Login / Register — page-specific frontend behavior only.
 * Generic required/email validation lives in assets/js/core/validation.js
 * and is reused as-is; this file only handles what's unique to the auth
 * pages:
 *   1. Show/Hide Password (any [data-toggle-password] control)
 *   2. Password strength guidance (Register only — frontend hint, not a
 *      real security check)
 *   3. Confirm Password matching (a cross-field check UKN.validateForm
 *      has no concept of)
 *   4. Mock submit feedback for both forms, and the Forgot Password
 *      placeholder action
 *
 * Frontend-only: no real authentication, no sessions, no persisted
 * credentials. Nothing here ever writes a password to localStorage,
 * a URL, or the console.
 */
(function () {
  'use strict';

  /* ---- 1. Show/Hide Password ---- */

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

  /* ---- 2. Password strength guidance (Register) ---- */

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

  /* ---- 3. Confirm Password matching ---- */

  function checkPasswordsMatch(form) {
    var password = form.querySelector('[data-password-strength-input]');
    var confirm = form.querySelector('[data-confirm-password-input]');
    if (!password || !confirm) {
      return true; // this form has no confirm-password field to check
    }
    if (!confirm.value) {
      return true; // required-empty case is UKN.validateForm's job
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

  /* ---- 4. Mock submit feedback ---- */

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-mock-auth-form]');
    if (!form) {
      return;
    }
    event.preventDefault(); // frontend-only mock — never a real login/registration

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

  /* ---- Forgot Password — no reset workflow exists yet; a safe,
     non-destructive placeholder is the only honest thing to show. ---- */

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
