(function () {
  'use strict';
  var form = document.querySelector('[data-admin-settings-form]');
  if (!form) {
    return;
  }
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
        return true;
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
      event.preventDefault();
      var requiredValid = window.UKN && window.UKN.validateForm ? window.UKN.validateForm(passwordForm) : true;
      var matchOk = checkPasswordsMatch();
      if (!requiredValid || !matchOk) {
        return;
      }
      if (window.UKN && window.UKN.showToast) {
        window.UKN.showToast('Password change validated in demo mode. No account data was changed.', 'success');
      }
      passwordForm.reset();
      var modalEl = passwordForm.closest('.modal');
      if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        var instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.hide();
      }
    });
  }
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
    event.preventDefault();
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