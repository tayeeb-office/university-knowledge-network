<?php
require_once __DIR__ . '/includes/users-data.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/stat-card.php';
$adminActiveNav = 'users';
$adminPageTitle = 'Users';
$adminPageSub = 'Manage learners, mentors and account status across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/users.js'];
$users = ukn_admin_mock_users();
$roleLabels = ['learner' => 'Learner', 'mentor' => 'Mentor', 'dual' => 'Dual Role'];
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral', 'suspended' => 'ukn-status-neutral'];
$departments = [];
foreach ($users as $u) {
    $departments[$u['department']] = true;
}
$departments = array_keys($departments);
sort($departments);
require __DIR__ . '/includes/header.php';
?>
<div class="ukn-admin-stat-grid mb-4">
  <?php
  ukn_stat_card(['label' => 'Total Users', 'value' => '1,152', 'icon' => 'group']);
  ukn_stat_card(['label' => 'Learners', 'value' => '986', 'icon' => 'school']);
  ukn_stat_card(['label' => 'Mentors', 'value' => '214', 'icon' => 'record_voice_over']);
  ukn_stat_card(['label' => 'Dual Role', 'value' => '48', 'icon' => 'swap_horiz']);
  ukn_stat_card(['label' => 'Active', 'value' => '1,120', 'icon' => 'check_circle']);
  ukn_stat_card(['label' => 'Suspended', 'value' => '12', 'icon' => 'block']);
  ?>
</div>
<div class="card mb-3">
  <div class="card-body">
    <div class="ukn-admin-filters">
      <div class="ukn-search ukn-admin-filters__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="userSearchInput" class="ukn-visually-hidden">Search users by name, email or university ID</label>
        <input type="search" id="userSearchInput" class="form-control" placeholder="Search users by name, email or university ID…" data-user-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="userRoleFilter">Filter by role</label>
      <select id="userRoleFilter" class="form-select form-select-sm" data-user-filter="role">
        <option value="">All Roles</option>
        <option value="learner">Learner</option>
        <option value="mentor">Mentor</option>
        <option value="dual">Dual Role</option>
      </select>
      <label class="ukn-visually-hidden" for="userDepartmentFilter">Filter by department</label>
      <select id="userDepartmentFilter" class="form-select form-select-sm" data-user-filter="department">
        <option value="">All Departments</option>
        <?php foreach ($departments as $dept): ?>
          <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
        <?php endforeach; ?>
      </select>

      <label class="ukn-visually-hidden" for="userStatusFilter">Filter by status</label>
      <select id="userStatusFilter" class="form-select form-select-sm" data-user-filter="status">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="suspended">Suspended</option>
      </select>

      <label class="ukn-visually-hidden" for="userSort">Sort users</label>
      <select id="userSort" class="form-select form-select-sm" data-user-sort>
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="name-asc">Name A–Z</option>
        <option value="name-desc">Name Z–A</option>
        <option value="active">Most Active</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-user-clear-filters>Clear Filters</button>
    </div>
  </div>
</div>
<p class="ukn-body-sm ukn-text-muted" data-user-result-count role="status"><?= count($users) ?> users found</p>

<div class="card">
  <div class="table-responsive">
    <table class="table ukn-admin-table" data-user-table>
      <thead>
        <tr>
          <th scope="col">User</th>
          <th scope="col">University ID</th>
          <th scope="col">Role</th>
          <th scope="col">Department</th>
          <th scope="col">Status</th>
          <th scope="col">Joined</th>
          <th scope="col">Sessions</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $id => $user):
            $sessions = ($user['learner']['sessions'] ?? 0) + ($user['mentor']['sessions'] ?? 0);
            $isSuspended = $user['status'] === 'suspended';
        ?>
          <tr
            data-user-row
            data-user-id="<?= $id ?>"
            data-user-name="<?= htmlspecialchars(strtolower($user['name'])) ?>"
            data-user-email="<?= htmlspecialchars(strtolower($user['email'])) ?>"
            data-user-uid="<?= htmlspecialchars(strtolower($user['universityId'])) ?>"
            data-user-role="<?= htmlspecialchars($user['role']) ?>"
            data-user-department="<?= htmlspecialchars($user['department']) ?>"
            data-user-status="<?= htmlspecialchars($user['status']) ?>"
            data-user-joined-sort="<?= htmlspecialchars($user['joinedSort']) ?>"
            data-user-sessions="<?= (int) $sessions ?>"
          >
            <td data-label="User">
              <div class="d-flex align-items-center gap-2">
                <span class="ukn-avatar flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($user['initials']) ?></span>
                <span class="ukn-min-w-0">
                  <span class="d-block fw-bold ukn-truncate"><?= htmlspecialchars($user['name']) ?></span>
                  <span class="d-block ukn-body-sm ukn-text-muted ukn-truncate"><?= htmlspecialchars($user['email']) ?></span>
                </span>
              </div>
            </td>
            <td data-label="University ID"><?= htmlspecialchars($user['universityId']) ?></td>
            <td data-label="Role"><?= htmlspecialchars($roleLabels[$user['role']]) ?></td>
            <td data-label="Department"><?= htmlspecialchars($user['department']) ?></td>
            <td data-label="Status">
              <span class="ukn-status <?= $statusClass[$user['status']] ?>" data-user-status-badge><?= htmlspecialchars($statusLabels[$user['status']]) ?></span>
            </td>
            <td data-label="Joined"><?= htmlspecialchars($user['joined']) ?></td>
            <td data-label="Sessions"><?= (int) $sessions ?></td>
            <td data-label="Actions">
              <div class="d-flex gap-1 justify-content-md-end">
                <a href="user-details.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">View</a>
                <div class="dropdown">
                  <button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User actions for <?= htmlspecialchars($user['name']) ?>">
                    <span class="ms" aria-hidden="true">more_vert</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-suspend-user
                        <?= $isSuspended ? 'hidden' : '' ?>
                        data-delete-title="Suspend <?= htmlspecialchars($user['name']) ?>?"
                        data-delete-message="This is a frontend demo. The account status will only change in the current mock state."
                        data-delete-confirm-label="Suspend User"
                        data-success-message="User suspended in demo mode."
                      >Suspend</button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-restore-user
                        <?= $isSuspended ? '' : 'hidden' ?>
                        data-delete-title="Restore <?= htmlspecialchars($user['name']) ?>?"
                        data-delete-message="This is a frontend demo. The account status will only change in the current mock state."
                        data-delete-confirm-label="Restore User"
                        data-success-message="User restored in demo mode."
                      >Restore</button>
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
  <div class="card-body" hidden data-user-empty>
    <?php ukn_empty_state([
        'icon' => 'person_search',
        'title' => 'No users found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-user-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
  <div class="card-body ukn-admin-pagination" data-user-pagination>
    <span class="ukn-body-sm ukn-text-muted" data-user-pagination-summary></span>
    <div class="d-flex gap-1" data-user-pagination-pages></div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>