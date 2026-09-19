/**
 * Settings — page-specific frontend behavior only.
 *   1. Local settings nav scroll-spy (plain in-page anchors — not a
 *      second global sidebar; just tracks which section is in view)
 *   2. Appearance radios — read/write the existing global
 *      window.UKN.theme singleton (assets/js/core/theme.js); no second
 *      theme state, and resyncs if the theme changes from elsewhere
 *      (e.g. the header toggle) while this page is open
 *   3. Mock "Change Email" feedback (no real email workflow exists)
 *   4. Change Password modal validation — required fields via the same
 *      window.UKN.validateForm every other modal uses, plus a New/Confirm
 *      match check mirroring pages/auth/register.php's own Confirm
 *      Password check (that page's listener is scoped to its own form,
 *      so this is a small, separate implementation of the same idea)
 *   5. Notification/Privacy toggles — enables Save/Reset once anything
 *      changes; Save shows a mock success toast, Reset restores whatever
 *      was checked when the page loaded
 *
 * Deliberately NOT here: the theme engine itself, the role-switch modal,
 * the delete-confirmation modal's generic confirm behavior (Deactivate
 * Account reuses that modal's existing document-wide handler as-is) —
 * all already exist and are only referenced from here, never reimplemented.
 */
(function () {
  'use strict';

  var page = document.querySelector('[data-settings-nav]');
  if (!page) {
    return;
  }

  /* ---- 1. Local nav scroll-spy ---- */

  var navLinks = Array.prototype.slice.call(document.querySelectorAll('[data-settings-nav-link]'));
  var sections = Array.prototype.slice.call(document.querySelectorAll('[data-settings-section]'));

  if (sections.length && navLinks.length && 'IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        navLinks.forEach(function (link) {
          link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id);
        });
      });
    }, { rootMargin: '-15% 0px -70% 0px' });
    sections.forEach(function (section) { observer.observe(section); });
  }

  /* ---- 2. Appearance (Light/Dark) ---- */

  var themeRadios = document.querySelectorAll('[data-theme-setting] input[name="appearanceTheme"]');

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

  /* ---- 3. Mock Change Email ---- */

  var changeEmailBtn = document.querySelector('[data-mock-email-change]');
  if (changeEmailBtn) {
    changeEmailBtn.addEventListener('click', function () {
      if (window.UKN && window.UKN.showToast) {
        window.UKN.showToast("Email changes aren't available in demo mode.", 'info');
      }
    });
  }

  /* ---- 4. Change Password ---- */

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

      passwordForm.reset();

      var modalEl = passwordForm.closest('.modal');
      if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        var instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.hide();
      }
    });
  }

  /* ---- 5. Notification / Privacy toggles — Save / Reset ---- */

  var toggles = Array.prototype.slice.call(document.querySelectorAll('[data-settings-toggle]'));
  var saveBtn = document.querySelector('[data-settings-save]');
  var resetBtn = document.querySelector('[data-settings-reset]');
  var initialState = toggles.map(function (t) { return t.checked; });

  function hasChanges() {
    return toggles.some(function (t, i) { return t.checked !== initialState[i]; });
  }

  function refreshSaveState() {
    var changed = hasChanges();
    if (saveBtn) { saveBtn.disabled = !changed; }
    if (resetBtn) { resetBtn.disabled = !changed; }
  }

  toggles.forEach(function (toggle) {
    toggle.addEventListener('change', refreshSaveState);
  });

  if (saveBtn) {
    saveBtn.addEventListener('click', function () {
      initialState = toggles.map(function (t) { return t.checked; });
      refreshSaveState();
      if (window.UKN && window.UKN.showToast) {
        window.UKN.showToast('Settings updated.', 'success');
      }
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      toggles.forEach(function (toggle, i) { toggle.checked = initialState[i]; });
      refreshSaveState();
    });
  }
})();
