<?php
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'departments';
$adminPageTitle = 'Departments';
$adminPageSub = 'Manage the academic departments available across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/skills.js'];
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral'];

$departments = [];
$totalUsers = 0;
$largest = null;
$departmentsDbError = false;

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query(
        "SELECT d.id, d.name, d.code, d.status,
                (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id) AS users,
                (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.role IN ('learner','dual')) AS learners,
                (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.role IN ('mentor','dual')) AS mentors
         FROM departments d
         ORDER BY d.name"
    );
    foreach ($stmt->fetchAll() as $row) {
        $row['users'] = (int) $row['users'];
        $row['learners'] = (int) $row['learners'];
        $row['mentors'] = (int) $row['mentors'];
        $departments[$row['id']] = $row;
        $totalUsers += $row['users'];
        if ($largest === null || $row['users'] > $largest['users']) {
            $largest = $row;
        }
    }
} catch (Throwable $e) {
    error_log('[UKN admin/departments] ' . $e->getMessage());
    $departmentsDbError = true;
    $departments = [];
}
require __DIR__ . '/includes/header.php';
?>
<?php if ($departmentsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load departments.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
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
  ukn_stat_card($largest !== null
      ? ['label' => 'Largest Department', 'value' => $largest['name'], 'icon' => 'trending_up', 'helper' => number_format($largest['users']) . ' users']
      : ['label' => 'Largest Department', 'value' => '—', 'icon' => 'trending_up']);
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
  <div class="card-body"<?= $departments ? ' hidden' : '' ?> data-department-empty>
    <?php ukn_empty_state([
        'icon' => 'apartment',
        'title' => 'No departments found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-department-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
</div>
<?php endif; ?>
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