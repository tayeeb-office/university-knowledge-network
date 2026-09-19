/**
 * My Profile / Edit Profile — page-specific frontend behavior only.
 * Follow toggling (learner-profile.php, mentor-profile.php) already reuses
 * the generic assets/js/components/follow.js; Request Session already
 * reuses the shared assets/js/core/modal.js + modals/session-request-modal.php;
 * the skill tag picker on Edit Profile already reuses modal.js's generic
 * [data-skill-picker] listeners. None of that is duplicated here — this
 * file only handles what's unique to Edit Profile:
 *   1. Bio character count
 *   2. Profile photo preview (temporary browser state only — never
 *      localStorage, never uploaded anywhere)
 *   3. The mock "Save Changes" -> validate -> toast flow
 *
 * #editProfileForm intentionally does NOT use [data-mock-form] (see that
 * file's docblock) — assets/js/core/modal.js's generic handler resets the
 * form and wipes every skill tag on submit, which is correct for a modal
 * that's about to close but would erase this page's pre-filled skills the
 * moment Save Changes is clicked. Frontend-only: no real persistence.
 */
(function () {
  'use strict';

  /* ---- 1. Bio character count ---- */

  document.addEventListener('input', function (event) {
    var field = event.target.closest('[data-bio-char-input]');
    if (!field) {
      return;
    }
    var group = field.closest('.ukn-form-group');
    var counter = group ? group.querySelector('[data-bio-char-count]') : null;
    if (counter) {
      counter.textContent = String(field.value.length);
    }
  });

  /* ---- 2. Profile photo preview ---- */

  var photoObjectUrl = null;

  function showPhotoPreview(avatar, file) {
    if (photoObjectUrl) {
      URL.revokeObjectURL(photoObjectUrl);
    }
    photoObjectUrl = URL.createObjectURL(file);

    avatar.textContent = '';
    var img = document.createElement('img');
    img.src = photoObjectUrl;
    img.alt = '';
    avatar.appendChild(img);
  }

  function resetPhotoPreview(avatar) {
    if (photoObjectUrl) {
      URL.revokeObjectURL(photoObjectUrl);
      photoObjectUrl = null;
    }
    avatar.textContent = avatar.getAttribute('data-photo-initials') || '';
  }

  document.addEventListener('click', function (event) {
    var uploadTrigger = event.target.closest('[data-photo-upload-trigger]');
    if (uploadTrigger) {
      var input = document.querySelector('[data-photo-input]');
      if (input) {
        input.click();
      }
      return;
    }

    var removeTrigger = event.target.closest('[data-photo-remove-trigger]');
    if (removeTrigger) {
      var avatar = document.querySelector('[data-photo-preview]');
      var fileInput = document.querySelector('[data-photo-input]');
      if (avatar) {
        resetPhotoPreview(avatar);
      }
      if (fileInput) {
        fileInput.value = '';
      }
    }
  });

  document.addEventListener('change', function (event) {
    var input = event.target.closest('[data-photo-input]');
    if (!input || !input.files || !input.files[0]) {
      return;
    }
    var avatar = document.querySelector('[data-photo-preview]');
    if (avatar) {
      showPhotoPreview(avatar, input.files[0]);
    }
  });

  /* ---- 3. Mock "Save Changes" ---- */

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-profile-form]');
    if (!form) {
      return;
    }
    event.preventDefault(); // frontend-only mock — never a real save

    var isValid = !(window.UKN && window.UKN.validateForm) || window.UKN.validateForm(form);
    if (!isValid) {
      return;
    }

    var message = form.getAttribute('data-success-message') || 'Saved successfully.';
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(message, 'success');
    }
  });
})();
