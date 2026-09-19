<?php
/**
 * Admin Departments Management. Reuses the shared
 * admin/includes/taxonomy-data.php mock dataset (also used by
 * admin/skill-categories.php and admin/skills.php) so all three Admin
 * taxonomy pages agree on the same names/counts.
 *
 * Add/Edit uses ONE reusable modal (#departmentFormModal) for both
 * actions rather than a modal per row; Deactivate/Activate reuses the
 * one shared modals/delete-confirmation-modal.php exactly like Suspend/
 * Restore did in admin/users.php. Deactivating a department with
 * assigned users is allowed (it's reversible, mock-only) but the
 * confirmation message always states the impact plainly rather than
 * silently proceeding — this project deliberately does not implement a
 * destructive Delete for Departments at all (deactivation is the
 * preferred, safer action per this prompt's own repeated guidance).
 *
 * Frontend-only: assets/js/admin/skills.js does all search/filter/Add/
 * Edit/status-toggle work against this server-rendered table; nothing
 * here calls a backend or persists past a page refresh.
 */
require_once __DIR__ . '/includes/taxonomy-data.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/stat-card.php';

$adminActiveNav = 'departments';
$adminPageTitle = 'Departments';
$adminPageSub = 'Manage the academic departments available across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/skills.js'];

$departments = ukn_admin_mock_departments();
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral'];

$totalUsers = array_sum(array_column($departments, 'users'));
$largest = null;
foreach ($departments as $dept) {
    if ($largest === null || $dept['users'] > $largest['users']) {
        $largest = $dept;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#departmentFormModal" data-department-add>
    <span class="ms" aria-hidden="true">add</span> Add Department
  </button>
</div>

<div class="ukn-admin-stat-grid mb-4">
  <?php
  ukn_stat_card(['label' => 'Total Departments', 'value' => (string) count($departments), 'icon' => 'apartment']);
  ukn_stat_card(['label' => 'Active', 'value' => (string) count(array_filter($departments, static fn ($d) => $d['status'] === 'active')), 'icon' => 'check_circle']);
  ukn_stat_card(['label' => 'Total Users', 'value' => number_format($totalUsers), 'icon' => 'group']);
  ukn_stat_card(['label' => 'Largest Department', 'value' => $largest['name'], 'icon' => 'trending_up', 'helper' => number_format($largest['users']) . ' users']);
  ?>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="ukn-admin-filters">
      <div class="ukn-search ukn-admin-filters__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="departmentSearchInput" class="ukn-visually-hidden">Search departments by name or code</label>
        <input type="search" id="departmentSearchInput" class="form-control" placeholder="Search departments…" data-department-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="departmentStatusFilter">Filter by status</label>
      <select id="departmentStatusFilter" class="form-select form-select-sm" data-department-filter="status">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-department-clear-filters>Clear Filters</button>
    </div>
  </div>
</div>

<p class="ukn-body-sm ukn-text-muted" data-department-result-count role="status"><?= count($departments) ?> departments found</p>

<div class="card">
  <div class="table-responsive">
    <table class="table ukn-admin-table" data-department-table>
      <thead>
        <tr>
          <th scope="col">Department</th>
          <th scope="col">Code</th>
          <th scope="col">Users</th>
          <th scope="col">Learners</th>
          <th scope="col">Mentors</th>
          <th scope="col">Status</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($departments as $id => $dept): $isActive = $dept['status'] === 'active'; ?>
          <tr
            data-department-row
            data-department-id="<?= $id ?>"
            data-department-name="<?= htmlspecialchars(strtolower($dept['name'])) ?>"
            data-department-code="<?= htmlspecialchars(strtolower($dept['code'])) ?>"
            data-department-status="<?= htmlspecialchars($dept['status']) ?>"
          >
            <td data-label="Department" class="fw-bold" data-department-cell="name"><?= htmlspecialchars($dept['name']) ?></td>
            <td data-label="Code" data-department-cell="code"><?= htmlspecialchars($dept['code']) ?></td>
            <td data-label="Users" data-department-cell="users"><?= number_format($dept['users']) ?></td>
            <td data-label="Learners"><?= number_format($dept['learners']) ?></td>
            <td data-label="Mentors"><?= number_format($dept['mentors']) ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$dept['status']] ?>" data-department-status-badge><?= htmlspecialchars($statusLabels[$dept['status']]) ?></span></td>
            <td data-label="Actions">
              <div class="d-flex gap-1 justify-content-md-end">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  data-bs-toggle="modal"
                  data-bs-target="#departmentFormModal"
                  data-department-edit
                >Edit</button>
                <div class="dropdown">
                  <button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Department actions for <?= htmlspecialchars($dept['name']) ?>">
                    <span class="ms" aria-hidden="true">more_vert</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-department-deactivate
                        <?= $isActive ? '' : 'hidden' ?>
                        data-delete-title="Deactivate <?= htmlspecialchars($dept['name']) ?>?"
                        data-delete-message="This department has <?= number_format($dept['users']) ?> assigned users. Deactivating is simulated only and will not remove those users or their data."
                        data-delete-confirm-label="Deactivate Department"
                        data-success-message="Department deactivated in demo mode."
                      >Deactivate</button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-department-activate
                        <?= $isActive ? 'hidden' : '' ?>
                        data-delete-title="Activate <?= htmlspecialchars($dept['name']) ?>?"
                        data-delete-message="This is a frontend demo. The department status will only change in the current mock state."
                        data-delete-confirm-label="Activate Department"
                        data-success-message="Department activated in demo mode."
                      >Activate</button>
                    </li>
                  </ul>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card-body" hidden data-department-empty>
    <?php ukn_empty_state([
        'icon' => 'apartment',
        'title' => 'No departments found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-department-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
</div>

<div class="modal fade" id="departmentFormModal" tabindex="-1" aria-labelledby="departmentFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="departmentFormModalLabel" data-department-form-title>Add Department</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <form data-department-form novalidate>
        <div class="modal-body">
          <input type="hidden" data-department-form-id>
          <div class="ukn-form-group">
            <label for="departmentNameInput" class="form-label">Department Name</label>
            <input type="text" class="form-control" id="departmentNameInput" name="name" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="name" data-message-required="Department name is required." data-message-duplicate="A department with this name already exists." hidden>
              <span class="ms" aria-hidden="true">error</span><span data-message-text>Department name is required.</span>
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="departmentCodeInput" class="form-label">Department Code</label>
            <input type="text" class="form-control" id="departmentCodeInput" name="code" maxlength="10" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="code" data-message-required="Department code is required." data-message-duplicate="A department with this code already exists." hidden>
              <span class="ms" aria-hidden="true">error</span><span data-message-text>Department code is required.</span>
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="departmentStatusInput" class="form-label">Status</label>
            <select class="form-select" id="departmentStatusInput" name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" data-department-form-submit>Add Department</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
