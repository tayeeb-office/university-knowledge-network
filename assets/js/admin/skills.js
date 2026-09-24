(function () {
  'use strict';
  // Admin taxonomy pages (Steps 45–47): client-side search/filter/sort/paging over the
  // server-rendered rows; create/edit/status/delete are real server forms.
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
    // Step 45: the form posts to the server (create or update); this only prefills it.
    var deptForm = document.querySelector('[data-department-form]');
    var deptFormTitle = document.querySelector('[data-department-form-title]');
    var deptFormSubmit = document.querySelector('[data-department-form-submit]');
    var deptFormId = document.querySelector('[data-department-form-id]');
    document.addEventListener('click', function (event) {
      if (!deptForm) {
        return;
      }
      if (event.target.closest('[data-department-add]')) {
        deptForm.reset();
        deptFormId.value = '';
        deptForm.setAttribute('action', deptForm.getAttribute('data-create-action'));
        deptFormTitle.textContent = 'Add Department';
        deptFormSubmit.textContent = 'Add Department';
      }
      var editBtn = event.target.closest('[data-department-edit]');
      if (editBtn) {
        var row = editBtn.closest('[data-department-row]');
        deptForm.reset();
        deptFormId.value = row.getAttribute('data-department-id');
        deptForm.setAttribute('action', deptForm.getAttribute('data-update-action'));
        deptForm.querySelector('[name="name"]').value = row.querySelector('[data-department-cell="name"]').textContent.trim();
        deptForm.querySelector('[name="code"]').value = row.querySelector('[data-department-cell="code"]').textContent.trim();
        deptForm.querySelector('[name="status"]').value = row.getAttribute('data-department-status');
        deptFormTitle.textContent = 'Edit Department';
        deptFormSubmit.textContent = 'Save Changes';
      }
    });
    applyDepartmentsView();
  }
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
    // Step 46: the form posts to the server (create or update); this only prefills it.
    var catForm = document.querySelector('[data-category-form]');
    var catFormTitle = document.querySelector('[data-category-form-title]');
    var catFormSubmit = document.querySelector('[data-category-form-submit]');
    var catFormId = document.querySelector('[data-category-form-id]');
    document.addEventListener('click', function (event) {
      if (!catForm) {
        return;
      }
      if (event.target.closest('[data-category-add]')) {
        catForm.reset();
        catFormId.value = '';
        catForm.setAttribute('action', catForm.getAttribute('data-create-action'));
        catFormTitle.textContent = 'Add Category';
        catFormSubmit.textContent = 'Add Category';
      }
      var editBtn = event.target.closest('[data-category-edit]');
      if (editBtn) {
        var row = editBtn.closest('[data-category-row]');
        catForm.reset();
        catFormId.value = row.getAttribute('data-category-id');
        catForm.setAttribute('action', catForm.getAttribute('data-update-action'));
        catForm.querySelector('[name="name"]').value = row.querySelector('[data-category-cell="name"]').textContent.trim();
        catForm.querySelector('[name="description"]').value = row.querySelector('[data-category-cell="description"]').textContent.trim();
        catForm.querySelector('[name="status"]').value = row.getAttribute('data-category-status');
        catFormTitle.textContent = 'Edit Category';
        catFormSubmit.textContent = 'Save Changes';
      }
    });
    applyCategoriesView();
  }
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
    // Step 47: the form posts to the server (create or update); this only prefills it.
    var skillForm = document.querySelector('[data-skill-form]');
    var skillFormTitle = document.querySelector('[data-skill-form-title]');
    var skillFormSubmit = document.querySelector('[data-skill-form-submit]');
    var skillFormId = document.querySelector('[data-skill-form-id]');
    var skillFormCountsNote = document.querySelector('[data-skill-form-counts-note]');
    // Inactive categories are disabled for new skills; a skill being edited may keep the
    // (inactive) category it already has, so that one option is re-enabled while editing.
    function setCategoryOptions(allowedInactiveId) {
      skillForm.querySelectorAll('[name="category_id"] option').forEach(function (option) {
        if (!option.hasAttribute('data-inactive')) {
          option.setAttribute('data-inactive', option.disabled ? '1' : '0');
        }
        option.disabled = option.getAttribute('data-inactive') === '1' && option.value !== allowedInactiveId;
      });
    }
    document.addEventListener('click', function (event) {
      if (!skillForm) {
        return;
      }
      if (event.target.closest('[data-skill-add]')) {
        skillForm.reset();
        skillFormId.value = '';
        setCategoryOptions('');
        skillForm.setAttribute('action', skillForm.getAttribute('data-create-action'));
        skillFormTitle.textContent = 'Add Skill';
        skillFormSubmit.textContent = 'Add Skill';
        if (skillFormCountsNote) { skillFormCountsNote.hidden = true; }
      }
      var editBtn = event.target.closest('[data-skill-edit]');
      if (editBtn) {
        var row = editBtn.closest('[data-skill-row]');
        skillForm.reset();
        skillFormId.value = row.getAttribute('data-skill-id');
        setCategoryOptions(row.getAttribute('data-skill-category-id'));
        skillForm.setAttribute('action', skillForm.getAttribute('data-update-action'));
        skillForm.querySelector('[name="name"]').value = row.querySelector('[data-skill-cell="name"]').textContent.trim();
        skillForm.querySelector('[name="category_id"]').value = row.getAttribute('data-skill-category-id');
        skillForm.querySelector('[name="description"]').value = row.querySelector('[data-skill-cell="description"]').textContent.trim();
        skillForm.querySelector('[name="status"]').value = row.getAttribute('data-skill-status');
        skillFormTitle.textContent = 'Edit Skill';
        skillFormSubmit.textContent = 'Save Changes';
        if (skillFormCountsNote) { skillFormCountsNote.hidden = false; }
      }
    });
    applySkillsView();
  }
})();
