(function () {
  'use strict';
  var goalFiltersBar = document.querySelector('[data-goal-filters]');
  var goalFormModalEl = document.getElementById('goalFormModal');

  if (goalFiltersBar) {
    goalFiltersBar.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-goal-filter]');
      if (!btn) {
        return;
      }
      var target = btn.getAttribute('data-goal-filter');
      goalFiltersBar.querySelectorAll('[data-goal-filter]').forEach(function (pill) {
        var isActive = pill === btn;
        pill.classList.toggle('is-active', isActive);
        pill.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
      document.querySelectorAll('[data-goal-section]').forEach(function (section) {
        section.hidden = section.getAttribute('data-goal-section') !== target;
      });
    });
  }
  // The goal form posts to backend/goals/create.php or update.php; completing and deleting
  // goals are real forms on each goal card. JS only fills the modal and blocks invalid submits.
  if (goalFormModalEl && window.bootstrap) {
    var goalForm = goalFormModalEl.querySelector('[data-goal-form]');
    var goalFormIdInput = goalFormModalEl.querySelector('[data-goal-form-id]');
    var goalFormTitleEl = goalFormModalEl.querySelector('[data-goal-form-title]');
    var goalFormSubmitBtn = goalFormModalEl.querySelector('[data-goal-form-submit]');
    var goalProgressInput = goalFormModalEl.querySelector('[data-goal-progress-input]');
    var goalProgressOutput = goalFormModalEl.querySelector('[data-goal-progress-output]');
    var goalModal = bootstrap.Modal.getOrCreateInstance(goalFormModalEl);
    var resetGoalForm = function () {
      goalForm.reset();
      goalForm.setAttribute('action', goalForm.getAttribute('data-create-action'));
      goalFormIdInput.value = '';
      goalFormTitleEl.textContent = 'Create Goal';
      goalFormSubmitBtn.textContent = 'Create Goal';
      goalProgressInput.value = '0';
      goalProgressOutput.textContent = '0%';
    };
    var openGoalFormForEdit = function (card) {
      goalForm.reset();
      goalForm.setAttribute('action', goalForm.getAttribute('data-update-action'));
      goalFormIdInput.value = card.getAttribute('data-goal-id') || '';
      goalForm.querySelector('#goalFormTitle').value = card.getAttribute('data-goal-title') || '';
      goalForm.querySelector('#goalFormSkill').value = card.getAttribute('data-goal-skill') || '';
      goalForm.querySelector('#goalFormDate').value = card.getAttribute('data-goal-target-date') || '';
      goalForm.querySelector('#goalFormDescription').value = card.getAttribute('data-goal-description') || '';
      var progress = card.getAttribute('data-goal-progress') || '0';
      goalProgressInput.value = progress;
      goalProgressOutput.textContent = progress + '%';
      goalFormTitleEl.textContent = 'Edit Goal';
      goalFormSubmitBtn.textContent = 'Save Changes';
    };
    if (goalProgressInput) {
      goalProgressInput.addEventListener('input', function () {
        goalProgressOutput.textContent = goalProgressInput.value + '%';
      });
    }
    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-goal-create]')) {
        event.preventDefault();
        resetGoalForm();
        goalModal.show();
        return;
      }
      var editBtn = event.target.closest('[data-goal-edit]');
      if (editBtn) {
        var card = editBtn.closest('[data-goal-id]');
        if (card) {
          openGoalFormForEdit(card);
          goalModal.show();
        }
      }
    });
    goalForm.addEventListener('submit', function (event) {
      var isValid = !(window.UKN && window.UKN.validateForm) || window.UKN.validateForm(goalForm);
      if (!isValid) {
        event.preventDefault();
      }
    });
  }

  var availabilityForm = document.querySelector('[data-availability-form]');
  if (!availabilityForm) {
    return;
  }
  var saveBtn = availabilityForm.querySelector('[data-availability-save]');
  var MAX_SLOTS_PER_DAY = 4;
  var markDirty = function () {
    if (saveBtn) {
      saveBtn.disabled = false;
    }
  };
  var updateAddSlotVisibility = function (dayEl) {
    var addBtn = dayEl.querySelector('[data-availability-add-slot]');
    var slotCount = dayEl.querySelectorAll('[data-availability-slot]').length;
    if (addBtn) {
      addBtn.hidden = slotCount >= MAX_SLOTS_PER_DAY;
    }
  };

  var validateSlot = function (slotEl) {
    var start = slotEl.querySelector('[data-slot-start]');
    var end = slotEl.querySelector('[data-slot-end]');
    var error = slotEl.querySelector('[data-slot-error]');
    if (!start || !end) {
      return;
    }
    var invalid = !!start.value && !!end.value && end.value <= start.value;
    start.classList.toggle('is-invalid', invalid);
    end.classList.toggle('is-invalid', invalid);
    if (error) {
      error.hidden = !invalid;
    }
  };
  var setDayEnabled = function (dayEl, enabled) {
    var toggle = dayEl.querySelector('[data-availability-toggle]');
    var slotsWrap = dayEl.querySelector('[data-availability-slots]');
    var unavailableLabel = dayEl.querySelector('[data-availability-unavailable-label]');
    var toggleLabel = dayEl.querySelector('[data-availability-toggle-label]');
    var addBtn = dayEl.querySelector('[data-availability-add-slot]');

    if (toggle) {
      toggle.checked = enabled;
    }
    if (slotsWrap) {
      slotsWrap.hidden = !enabled;
    }
    if (unavailableLabel) {
      unavailableLabel.hidden = enabled;
    }
    if (toggleLabel) {
      toggleLabel.textContent = enabled ? 'Available' : 'Unavailable';
    }
    if (addBtn) {
      addBtn.hidden = !enabled;
    }
    if (enabled) {
      updateAddSlotVisibility(dayEl);
    }
  };
  var buildSlotRow = function (dayKey, index) {
    var startId = 'avail-' + dayKey + '-start-' + index;
    var endId = 'avail-' + dayKey + '-end-' + index;
    var row = document.createElement('div');
    row.className = 'd-flex align-items-start gap-2 flex-wrap mb-2';
    row.setAttribute('data-availability-slot', '');
    row.innerHTML =
      '<div><label class="ukn-visually-hidden" for="' + startId + '">Start time</label>' +
      '<input type="time" class="form-control form-control-sm ukn-time-input" id="' + startId + '" name="slot_start[' + dayKey + '][]" data-slot-start></div>' +
      '<span class="ukn-body-sm mt-1">to</span>' +
      '<div><label class="ukn-visually-hidden" for="' + endId + '">End time</label>' +
      '<input type="time" class="form-control form-control-sm ukn-time-input" id="' + endId + '" name="slot_end[' + dayKey + '][]" data-slot-end></div>' +
      '<button type="button" class="btn-icon btn-icon-sm" aria-label="Remove this time slot" data-availability-remove-slot>' +
      '<span class="ms" aria-hidden="true">close</span></button>' +
      '<div class="ukn-field-message is-invalid mb-0 w-100" data-slot-error hidden>' +
      '<span class="ms" aria-hidden="true">error</span>End time must be later than start time.</div>';
    return row;
  };

  availabilityForm.addEventListener('change', function (event) {
    var toggle = event.target.closest('[data-availability-toggle]');
    if (toggle) {
      setDayEnabled(toggle.closest('[data-availability-day]'), toggle.checked);
      markDirty();
      return;
    }
    if (event.target.closest('[data-slot-start], [data-slot-end]')) {
      validateSlot(event.target.closest('[data-availability-slot]'));
      markDirty();
      return;
    }
    if (event.target.closest('[data-availability-request-status]')) {
      var label = availabilityForm.querySelector('[data-availability-request-status-label]');
      if (label) {
        label.textContent = event.target.checked ? 'Accepting Requests' : 'Not Accepting Requests';
      }
      markDirty();
    }
  });

  availabilityForm.addEventListener('click', function (event) {
    var addBtn = event.target.closest('[data-availability-add-slot]');
    if (addBtn) {
      var dayEl = addBtn.closest('[data-availability-day]');
      var slotsWrap = dayEl.querySelector('[data-availability-slots]');
      var dayKey = dayEl.getAttribute('data-availability-day');
      var row = buildSlotRow(dayKey, slotsWrap.querySelectorAll('[data-availability-slot]').length);
      slotsWrap.appendChild(row);
      updateAddSlotVisibility(dayEl);
      markDirty();
      return;
    }
    var removeBtn = event.target.closest('[data-availability-remove-slot]');
    if (removeBtn) {
      var slotEl = removeBtn.closest('[data-availability-slot]');
      var day = removeBtn.closest('[data-availability-day]');
      slotEl.remove();
      if (day.querySelectorAll('[data-availability-slot]').length === 0) {
        setDayEnabled(day, false);
      } else {
        updateAddSlotVisibility(day);
      }
      markDirty();
    }
  });
  // Posts to backend/availability/save.php, which re-validates everything server-side.
  availabilityForm.addEventListener('submit', function (event) {
    if (availabilityForm.querySelector('.is-invalid')) {
      event.preventDefault();
      if (window.UKN && window.UKN.showToast) {
        window.UKN.showToast('Fix the highlighted time slots before saving.', 'danger');
      }
    }
  });
  document.addEventListener('click', function (event) {
    if (event.target.closest('[data-availability-reset]')) {
      window.location.reload();
    }
  });
})();
