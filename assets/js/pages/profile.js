(function () {
  'use strict';
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
  var photoObjectUrl = null;
  // Profile photo: the file input and remove_avatar are submitted with the profile form
  // (backend/profile/update.php validates the file again). A newly chosen file wins over Remove.
  var PHOTO_TYPES = ['image/jpeg', 'image/png'];
  var PHOTO_MAX_BYTES = 2 * 1024 * 1024;
  function setPhotoRemoveFlag(value) {
    var flag = document.querySelector('[data-photo-remove-flag]');
    if (flag) {
      flag.value = value;
    }
  }

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
      setPhotoRemoveFlag('1');
    }
  });
  document.addEventListener('change', function (event) {
    var input = event.target.closest('[data-photo-input]');
    if (!input || !input.files || !input.files[0]) {
      return;
    }
    var file = input.files[0];
    if (PHOTO_TYPES.indexOf(file.type) === -1 || file.size > PHOTO_MAX_BYTES) {
      input.value = '';
      if (window.UKN && window.UKN.showToast) {
        window.UKN.showToast('Choose a JPG or PNG image of 2 MB or less.', 'danger');
      }
      return;
    }
    setPhotoRemoveFlag('');
    var avatar = document.querySelector('[data-photo-preview]');
    if (avatar) {
      showPhotoPreview(avatar, file);
    }
  });
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-profile-form]');
    if (!form) {
      return;
    }
    // Posts to backend/profile/update.php; only block clearly invalid input here.
    var isValid = !(window.UKN && window.UKN.validateForm) || window.UKN.validateForm(form);
    if (!isValid) {
      event.preventDefault();
    }
  });
})();