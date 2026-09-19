/**
 * Admin Settings — page-specific frontend behavior only. Local section
 * navigation needs no JS at all (plain Bootstrap .nav-tabs/.tab-pane,
 * switched by bootstrap.bundle.min.js's own data-bs-toggle="tab"); this
 * file only covers what Bootstrap has no opinion on:
 *   1. Appearance radios — read/write the existing global
 *      window.UKN.theme singleton (assets/js/core/theme.js), the exact
 *      same small pattern assets/js/pages/settings.js already uses for
 *      the user-facing Settings page's own Appearance section. No second
 *      theme state is created, and Appearance never goes through Save/
 *      Reset below — it applies immediately, like the header toggle.
 *   2. Change Password modal — the same shared modals/
 *      change-password-modal.php pages/settings/settings.php already
 *      uses, paired here with the same small required-fields +
 *      New/Confirm match validation that modal's own docblock already
 *      anticipates a second page re-implementing (frontend-only: no
 *      password value is ever read back out of the fields or stored).
 *   3. Show/Hide Password — the same tiny [data-toggle-password] behavior
 *      assets/js/pages/auth.js already has, reimplemented in these few
 *      lines rather than loading that whole unrelated page script here
 *      just to reuse one small generic DOM helper.
 *   4. All other Admin Settings fields (every [data-admin-settings-field]
 *      toggle/select/input) — a generic "did anything change from the
 *      last saved snapshot" dirty check driving Save/Reset, plus
 *      window.UKN.validateForm() for the required/email fields and two
 *      small extra checks validateForm has no concept of (Report
 *      Threshold's numeric range, Minimum <= Maximum session duration).
 *      Save only updates this file's own in-memory snapshot — there is
 *      no backend, no localStorage, nothing written for these fields.
 *
 * Guarded so this only ever runs on admin/settings.php; every other
 * Admin page loading assets/js/admin/*.js never touches this file at all.
 */
(function () {
  'use strict';

  var form = document.querySelector('[data-admin-settings-form]');
  if (!form) {
    return;
  }

  /* ---- 1. Appearance (Light/Dark) — same pattern as assets/js/pages/settings.js ---- */

  var themeRadios = document.querySelectorAll('[data-admin-theme-setting] input[name="adminAppearanceTheme"]');

  function syncThemeRadios() {
    if (!window.UKN || !window.UKN.theme) {
      return;
    }
    var current = window.UKN.theme.current();
    themeRadios.forEach(function (radio) {
      radio.checked = radio.value === current;
    });
  }

  themeRadios.forEach(function (radio) {
    radio.addEventListener('change', function () {
      if (radio.checked && window.UKN && window.UKN.theme) {
        window.UKN.theme.set(radio.value);
      }
    });
  });

  syncThemeRadios();
  document.addEventListener('ukn:themechange', syncThemeRadios);

  /* ---- 2 & 3. Change Password modal ---- */

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
  });

  var passwordForm = document.querySelector('[data-change-password-form]');
  if (passwordForm) {
    var checkPasswordsMatch = function () {
      var newPassword = passwordForm.querySelector('[name="newPassword"]');
      var confirmField = passwordForm.querySelector('[data-confirm-new-password-input]');
      if (!newPassword || !confirmField || !confirmField.value) {
        return true; // required-empty case is UKN.validateForm's job
      }
      var mismatch = newPassword.value !== confirmField.value;
      if (window.UKN && window.UKN.setFieldError) {
        window.UKN.setFieldError(passwordForm, confirmField.name, mismatch, confirmField, mismatch ? 'mismatch' : null);
      }
      return !mismatch;
    };

    passwordForm.addEventListener('input', function (event) {
      if (event.target.name === 'newPassword' || event.target.name === 'confirmNewPassword') {
        checkPasswordsMatch();
      }
    });

    passwordForm.addEventListener('submit', function (event) {
      event.preventDefault(); // frontend-only mock — never a real password change

      var requiredValid = window.UKN && window.UKN.validateForm ? window.UKN.validateForm(passwordForm) : true;
      var matchOk = checkPasswordsMatch();
      if (!requiredValid || !matchOk) {
        return;
      }

      if (window.UKN && window.UKN.showToast) {
        window.UKN.showToast('Password change validated in demo mode. No account data was changed.', 'success');
      }

      passwordForm.reset(); // clears every password field immediately — nothing is ever read back out

      var modalEl = passwordForm.closest('.modal');
      if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        var instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.hide();
      }
    });
  }

  /* ---- 4. Settings fields — dirty state, validation, Save, Reset ---- */

  var fields = Array.prototype.slice.call(form.querySelectorAll('[data-admin-settings-field]'));
  var saveBtn = document.querySelector('[data-admin-settings-save]');
  var resetBtn = document.querySelector('[data-admin-settings-reset]');

  function fieldValue(field) {
    return field.type === 'checkbox' ? field.checked : field.value;
  }

  function applyFieldValue(field, value) {
    if (field.type === 'checkbox') {
      field.checked = value;
    } else {
      field.value = value;
    }
  }

  var savedSnapshot = fields.map(fieldValue);

  function hasChanges() {
    return fields.some(function (field, i) { return fieldValue(field) !== savedSnapshot[i]; });
  }

  function refreshButtonState() {
    var changed = hasChanges();
    if (saveBtn) { saveBtn.disabled = !changed; }
    if (resetBtn) { resetBtn.disabled = !changed; }
  }

  fields.forEach(function (field) {
    field.addEventListener(field.tagName === 'SELECT' || field.type === 'checkbox' ? 'change' : 'input', refreshButtonState);
  });

  function validateSessionRange() {
    var min = form.querySelector('#minSessionDuration');
    var max = form.querySelector('#maxSessionDuration');
    if (!min || !max) {
      return true;
    }
    var invalid = parseInt(min.value, 10) > parseInt(max.value, 10);
    if (window.UKN && window.UKN.setFieldError) {
      window.UKN.setFieldError(form, 'maxSessionDuration', invalid, max);
    }
    return !invalid;
  }

  function validateReportThreshold() {
    var field = form.querySelector('#reportThreshold');
    if (!field) {
      return true;
    }
    var value = parseInt(field.value, 10);
    var invalid = !Number.isInteger(value) || value < 1 || value > 20;
    if (window.UKN && window.UKN.setFieldError) {
      window.UKN.setFieldError(form, 'reportThreshold', invalid, field);
    }
    return !invalid;
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault(); // frontend demo only — never a real settings save

    var requiredValid = window.UKN && window.UKN.validateForm ? window.UKN.validateForm(form) : true;
    var rangeValid = validateSessionRange();
    var thresholdValid = validateReportThreshold();
    if (!requiredValid || !rangeValid || !thresholdValid) {
      return;
    }

    savedSnapshot = fields.map(fieldValue);
    refreshButtonState();

    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast('Settings updated in demo mode.', 'success');
    }
  });

  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      fields.forEach(function (field, i) { applyFieldValue(field, savedSnapshot[i]); });
      validateSessionRange();
      validateReportThreshold();
      refreshButtonState();
    });
  }
})();
