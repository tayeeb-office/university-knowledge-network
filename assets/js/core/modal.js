(function () {
  'use strict';
  function closeParentModal(el) {
    var modalEl = el.closest('.modal');
    if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      return;
    }
    var instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
    instance.hide();
  }
  function resetSkillPicker(form) {
    form.querySelectorAll('[data-skill-picker]').forEach(function (picker) {
      picker.querySelectorAll('[data-skill-tag]').forEach(function (tag) {
        tag.remove();
      });
      var hidden = picker.parentElement.querySelector('[data-skill-value]');
      if (hidden) {
        hidden.value = '';
      }
      var textInput = picker.querySelector('[data-skill-input]');
      if (textInput) {
        textInput.value = '';
      }
    });
  }
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-mock-form]');
    if (!form) {
      return;
    }
    event.preventDefault();
    var isValid = !(window.UKN && window.UKN.validateForm) || window.UKN.validateForm(form);
    if (!isValid) {
      return;
    }

    var message = form.getAttribute('data-success-message') || 'Saved successfully.';
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(message, 'success');
    }
    closeParentModal(form);
    window.setTimeout(function () {
      form.reset();
      resetSkillPicker(form);
    }, 300);
  });
  // Real server forms inside modals (session request, rating): check required fields first,
  // otherwise let the browser POST normally.
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-validated-form]');
    if (!form) {
      return;
    }
    // Create/Edit Post: a skill typed but not yet confirmed with Enter/comma is added exactly as
    // Enter would add it; the server still checks every name against the skill directory.
    form.querySelectorAll('[data-skill-picker]').forEach(commitPendingSkill);
    if (window.UKN && window.UKN.validateForm && !window.UKN.validateForm(form)) {
      event.preventDefault();
    }
  });
  function commitPendingSkill(picker) {
    var textInput = picker.querySelector('[data-skill-input]');
    var hiddenInput = picker.parentElement.querySelector('[data-skill-value]');
    if (!textInput || !hiddenInput) {
      return;
    }
    addSkillTag(picker, hiddenInput, textInput.value);
    textInput.value = '';
  }
  function currentSkills(hiddenInput) {
    return hiddenInput.value ? hiddenInput.value.split('|').filter(Boolean) : [];
  }
  function addSkillTag(picker, hiddenInput, value) {
    value = value.trim();
    if (!value) {
      return;
    }
    var skills = currentSkills(hiddenInput);
    if (skills.indexOf(value) !== -1) {
      return;
    }
    skills.push(value);
    hiddenInput.value = skills.join('|');
    var tag = document.createElement('button');
    tag.type = 'button';
    tag.className = 'ukn-tag-skill';
    tag.setAttribute('data-skill-tag', value);
    tag.setAttribute('aria-label', 'Remove ' + value);
    tag.appendChild(document.createTextNode(value + ' '));
    var icon = document.createElement('span');
    icon.className = 'ms';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = 'close';
    tag.appendChild(icon);
    var textInput = picker.querySelector('[data-skill-input]');
    picker.insertBefore(tag, textInput);
  }
  function removeSkillTag(tag, hiddenInput) {
    var skills = currentSkills(hiddenInput);
    var index = skills.indexOf(tag.getAttribute('data-skill-tag'));
    if (index !== -1) {
      skills.splice(index, 1);
    }
    hiddenInput.value = skills.join('|');
    tag.remove();
  }
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' && event.key !== ',') {
      return;
    }
    var input = event.target.closest('[data-skill-input]');
    if (!input) {
      return;
    }
    event.preventDefault();
    var picker = input.closest('[data-skill-picker]');
    var hiddenInput = picker.parentElement.querySelector('[data-skill-value]');
    if (!hiddenInput) {
      return;
    }
    addSkillTag(picker, hiddenInput, input.value);
    input.value = '';
    var form = picker.closest('[data-mock-form], [data-validated-form]');
    if (form) {
      var message = form.querySelector('[data-error-for="' + hiddenInput.name + '"]');
      if (message) {
        message.hidden = true;
      }
    }
  });
  document.addEventListener('click', function (event) {
    var tag = event.target.closest('[data-skill-tag]');
    if (!tag) {
      return;
    }
    var picker = tag.closest('[data-skill-picker]');
    var hiddenInput = picker.parentElement.querySelector('[data-skill-value]');
    if (hiddenInput) {
      removeSkillTag(tag, hiddenInput);
    }
  });
  var deleteModal = document.getElementById('deleteConfirmationModal');
  // Triggers with data-delete-form="<form id>" are real server actions: confirming submits
  // that form (the server reports the result) instead of showing a local toast.
  var pendingDeleteForm = null;
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      var formId = trigger ? trigger.getAttribute('data-delete-form') : null;
      pendingDeleteForm = formId ? document.getElementById(formId) : null;
      if (!trigger) {
        return;
      }
      var title = trigger.getAttribute('data-delete-title');
      var message = trigger.getAttribute('data-delete-message');
      var confirmLabel = trigger.getAttribute('data-delete-confirm-label');
      var successMessage = trigger.getAttribute('data-success-message');
      var titleEl = deleteModal.querySelector('[data-delete-title]');
      var messageEl = deleteModal.querySelector('[data-delete-message]');
      var confirmBtn = deleteModal.querySelector('[data-delete-confirm]');
      if (titleEl && title) {
        titleEl.textContent = title;
      }
      if (messageEl && message) {
        messageEl.textContent = message;
      }
      if (confirmBtn) {
        confirmBtn.textContent = confirmLabel || 'Delete';
        confirmBtn.setAttribute('data-success-message', successMessage || 'Item deleted.');
      }
    });
  }
  document.addEventListener('click', function (event) {
    var confirmBtn = event.target.closest('[data-delete-confirm]');
    if (!confirmBtn) {
      return;
    }
    if (pendingDeleteForm) {
      confirmBtn.disabled = true;
      pendingDeleteForm.submit();
      return;
    }
    var message = confirmBtn.getAttribute('data-success-message') || 'Item deleted.';
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(message, 'success');
    }
    closeParentModal(confirmBtn);
  });

  var sessionRequestModal = document.getElementById('sessionRequestModal');
  if (sessionRequestModal) {
    sessionRequestModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      if (!trigger || !trigger.hasAttribute('data-request-name')) {
        return;
      }
      var name = trigger.getAttribute('data-request-name') || '';
      var initials = trigger.getAttribute('data-request-initials') || '';
      var department = trigger.getAttribute('data-request-department') || '';
      var skill = trigger.getAttribute('data-request-skill') || '';
      var rating = trigger.getAttribute('data-request-rating') || '';
      var skillOptions = (trigger.getAttribute('data-request-skill-options') || '').split('|').filter(Boolean);
      var mentorIdInput = sessionRequestModal.querySelector('[data-request-mentor-id-input]');
      if (mentorIdInput) {
        mentorIdInput.value = trigger.getAttribute('data-request-mentor-id') || '';
      }
      var avatarEl = sessionRequestModal.querySelector('[data-request-avatar]');
      var nameEl = sessionRequestModal.querySelector('[data-request-name-el]');
      var metaEl = sessionRequestModal.querySelector('[data-request-meta-el]');
      var ratingEl = sessionRequestModal.querySelector('[data-request-rating-el]');
      var skillSelect = sessionRequestModal.querySelector('#sessionRequestSkill');
      if (avatarEl) {
        avatarEl.textContent = initials;
      }
      if (nameEl) {
        nameEl.textContent = name;
      }
      if (metaEl) {
        metaEl.textContent = (skill ? skill + ' Mentor · ' : '') + department;
      }
      if (ratingEl) {
        ratingEl.textContent = rating ? '★ ' + rating : '';
      }
      if (skillSelect) {
        skillSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.selected = true;
        placeholder.disabled = true;
        placeholder.textContent = 'Choose a skill';
        skillSelect.appendChild(placeholder);
        skillOptions.forEach(function (optionLabel) {
          var optionEl = document.createElement('option');
          optionEl.value = optionLabel;
          optionEl.textContent = optionLabel;
          skillSelect.appendChild(optionEl);
        });
      }
    });
  }

  var ratingModal = document.getElementById('ratingModal');
  if (ratingModal) {
    ratingModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      if (!trigger || !trigger.hasAttribute('data-rating-mentor')) {
        return;
      }
      var mentor = trigger.getAttribute('data-rating-mentor') || '';
      var skill = trigger.getAttribute('data-rating-skill') || '';
      var date = trigger.getAttribute('data-rating-date') || '';
      var sessionIdInput = ratingModal.querySelector('[data-rating-session-id-input]');
      if (sessionIdInput) {
        sessionIdInput.value = trigger.getAttribute('data-rating-session-id') || '';
      }
      var summaryEl = ratingModal.querySelector('[data-rating-summary]');
      if (summaryEl) {
        summaryEl.textContent = (skill ? skill + ' with ' : '') + mentor + (date ? ' · ' + date : '');
      }
    });
  }
  var editPostModal = document.getElementById('editPostModal');
  if (editPostModal) {
    editPostModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      if (!trigger || !trigger.hasAttribute('data-edit-post-title')) {
        return;
      }
      var form = editPostModal.querySelector('form');
      var postIdInput = editPostModal.querySelector('[data-edit-post-id-input]');
      if (postIdInput) {
        postIdInput.value = trigger.getAttribute('data-edit-post-id') || '';
      }
      var titleInput = editPostModal.querySelector('#editPostTitle');
      var contentInput = editPostModal.querySelector('#editPostContent');
      var picker = editPostModal.querySelector('[data-skill-picker]');
      var hiddenSkills = picker ? picker.parentElement.querySelector('[data-skill-value]') : null;

      if (titleInput) {
        titleInput.value = trigger.getAttribute('data-edit-post-title') || '';
      }
      if (contentInput) {
        contentInput.value = trigger.getAttribute('data-edit-post-content') || '';
      }
      if (form) {
        resetSkillPicker(form);
      }
      if (picker && hiddenSkills) {
        (trigger.getAttribute('data-edit-post-skills') || '').split('|').filter(Boolean).forEach(function (skill) {
          addSkillTag(picker, hiddenSkills, skill);
        });
      }
    });
  }
})();