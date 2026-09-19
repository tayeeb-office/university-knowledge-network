/**
 * Admin Departments / Skill Categories / Skills — page-specific frontend
 * behavior for all three management pages (each section below only runs
 * if that page's own table exists in the DOM — see "page detection"
 * guards). Search/filter/sort/pagination are all client-side against the
 * server-rendered table; Add/Edit uses one reusable modal per page,
 * populated fresh on every open so there is never stale context between
 * two different rows. Deactivate/Activate all reuse the ONE shared
 * modals/delete-confirmation-modal.php + its existing generic
 * [data-delete-confirm] toast/close behavior (assets/js/core/modal.js) —
 * this file only adds the "which row triggered it" pending-reference step,
 * the same pattern admin/users.js already uses for Suspend/Restore.
 *
 * Frontend-only: nothing here ever calls a backend. "Adding" a row only
 * appends a new <tr> to the current page's own DOM/table — it resets on
 * refresh, which is an accepted, explicitly-sanctioned limitation of this
 * frontend-only phase rather than a bug.
 */
(function () {
  'use strict';

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  /* =====================================================================
     Shared Deactivate/Activate pending-action handling for all three
     entity types on ONE shared modals/delete-confirmation-modal.php.
     ===================================================================== */

  var deleteModal = document.getElementById('deleteConfirmationModal');
  var pendingStatusAction = null; // { entity: 'department'|'category'|'skill', action: 'activate'|'deactivate', row }

  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      pendingStatusAction = null;
      if (!trigger) {
        return;
      }
      var entities = ['department', 'category', 'skill'];
      for (var i = 0; i < entities.length; i++) {
        var entity = entities[i];
        if (trigger.hasAttribute('data-' + entity + '-deactivate') || trigger.hasAttribute('data-' + entity + '-activate')) {
          var row = trigger.closest('[data-' + entity + '-row]');
          if (row) {
            pendingStatusAction = {
              entity: entity,
              action: trigger.hasAttribute('data-' + entity + '-deactivate') ? 'deactivate' : 'activate',
              row: row,
            };
          }
          return;
        }
      }
    });
  }

  function setEntityStatus(entity, row, status) {
    var label = status === 'active' ? 'Active' : 'Inactive';
    var statusClass = status === 'active' ? 'ukn-status-accent' : 'ukn-status-neutral';

    row.dataset[entity + 'Status'] = status;

    var badge = row.querySelector('[data-' + entity + '-status-badge]');
    if (badge) {
      badge.textContent = label;
      badge.classList.remove('ukn-status-accent', 'ukn-status-neutral');
      badge.classList.add(statusClass);
    }
    var deactivateBtn = row.querySelector('[data-' + entity + '-deactivate]');
    var activateBtn = row.querySelector('[data-' + entity + '-activate]');
    if (deactivateBtn) {
      deactivateBtn.hidden = status !== 'active';
    }
    if (activateBtn) {
      activateBtn.hidden = status === 'active';
    }
  }

  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-delete-confirm]') || !pendingStatusAction) {
      return;
    }
    var action = pendingStatusAction;
    pendingStatusAction = null;
    setEntityStatus(action.entity, action.row, action.action === 'activate' ? 'active' : 'inactive');
    if (action.entity === 'department' && typeof applyDepartmentsView === 'function') {
      applyDepartmentsView();
    }
    if (action.entity === 'category' && typeof applyCategoriesView === 'function') {
      applyCategoriesView();
    }
    if (action.entity === 'skill' && typeof applySkillsView === 'function') {
      applySkillsView();
    }
  });

  /* =====================================================================
     Departments (admin/departments.php)
     ===================================================================== */

  var departmentTable = document.querySelector('[data-department-table]');
  if (departmentTable) {
    var deptTbody = departmentTable.querySelector('tbody');
    var deptSearch = document.querySelector('[data-department-search]');
    var deptStatusFilter = document.querySelector('[data-department-filter="status"]');
    var deptResultCount = document.querySelector('[data-department-result-count]');
    var deptEmpty = document.querySelector('[data-department-empty]');

    function deptRows() {
      return Array.prototype.slice.call(deptTbody.querySelectorAll('tr[data-department-row]'));
    }

    window.applyDepartmentsView = function applyDepartmentsView() {
      var query = deptSearch ? deptSearch.value.trim().toLowerCase() : '';
      var status = deptStatusFilter ? deptStatusFilter.value : '';
      var visibleCount = 0;
      deptRows().forEach(function (row) {
        var haystack = row.getAttribute('data-department-name') + ' ' + row.getAttribute('data-department-code');
        var matches = (!query || haystack.indexOf(query) !== -1) && (!status || row.getAttribute('data-department-status') === status);
        row.hidden = !matches;
        if (matches) {
          visibleCount++;
        }
      });
      if (deptResultCount) {
        deptResultCount.textContent = visibleCount + (visibleCount === 1 ? ' department found' : ' departments found');
      }
      if (deptEmpty) {
        deptEmpty.hidden = visibleCount !== 0;
      }
      departmentTable.closest('.card').querySelector('.table-responsive').hidden = visibleCount === 0;
    };

    if (deptSearch) { deptSearch.addEventListener('input', applyDepartmentsView); }
    if (deptStatusFilter) { deptStatusFilter.addEventListener('change', applyDepartmentsView); }
    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-department-clear-filters]')) {
        return;
      }
      event.preventDefault();
      if (deptSearch) { deptSearch.value = ''; }
      if (deptStatusFilter) { deptStatusFilter.value = ''; }
      applyDepartmentsView();
    });

    /* ---- Add / Edit Department ---- */

    var deptForm = document.querySelector('[data-department-form]');
    var deptFormTitle = document.querySelector('[data-department-form-title]');
    var deptFormSubmit = document.querySelector('[data-department-form-submit]');
    var deptFormId = document.querySelector('[data-department-form-id]');

    function nextDepartmentId() {
      var max = 0;
      deptRows().forEach(function (row) {
        max = Math.max(max, parseInt(row.getAttribute('data-department-id'), 10) || 0);
      });
      return max + 1;
    }

    function buildDepartmentRow(id, data) {
      var row = document.createElement('tr');
      row.setAttribute('data-department-row', '');
      row.setAttribute('data-department-id', String(id));
      row.setAttribute('data-department-name', data.name.toLowerCase());
      row.setAttribute('data-department-code', data.code.toLowerCase());
      row.setAttribute('data-department-status', data.status);
      var isActive = data.status === 'active';
      row.innerHTML =
        '<td data-label="Department" class="fw-bold" data-department-cell="name">' + escapeHtml(data.name) + '</td>' +
        '<td data-label="Code" data-department-cell="code">' + escapeHtml(data.code) + '</td>' +
        '<td data-label="Users" data-department-cell="users">0</td>' +
        '<td data-label="Learners">0</td>' +
        '<td data-label="Mentors">0</td>' +
        '<td data-label="Status"><span class="ukn-status ' + (isActive ? 'ukn-status-accent' : 'ukn-status-neutral') + '" data-department-status-badge>' + (isActive ? 'Active' : 'Inactive') + '</span></td>' +
        '<td data-label="Actions">' +
          '<div class="d-flex gap-1 justify-content-md-end">' +
            '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#departmentFormModal" data-department-edit>Edit</button>' +
            '<div class="dropdown">' +
              '<button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Department actions for ' + escapeHtml(data.name) + '"><span class="ms" aria-hidden="true">more_vert</span></button>' +
              '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-department-deactivate' + (isActive ? '' : ' hidden') + ' data-delete-title="Deactivate ' + escapeHtml(data.name) + '?" data-delete-message="This is a frontend demo. The department status will only change in the current mock state." data-delete-confirm-label="Deactivate Department" data-success-message="Department deactivated in demo mode.">Deactivate</button></li>' +
                '<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-department-activate' + (isActive ? ' hidden' : '') + ' data-delete-title="Activate ' + escapeHtml(data.name) + '?" data-delete-message="This is a frontend demo. The department status will only change in the current mock state." data-delete-confirm-label="Activate Department" data-success-message="Department activated in demo mode.">Activate</button></li>' +
              '</ul>' +
            '</div>' +
          '</div>' +
        '</td>';
      return row;
    }

    function checkDepartmentDuplicate(form) {
      var nameField = form.querySelector('[name="name"]');
      var codeField = form.querySelector('[name="code"]');
      var editingId = deptFormId.value;
      var name = nameField.value.trim().toLowerCase();
      var code = codeField.value.trim().toLowerCase();
      var nameDup = false;
      var codeDup = false;
      deptRows().forEach(function (row) {
        if (editingId && row.getAttribute('data-department-id') === editingId) {
          return;
        }
        if (name && row.getAttribute('data-department-name') === name) {
          nameDup = true;
        }
        if (code && row.getAttribute('data-department-code') === code) {
          codeDup = true;
        }
      });
      if (window.UKN && window.UKN.setFieldError) {
        window.UKN.setFieldError(form, 'name', nameDup, nameField, nameDup ? 'duplicate' : null);
        window.UKN.setFieldError(form, 'code', codeDup, codeField, codeDup ? 'duplicate' : null);
      }
      return !nameDup && !codeDup;
    }

    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-department-add]')) {
        deptForm.reset();
        deptFormId.value = '';
        deptFormTitle.textContent = 'Add Department';
        deptFormSubmit.textContent = 'Add Department';
      }
      var editBtn = event.target.closest('[data-department-edit]');
      if (editBtn) {
        var row = editBtn.closest('[data-department-row]');
        deptForm.reset();
        deptFormId.value = row.getAttribute('data-department-id');
        deptForm.querySelector('[name="name"]').value = row.querySelector('[data-department-cell="name"]').textContent.trim();
        deptForm.querySelector('[name="code"]').value = row.querySelector('[data-department-cell="code"]').textContent.trim();
        deptForm.querySelector('[name="status"]').value = row.getAttribute('data-department-status');
        deptFormTitle.textContent = 'Edit Department';
        deptFormSubmit.textContent = 'Save Changes';
      }
    });

    if (deptForm) {
      deptForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var requiredValid = window.UKN && window.UKN.validateForm ? window.UKN.validateForm(deptForm) : true;
        var noDuplicate = checkDepartmentDuplicate(deptForm);
        if (!requiredValid || !noDuplicate) {
          return;
        }

        var data = {
          name: deptForm.querySelector('[name="name"]').value.trim(),
          code: deptForm.querySelector('[name="code"]').value.trim().toUpperCase(),
          status: deptForm.querySelector('[name="status"]').value,
        };

        if (deptFormId.value) {
          var row = deptTbody.querySelector('[data-department-id="' + deptFormId.value + '"]');
          if (row) {
            row.querySelector('[data-department-cell="name"]').textContent = data.name;
            row.querySelector('[data-department-cell="code"]').textContent = data.code;
            row.setAttribute('data-department-name', data.name.toLowerCase());
            row.setAttribute('data-department-code', data.code.toLowerCase());
            setEntityStatus('department', row, data.status);
          }
          if (window.UKN && window.UKN.showToast) { window.UKN.showToast('Department updated in demo mode.', 'success'); }
        } else {
          var newRow = buildDepartmentRow(nextDepartmentId(), data);
          deptTbody.appendChild(newRow);
          if (window.UKN && window.UKN.showToast) { window.UKN.showToast('Department added in demo mode.', 'success'); }
        }

        applyDepartmentsView();
        var modalEl = deptForm.closest('.modal');
        if (modalEl && window.bootstrap) {
          var instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
          instance.hide();
        }
      });
    }

    applyDepartmentsView();
  }

  /* =====================================================================
     Skill Categories (admin/skill-categories.php)
     ===================================================================== */

  var categoryTable = document.querySelector('[data-category-table]');
  if (categoryTable) {
    var catTbody = categoryTable.querySelector('tbody');
    var catSearch = document.querySelector('[data-category-search]');
    var catStatusFilter = document.querySelector('[data-category-filter="status"]');
    var catResultCount = document.querySelector('[data-category-result-count]');
    var catEmpty = document.querySelector('[data-category-empty]');

    function catRows() {
      return Array.prototype.slice.call(catTbody.querySelectorAll('tr[data-category-row]'));
    }

    window.applyCategoriesView = function applyCategoriesView() {
      var query = catSearch ? catSearch.value.trim().toLowerCase() : '';
      var status = catStatusFilter ? catStatusFilter.value : '';
      var visibleCount = 0;
      catRows().forEach(function (row) {
        var matches = (!query || row.getAttribute('data-category-name').indexOf(query) !== -1) && (!status || row.getAttribute('data-category-status') === status);
        row.hidden = !matches;
        if (matches) {
          visibleCount++;
        }
      });
      if (catResultCount) {
        catResultCount.textContent = visibleCount + (visibleCount === 1 ? ' category found' : ' categories found');
      }
      if (catEmpty) {
        catEmpty.hidden = visibleCount !== 0;
      }
      categoryTable.closest('.card').querySelector('.table-responsive').hidden = visibleCount === 0;
    };

    if (catSearch) { catSearch.addEventListener('input', applyCategoriesView); }
    if (catStatusFilter) { catStatusFilter.addEventListener('change', applyCategoriesView); }
    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-category-clear-filters]')) {
        return;
      }
      event.preventDefault();
      if (catSearch) { catSearch.value = ''; }
      if (catStatusFilter) { catStatusFilter.value = ''; }
      applyCategoriesView();
    });

    /* ---- Add / Edit Category ---- */

    var catForm = document.querySelector('[data-category-form]');
    var catFormTitle = document.querySelector('[data-category-form-title]');
    var catFormSubmit = document.querySelector('[data-category-form-submit]');
    var catFormId = document.querySelector('[data-category-form-id]');

    function nextCategoryId() {
      var max = 0;
      catRows().forEach(function (row) {
        max = Math.max(max, parseInt(row.getAttribute('data-category-id'), 10) || 0);
      });
      return max + 1;
    }

    function buildCategoryRow(id, data) {
      var row = document.createElement('tr');
      row.setAttribute('data-category-row', '');
      row.setAttribute('data-category-id', String(id));
      row.setAttribute('data-category-name', data.name.toLowerCase());
      row.setAttribute('data-category-status', data.status);
      row.setAttribute('data-category-skill-count', '0');
      var isActive = data.status === 'active';
      row.innerHTML =
        '<td data-label="Category" class="fw-bold" data-category-cell="name">' + escapeHtml(data.name) + '</td>' +
        '<td data-label="Description" class="ukn-body-sm ukn-text-muted" data-category-cell="description">' + escapeHtml(data.description) + '</td>' +
        '<td data-label="Skills">0 Skills</td>' +
        '<td data-label="Status"><span class="ukn-status ' + (isActive ? 'ukn-status-accent' : 'ukn-status-neutral') + '" data-category-status-badge>' + (isActive ? 'Active' : 'Inactive') + '</span></td>' +
        '<td data-label="Updated">Just now</td>' +
        '<td data-label="Actions">' +
          '<div class="d-flex gap-1 justify-content-md-end">' +
            '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryFormModal" data-category-edit>Edit</button>' +
            '<div class="dropdown">' +
              '<button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Category actions for ' + escapeHtml(data.name) + '"><span class="ms" aria-hidden="true">more_vert</span></button>' +
              '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-category-deactivate' + (isActive ? '' : ' hidden') + ' data-delete-title="Deactivate ' + escapeHtml(data.name) + '?" data-delete-message="This is a frontend demo. The category status will only change in the current mock state." data-delete-confirm-label="Deactivate Category" data-success-message="Category deactivated in demo mode.">Deactivate</button></li>' +
                '<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-category-activate' + (isActive ? ' hidden' : '') + ' data-delete-title="Activate ' + escapeHtml(data.name) + '?" data-delete-message="This is a frontend demo. The category status will only change in the current mock state." data-delete-confirm-label="Activate Category" data-success-message="Category activated in demo mode.">Activate</button></li>' +
              '</ul>' +
            '</div>' +
          '</div>' +
        '</td>';
      return row;
    }

    function checkCategoryDuplicate(form) {
      var nameField = form.querySelector('[name="name"]');
      var editingId = catFormId.value;
      var name = nameField.value.trim().toLowerCase();
      var dup = false;
      catRows().forEach(function (row) {
        if (editingId && row.getAttribute('data-category-id') === editingId) {
          return;
        }
        if (name && row.getAttribute('data-category-name') === name) {
          dup = true;
        }
      });
      if (window.UKN && window.UKN.setFieldError) {
        window.UKN.setFieldError(form, 'name', dup, nameField, dup ? 'duplicate' : null);
      }
      return !dup;
    }

    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-category-add]')) {
        catForm.reset();
        catFormId.value = '';
        catFormTitle.textContent = 'Add Category';
        catFormSubmit.textContent = 'Add Category';
      }
      var editBtn = event.target.closest('[data-category-edit]');
      if (editBtn) {
        var row = editBtn.closest('[data-category-row]');
        catForm.reset();
        catFormId.value = row.getAttribute('data-category-id');
        catForm.querySelector('[name="name"]').value = row.querySelector('[data-category-cell="name"]').textContent.trim();
        catForm.querySelector('[name="description"]').value = row.querySelector('[data-category-cell="description"]').textContent.trim();
        catForm.querySelector('[name="status"]').value = row.getAttribute('data-category-status');
        catFormTitle.textContent = 'Edit Category';
        catFormSubmit.textContent = 'Save Changes';
      }
    });

    if (catForm) {
      catForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var requiredValid = window.UKN && window.UKN.validateForm ? window.UKN.validateForm(catForm) : true;
        var noDuplicate = checkCategoryDuplicate(catForm);
        if (!requiredValid || !noDuplicate) {
          return;
        }

        var data = {
          name: catForm.querySelector('[name="name"]').value.trim(),
          description: catForm.querySelector('[name="description"]').value.trim(),
          status: catForm.querySelector('[name="status"]').value,
        };

        if (catFormId.value) {
          var row = catTbody.querySelector('[data-category-id="' + catFormId.value + '"]');
          if (row) {
            row.querySelector('[data-category-cell="name"]').textContent = data.name;
            row.querySelector('[data-category-cell="description"]').textContent = data.description;
            row.setAttribute('data-category-name', data.name.toLowerCase());
            setEntityStatus('category', row, data.status);
          }
          if (window.UKN && window.UKN.showToast) { window.UKN.showToast('Category updated in demo mode.', 'success'); }
        } else {
          var newRow = buildCategoryRow(nextCategoryId(), data);
          catTbody.appendChild(newRow);
          if (window.UKN && window.UKN.showToast) { window.UKN.showToast('Category added in demo mode.', 'success'); }
        }

        applyCategoriesView();
        var modalEl = catForm.closest('.modal');
        if (modalEl && window.bootstrap) {
          var instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
          instance.hide();
        }
      });
    }

    applyCategoriesView();
  }

  /* =====================================================================
     Skills (admin/skills.php)
     ===================================================================== */

  var skillTable = document.querySelector('[data-skill-table]');
  if (skillTable) {
    var skillTbody = skillTable.querySelector('tbody');
    var skillSearch = document.querySelector('[data-skill-search]');
    var skillCategoryFilter = document.querySelector('[data-skill-filter="category"]');
    var skillStatusFilter = document.querySelector('[data-skill-filter="status"]');
    var skillSort = document.querySelector('[data-skill-sort]');
    var skillResultCount = document.querySelector('[data-skill-result-count]');
    var skillEmpty = document.querySelector('[data-skill-empty]');
    var skillPagination = document.querySelector('[data-skill-pagination]');
    var skillPaginationSummary = document.querySelector('[data-skill-pagination-summary]');
    var skillPaginationPages = document.querySelector('[data-skill-pagination-pages]');
    var SKILL_PAGE_SIZE = 10;
    var skillCurrentPage = 1;

    function skillRows() {
      return Array.prototype.slice.call(skillTbody.querySelectorAll('tr[data-skill-row]'));
    }

    function sortSkillRows() {
      var key = skillSort ? skillSort.value : 'name-asc';
      var rows = skillRows();
      var sorted = rows.slice().sort(function (a, b) {
        switch (key) {
          case 'name-desc':
            return b.getAttribute('data-skill-name').localeCompare(a.getAttribute('data-skill-name'));
          case 'learners':
            return parseInt(b.getAttribute('data-skill-learners'), 10) - parseInt(a.getAttribute('data-skill-learners'), 10);
          case 'mentors':
            return parseInt(b.getAttribute('data-skill-mentors'), 10) - parseInt(a.getAttribute('data-skill-mentors'), 10);
          case 'updated':
            return parseInt(b.getAttribute('data-skill-updated-sort'), 10) - parseInt(a.getAttribute('data-skill-updated-sort'), 10);
          case 'name-asc':
          default:
            return a.getAttribute('data-skill-name').localeCompare(b.getAttribute('data-skill-name'));
        }
      });
      sorted.forEach(function (row) { skillTbody.appendChild(row); });
    }

    function skillMatches(row) {
      var query = skillSearch ? skillSearch.value.trim().toLowerCase() : '';
      if (query) {
        var haystack = row.getAttribute('data-skill-name') + ' ' + row.getAttribute('data-skill-category-name') + ' ' + row.getAttribute('data-skill-description');
        if (haystack.indexOf(query) === -1) {
          return false;
        }
      }
      if (skillCategoryFilter && skillCategoryFilter.value && row.getAttribute('data-skill-category-id') !== skillCategoryFilter.value) {
        return false;
      }
      if (skillStatusFilter && skillStatusFilter.value && row.getAttribute('data-skill-status') !== skillStatusFilter.value) {
        return false;
      }
      return true;
    }

    function renderSkillPagination(total) {
      if (!skillPagination) {
        return;
      }
      var totalPages = Math.max(1, Math.ceil(total / SKILL_PAGE_SIZE));
      if (skillCurrentPage > totalPages) {
        skillCurrentPage = totalPages;
      }
      if (skillPaginationSummary) {
        if (total === 0) {
          skillPaginationSummary.textContent = '';
        } else {
          var start = (skillCurrentPage - 1) * SKILL_PAGE_SIZE + 1;
          var end = Math.min(total, skillCurrentPage * SKILL_PAGE_SIZE);
          skillPaginationSummary.textContent = 'Showing ' + start + '–' + end + ' of ' + total;
        }
      }
      if (skillPaginationPages) {
        skillPaginationPages.innerHTML = '';
        if (totalPages > 1) {
          for (var p = 1; p <= totalPages; p++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm ' + (p === skillCurrentPage ? 'btn-primary' : 'btn-outline-secondary');
            btn.textContent = String(p);
            btn.setAttribute('aria-label', 'Page ' + p);
            if (p === skillCurrentPage) {
              btn.setAttribute('aria-current', 'page');
            }
            (function (page) {
              btn.addEventListener('click', function () {
                skillCurrentPage = page;
                applySkillsView();
              });
            })(p);
            skillPaginationPages.appendChild(btn);
          }
        }
      }
      skillPagination.hidden = total === 0;
    }

    window.applySkillsView = function applySkillsView() {
      sortSkillRows();
      var rows = skillRows();
      var matched = rows.filter(skillMatches);
      var pageStart = (skillCurrentPage - 1) * SKILL_PAGE_SIZE;
      var visibleThisPage = matched.slice(pageStart, pageStart + SKILL_PAGE_SIZE);
      var visibleSet = new Set(visibleThisPage);

      rows.forEach(function (row) {
        row.hidden = !visibleSet.has(row);
      });

      if (skillResultCount) {
        skillResultCount.textContent = matched.length + (matched.length === 1 ? ' skill found' : ' skills found');
      }
      if (skillEmpty) {
        skillEmpty.hidden = matched.length !== 0;
      }
      skillTable.closest('.card').querySelector('.table-responsive').hidden = matched.length === 0;
      renderSkillPagination(matched.length);
    };

    function resetSkillsToFirstPage() {
      skillCurrentPage = 1;
      applySkillsView();
    }

    if (skillSearch) { skillSearch.addEventListener('input', resetSkillsToFirstPage); }
    [skillCategoryFilter, skillStatusFilter, skillSort].forEach(function (control) {
      if (control) { control.addEventListener('change', resetSkillsToFirstPage); }
    });
    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-skill-clear-filters]')) {
        return;
      }
      event.preventDefault();
      if (skillSearch) { skillSearch.value = ''; }
      if (skillCategoryFilter) { skillCategoryFilter.value = ''; }
      if (skillStatusFilter) { skillStatusFilter.value = ''; }
      if (skillSort) { skillSort.value = 'name-asc'; }
      resetSkillsToFirstPage();
    });

    /* ---- Add / Edit Skill ---- */

    var skillForm = document.querySelector('[data-skill-form]');
    var skillFormTitle = document.querySelector('[data-skill-form-title]');
    var skillFormSubmit = document.querySelector('[data-skill-form-submit]');
    var skillFormId = document.querySelector('[data-skill-form-id]');
    var skillFormCountsNote = document.querySelector('[data-skill-form-counts-note]');

    function nextSkillId() {
      var max = 0;
      skillRows().forEach(function (row) {
        max = Math.max(max, parseInt(row.getAttribute('data-skill-id'), 10) || 0);
      });
      return max + 1;
    }

    function categoryNameFor(categoryId) {
      var option = skillForm.querySelector('[name="categoryId"] option[value="' + categoryId + '"]');
      return option ? option.textContent.replace(' (Inactive)', '') : '';
    }

    function buildSkillRow(id, data) {
      var row = document.createElement('tr');
      row.setAttribute('data-skill-row', '');
      row.setAttribute('data-skill-id', String(id));
      row.setAttribute('data-skill-name', data.name.toLowerCase());
      row.setAttribute('data-skill-category-id', data.categoryId);
      row.setAttribute('data-skill-category-name', categoryNameFor(data.categoryId).toLowerCase());
      row.setAttribute('data-skill-description', data.description.toLowerCase());
      row.setAttribute('data-skill-status', data.status);
      row.setAttribute('data-skill-learners', '0');
      row.setAttribute('data-skill-mentors', '0');
      row.setAttribute('data-skill-updated-sort', String(id));
      var isActive = data.status === 'active';
      row.innerHTML =
        '<td data-label="Skill"><div class="fw-bold" data-skill-cell="name">' + escapeHtml(data.name) + '</div><div class="ukn-body-sm ukn-text-muted ukn-truncate ukn-clamp-2" data-skill-cell="description">' + escapeHtml(data.description) + '</div></td>' +
        '<td data-label="Category" data-skill-cell="category">' + escapeHtml(categoryNameFor(data.categoryId)) + '</td>' +
        '<td data-label="Learners">0</td>' +
        '<td data-label="Mentors">0</td>' +
        '<td data-label="Status"><span class="ukn-status ' + (isActive ? 'ukn-status-accent' : 'ukn-status-neutral') + '" data-skill-status-badge>' + (isActive ? 'Active' : 'Inactive') + '</span></td>' +
        '<td data-label="Updated">Just now</td>' +
        '<td data-label="Actions">' +
          '<div class="d-flex gap-1 justify-content-md-end">' +
            '<a href="../index.php?page=skill-details&id=' + id + '" class="btn btn-outline-secondary btn-sm">View in App</a>' +
            '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#skillFormModal" data-skill-edit>Edit</button>' +
            '<div class="dropdown">' +
              '<button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Skill actions for ' + escapeHtml(data.name) + '"><span class="ms" aria-hidden="true">more_vert</span></button>' +
              '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-skill-deactivate' + (isActive ? '' : ' hidden') + ' data-delete-title="Deactivate ' + escapeHtml(data.name) + '?" data-delete-message="This is a frontend demo. The skill status will only change in the current mock state." data-delete-confirm-label="Deactivate Skill" data-success-message="Skill deactivated in demo mode.">Deactivate</button></li>' +
                '<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-skill-activate' + (isActive ? ' hidden' : '') + ' data-delete-title="Activate ' + escapeHtml(data.name) + '?" data-delete-message="This is a frontend demo. The skill status will only change in the current mock state." data-delete-confirm-label="Activate Skill" data-success-message="Skill activated in demo mode.">Activate</button></li>' +
              '</ul>' +
            '</div>' +
          '</div>' +
        '</td>';
      return row;
    }

    function checkSkillDuplicate(form) {
      var nameField = form.querySelector('[name="name"]');
      var editingId = skillFormId.value;
      var name = nameField.value.trim().toLowerCase();
      var dup = false;
      skillRows().forEach(function (row) {
        if (editingId && row.getAttribute('data-skill-id') === editingId) {
          return;
        }
        if (name && row.getAttribute('data-skill-name') === name) {
          dup = true;
        }
      });
      if (window.UKN && window.UKN.setFieldError) {
        window.UKN.setFieldError(form, 'name', dup, nameField, dup ? 'duplicate' : null);
      }
      return !dup;
    }

    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-skill-add]')) {
        skillForm.reset();
        skillFormId.value = '';
        skillFormTitle.textContent = 'Add Skill';
        skillFormSubmit.textContent = 'Add Skill';
        if (skillFormCountsNote) { skillFormCountsNote.hidden = true; }
      }
      var editBtn = event.target.closest('[data-skill-edit]');
      if (editBtn) {
        var row = editBtn.closest('[data-skill-row]');
        skillForm.reset();
        skillFormId.value = row.getAttribute('data-skill-id');
        skillForm.querySelector('[name="name"]').value = row.querySelector('[data-skill-cell="name"]').textContent.trim();
        skillForm.querySelector('[name="categoryId"]').value = row.getAttribute('data-skill-category-id');
        skillForm.querySelector('[name="description"]').value = row.querySelector('[data-skill-cell="description"]').textContent.trim();
        skillForm.querySelector('[name="status"]').value = row.getAttribute('data-skill-status');
        skillFormTitle.textContent = 'Edit Skill';
        skillFormSubmit.textContent = 'Save Changes';
        if (skillFormCountsNote) { skillFormCountsNote.hidden = false; }
      }
    });

    if (skillForm) {
      skillForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var requiredValid = window.UKN && window.UKN.validateForm ? window.UKN.validateForm(skillForm) : true;
        var noDuplicate = checkSkillDuplicate(skillForm);
        if (!requiredValid || !noDuplicate) {
          return;
        }

        var data = {
          name: skillForm.querySelector('[name="name"]').value.trim(),
          categoryId: skillForm.querySelector('[name="categoryId"]').value,
          description: skillForm.querySelector('[name="description"]').value.trim(),
          status: skillForm.querySelector('[name="status"]').value,
        };

        if (skillFormId.value) {
          var row = skillTbody.querySelector('[data-skill-id="' + skillFormId.value + '"]');
          if (row) {
            row.querySelector('[data-skill-cell="name"]').textContent = data.name;
            row.querySelector('[data-skill-cell="description"]').textContent = data.description;
            row.querySelector('[data-skill-cell="category"]').textContent = categoryNameFor(data.categoryId);
            row.setAttribute('data-skill-name', data.name.toLowerCase());
            row.setAttribute('data-skill-category-id', data.categoryId);
            row.setAttribute('data-skill-category-name', categoryNameFor(data.categoryId).toLowerCase());
            row.setAttribute('data-skill-description', data.description.toLowerCase());
            setEntityStatus('skill', row, data.status);
          }
          if (window.UKN && window.UKN.showToast) { window.UKN.showToast('Skill updated in demo mode.', 'success'); }
        } else {
          var newRow = buildSkillRow(nextSkillId(), data);
          skillTbody.appendChild(newRow);
          if (window.UKN && window.UKN.showToast) { window.UKN.showToast('Skill added in demo mode.', 'success'); }
        }

        applySkillsView();
        var modalEl = skillForm.closest('.modal');
        if (modalEl && window.bootstrap) {
          var instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
          instance.hide();
        }
      });
    }

    applySkillsView();
  }
})();
