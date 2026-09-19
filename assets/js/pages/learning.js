/**
 * Learning Goals / Availability — page-specific frontend behavior only.
 * Generic behavior (the shared modal engine, the Delete Confirmation
 * modal's own toast+close, global validation, theme/role switching) all
 * already has its own dedicated module and is not duplicated here — this
 * file only handles what's unique to these two pages:
 *   Learning Goals: filter pills, Create/Edit Goal (one page-specific
 *     modal, see pages/learning/learning-goals.php), Mark Complete,
 *     Delete-card removal
 *   Availability: day enable/disable, add/remove time slot, time
 *     validation, dirty-tracking the Save button, Save/Reset
 *
 * All frontend-only mock state, kept in memory / the DOM — nothing here
 * persists anywhere or survives a refresh.
 */
(function () {
  'use strict';

  /* =====================================================================
     LEARNING GOALS
     ===================================================================== */

  var goalFiltersBar = document.querySelector('[data-goal-filters]');
  var goalFormModalEl = document.getElementById('goalFormModal');

  function formatDateLong(date) {
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
  }

  function parseIsoDateLocal(iso) {
    // 'YYYY-MM-DD' parsed as local midnight, not UTC, so the displayed
    // day never shifts by a timezone off-by-one.
    var parts = iso.split('-').map(Number);
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }

  /* ---- Filter pills (In Progress / Completed) ---- */

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

  /* ---- Create / Edit Goal (one shared page-specific modal) ---- */

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
      goalFormIdInput.value = '';
      goalFormTitleEl.textContent = 'Create Goal';
      goalFormSubmitBtn.textContent = 'Create Goal';
      goalProgressInput.value = '0';
      goalProgressOutput.textContent = '0%';
    };

    var openGoalFormForEdit = function (card) {
      goalForm.reset();
      goalFormIdInput.value = card.getAttribute('data-goal-id') || '';
      goalForm.querySelector('#goalFormTitle').value = card.getAttribute('data-goal-title') || '';
      goalForm.querySelector('#goalFormSkill').value = card.getAttribute('data-goal-skill') || '';
      goalForm.querySelector('#goalFormDate').value = card.getAttribute('data-goal-target-date') || '';
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
        event.preventDefault(); // covers the empty-state's plain '#' link too
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
      event.preventDefault(); // frontend-only mock — never a real save

      var isValid = !(window.UKN && window.UKN.validateForm) || window.UKN.validateForm(goalForm);
      if (!isValid) {
        return;
      }

      var goalId = goalFormIdInput.value;
      var title = goalForm.querySelector('#goalFormTitle').value.trim();
      var skill = goalForm.querySelector('#goalFormSkill').value;
      var dateValue = goalForm.querySelector('#goalFormDate').value;
      var progress = goalProgressInput.value;

      if (goalId) {
        var card = document.querySelector('[data-goal-id="' + goalId + '"]');
        if (card) {
          card.setAttribute('data-goal-title', title);
          card.setAttribute('data-goal-skill', skill);
          card.setAttribute('data-goal-target-date', dateValue);
          card.setAttribute('data-goal-progress', progress);

          var titleEl = card.querySelector('h3');
          if (titleEl) {
            titleEl.textContent = title;
          }
          var pct = Math.max(0, Math.min(100, parseInt(progress, 10) || 0));
          var progressBar = card.querySelector('.progress-bar');
          if (progressBar) {
            progressBar.style.width = pct + '%';
            progressBar.setAttribute('aria-valuenow', String(pct));
          }
          var progressLabel = card.querySelector('[data-goal-progress-label]');
          if (progressLabel) {
            progressLabel.textContent = pct + '%';
          }
          var isCompleted = card.getAttribute('data-goal-status') === 'completed';
          var metaEl = card.querySelector('[data-goal-meta]');
          if (metaEl && dateValue) {
            var niceDate = formatDateLong(parseIsoDateLocal(dateValue));
            metaEl.textContent = (skill ? skill + ' · ' : '') + (isCompleted ? 'Completed' : 'target') + ' ' + niceDate;
          }
        }
        if (window.UKN && window.UKN.showToast) {
          window.UKN.showToast('Goal updated successfully. Demo mode only.', 'success');
        }
      } else if (window.UKN && window.UKN.showToast) {
        window.UKN.showToast('Goal created successfully. Demo mode only.', 'success');
      }

      goalModal.hide();
    });
  }

  /* ---- Mark Complete ---- */

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-goal-complete]');
    if (!btn) {
      return;
    }
    var card = btn.closest('[data-goal-id]');
    if (!card) {
      return;
    }

    card.setAttribute('data-goal-status', 'completed');
    card.setAttribute('data-goal-progress', '100');
    card.classList.remove('ukn-card-marked-left');

    var statusPill = card.querySelector('.ukn-status');
    if (statusPill) {
      statusPill.textContent = 'Completed';
      statusPill.classList.remove('ukn-status-accent');
      statusPill.classList.add('ukn-status-success');
    }
    var progressBar = card.querySelector('.progress-bar');
    if (progressBar) {
      progressBar.style.width = '100%';
      progressBar.setAttribute('aria-valuenow', '100');
    }
    var progressLabel = card.querySelector('[data-goal-progress-label]');
    if (progressLabel) {
      progressLabel.textContent = '100%';
    }
    var metaEl = card.querySelector('[data-goal-meta]');
    if (metaEl) {
      var skill = card.getAttribute('data-goal-skill');
      var today = formatDateLong(new Date());
      metaEl.textContent = (skill ? skill + ' · ' : '') + 'Completed ' + today;
    }
    btn.remove();

    var activeList = document.querySelector('[data-goal-section="active"] [data-goal-list]');
    var completedList = document.querySelector('[data-goal-section="completed"] [data-goal-list]');
    if (completedList) {
      completedList.appendChild(card);
    }

    var activeCountEl = document.querySelector('[data-goal-count="active"]');
    var completedCountEl = document.querySelector('[data-goal-count="completed"]');
    if (activeCountEl) {
      activeCountEl.textContent = String(Math.max(0, (parseInt(activeCountEl.textContent, 10) || 0) - 1));
    }
    if (completedCountEl) {
      completedCountEl.textContent = String((parseInt(completedCountEl.textContent, 10) || 0) + 1);
    }

    var activeEmpty = document.querySelector('[data-goal-section="active"] [data-goal-list-empty]');
    if (activeList && activeEmpty) {
      activeEmpty.hidden = !!activeList.querySelector('[data-goal-id]');
    }
    var completedEmpty = document.querySelector('[data-goal-section="completed"] [data-goal-list-empty]');
    if (completedEmpty) {
      completedEmpty.hidden = true;
    }

    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast((card.getAttribute('data-goal-title') || 'Goal') + ' marked as complete!', 'success');
    }
  });

  /* ---- Delete Goal (shared Delete Confirmation modal) ----
     Mirrors assets/js/pages/skills.js's [data-remove-skill-card] pattern
     exactly — a distinct data attribute/pending-reference pair means both
     can safely listen on the same shared modal without interfering with
     each other. */

  var pendingRemoveGoalCard = null;
  var deleteModal = document.getElementById('deleteConfirmationModal');

  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      pendingRemoveGoalCard = trigger && trigger.hasAttribute('data-remove-goal-card')
        ? trigger.closest('[data-goal-id]')
        : null;
    });
  }

  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-delete-confirm]') || !pendingRemoveGoalCard) {
      return;
    }
    var card = pendingRemoveGoalCard;
    pendingRemoveGoalCard = null;

    var wasCompleted = card.getAttribute('data-goal-status') === 'completed';
    var section = card.closest('[data-goal-section]');
    card.remove();

    var countEl = document.querySelector('[data-goal-count="' + (wasCompleted ? 'completed' : 'active') + '"]');
    if (countEl) {
      countEl.textContent = String(Math.max(0, (parseInt(countEl.textContent, 10) || 0) - 1));
    }

    if (section) {
      var list = section.querySelector('[data-goal-list]');
      var emptyState = section.querySelector('[data-goal-list-empty]');
      if (list && emptyState) {
        emptyState.hidden = !!list.querySelector('[data-goal-id]');
      }
    }
  });

  /* =====================================================================
     AVAILABILITY
     ===================================================================== */

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
      '<input type="time" class="form-control form-control-sm ukn-time-input" id="' + startId + '" data-slot-start></div>' +
      '<span class="ukn-body-sm mt-1">to</span>' +
      '<div><label class="ukn-visually-hidden" for="' + endId + '">End time</label>' +
      '<input type="time" class="form-control form-control-sm ukn-time-input" id="' + endId + '" data-slot-end></div>' +
      '<button type="button" class="btn-icon btn-icon-sm" aria-label="Remove this time slot" data-availability-remove-slot>' +
      '<span class="ms" aria-hidden="true">close</span></button>' +
      '<div class="ukn-field-message is-invalid mb-0 w-100" data-slot-error hidden>' +
      '<span class="ms" aria-hidden="true">error</span>End time must be later than start time.</div>';
    return row;
  };

  /* ---- Day toggle ---- */

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

  /* ---- Add / Remove time slot ---- */

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

  /* ---- Save / Reset ---- */

  availabilityForm.addEventListener('submit', function (event) {
    event.preventDefault(); // frontend-only mock — never a real save

    if (availabilityForm.querySelector('.is-invalid')) {
      if (window.UKN && window.UKN.showToast) {
        window.UKN.showToast('Fix the highlighted time slots before saving.', 'danger');
      }
      return;
    }

    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast('Availability updated successfully. Demo mode only.', 'success');
    }
    if (saveBtn) {
      saveBtn.disabled = true;
    }
  });

  document.addEventListener('click', function (event) {
    if (event.target.closest('[data-availability-reset]')) {
      window.location.reload();
    }
  });
})();
