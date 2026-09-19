/**
 * Shared modal-content behavior for modals/*.php.
 *
 * Bootstrap's own Modal component (bootstrap.bundle.min.js) already
 * handles opening/closing, the backdrop, Escape, focus return and modal
 * stacking safety — none of that is reimplemented here. This file only
 * wires the parts Bootstrap has no opinion on:
 *   1. [data-mock-form] submit -> validate -> mock success toast + close
 *      (no real backend, ever — this always prevents the native submit)
 *   2. the skill tag picker used by Create Post / Edit Post
 *   3. injecting contextual title/message into the one reusable Delete
 *      Confirmation modal, based on whichever button opened it
 *   4. injecting contextual mentor content into the one reusable Session
 *      Request modal, based on whichever mentor-card.php button opened it
 *   5. injecting contextual mentor/skill/date content into the one
 *      reusable Rating modal, based on whichever session-card.php "Rate
 *      Mentor" button opened it
 *   6. injecting contextual title/content/skills into the one reusable
 *      Edit Post modal, based on whichever components/post-card.php
 *      "Edit post" button opened it
 *
 * assets/js/core/validation.js does the actual required-field checks;
 * assets/js/core/toast.js renders the success notice. Splitting these
 * concerns into one file each — rather than one file listening for the
 * same events multiple times — is what avoids duplicated listeners.
 */
(function () {
  'use strict';

  /* ---- 1. Mock form submit ---- */

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
    });
  }

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-mock-form]');
    if (!form) {
      return;
    }

    event.preventDefault(); // frontend-only mock — never a real submission

    var isValid = !(window.UKN && window.UKN.validateForm) || window.UKN.validateForm(form);
    if (!isValid) {
      return;
    }

    var message = form.getAttribute('data-success-message') || 'Saved successfully.';
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(message, 'success');
    }

    closeParentModal(form);

    // Reset after the hide transition starts so fields don't visibly
    // flash empty while the modal is still on screen.
    window.setTimeout(function () {
      form.reset();
      resetSkillPicker(form);
    }, 300);
  });

  /* ---- 2. Skill tag picker (Create Post / Edit Post) ---- */

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

    var form = picker.closest('[data-mock-form]');
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

  /* ---- 3. Reusable Delete Confirmation modal — contextual content ---- */
  /* Any trigger button can set data-delete-title / data-delete-message
     (and optionally data-success-message) to customize this one shared
     modal instead of a separate modal per entity type. */

  var deleteModal = document.getElementById('deleteConfirmationModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
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

    var message = confirmBtn.getAttribute('data-success-message') || 'Item deleted.';
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(message, 'success');
    }
    closeParentModal(confirmBtn);
  });

  /* ---- 4. Reusable Session Request modal — contextual mentor content ---- */
  /* Same shape as section 3 above: any components/mentor-card.php "Request
     Session" button can set data-request-name/-initials/-department/-skill/
     -rating/-skill-options instead of a second modal per mentor. A trigger
     that carries none of these (pages/profile/mentor-profile.php's button,
     which instead sets $requestMentor server-side) is left alone — this
     only overwrites content when there's something to overwrite it with. */

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

  /* ---- 5. Reusable Rating modal — contextual session content ---- */
  /* Same shape as sections 3/4 above: any components/session-card.php
     "Rate Mentor" button (or pages/sessions/session-details.php's own,
     same markup contract) sets data-rating-mentor/-skill/-date; this
     rewrites the one shared modal's summary line so rating Session A then
     Session B never leaves Session A's context showing. */

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

      var summaryEl = ratingModal.querySelector('[data-rating-summary]');
      if (summaryEl) {
        summaryEl.textContent = (skill ? skill + ' with ' : '') + mentor + (date ? ' · ' + date : '');
      }
    });
  }

  /* ---- 6. Reusable Edit Post modal — contextual post content ---- */
  /* Same shape as sections 3/4/5 above: components/post-card.php's "Edit
     post" button sets data-edit-post-id/-title/-content/-skills; this
     rewrites the one shared modal's fields (title input, content
     textarea, and the skill tag picker — reusing this file's own
     resetSkillPicker()/addSkillTag() from section 2 rather than a second
     tag-rebuilding implementation) so editing Post A then Post B never
     leaves Post A's title/content/skills showing. A trigger with none of
     these attributes (there isn't one today, but a future direct
     data-bs-target="#editPostModal" with no context) leaves the modal's
     own hardcoded fallback content in $editPost untouched. */

  var editPostModal = document.getElementById('editPostModal');
  if (editPostModal) {
    editPostModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      if (!trigger || !trigger.hasAttribute('data-edit-post-title')) {
        return;
      }

      var form = editPostModal.querySelector('[data-mock-form]');
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
