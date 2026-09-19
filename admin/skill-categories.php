<?php
/**
 * Admin Skill Categories Management. Reuses the shared
 * admin/includes/taxonomy-data.php mock dataset — each category's
 * displayed Skill Count is COUNTED from ukn_admin_mock_skills() at
 * render time (never a separately hand-typed number), so it can never
 * drift from what admin/skills.php actually shows for that category.
 *
 * Add/Edit uses one reusable modal (#categoryFormModal); Deactivate/
 * Activate reuses the shared modals/delete-confirmation-modal.php.
 * Deleting a category is deliberately not implemented at all — a
 * category with assigned skills is protected by saying so plainly in
 * the Deactivate confirmation message instead (deactivation is
 * reversible and mock-only; this project does not implement a
 * destructive category Delete per this prompt's own guidance to prefer
 * deactivation).
 */
require_once __DIR__ . '/includes/taxonomy-data.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/stat-card.php';

$adminActiveNav = 'skill-categories';
$adminPageTitle = 'Skill Categories';
$adminPageSub = 'Organize skills into clear categories for learners and mentors.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/skills.js'];

$categories = ukn_admin_mock_categories();
$skills = ukn_admin_mock_skills();
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral'];

$skillCounts = array_fill_keys(array_keys($categories), 0);
foreach ($skills as $skill) {
    if (isset($skillCounts[$skill['categoryId']])) {
        $skillCounts[$skill['categoryId']]++;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryFormModal" data-category-add>
    <span class="ms" aria-hidden="true">add</span> Add Category
  </button>
</div>

<div class="ukn-admin-stat-grid mb-4">
  <?php
  ukn_stat_card(['label' => 'Total Categories', 'value' => (string) count($categories), 'icon' => 'category']);
  ukn_stat_card(['label' => 'Active Categories', 'value' => (string) count(array_filter($categories, static fn ($c) => $c['status'] === 'active')), 'icon' => 'check_circle']);
  ukn_stat_card(['label' => 'Total Skills', 'value' => (string) count($skills), 'icon' => 'workspaces']);
  ?>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="ukn-admin-filters">
      <div class="ukn-search ukn-admin-filters__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="categorySearchInput" class="ukn-visually-hidden">Search categories by name</label>
        <input type="search" id="categorySearchInput" class="form-control" placeholder="Search categories…" data-category-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="categoryStatusFilter">Filter by status</label>
      <select id="categoryStatusFilter" class="form-select form-select-sm" data-category-filter="status">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-category-clear-filters>Clear Filters</button>
    </div>
  </div>
</div>

<p class="ukn-body-sm ukn-text-muted" data-category-result-count role="status"><?= count($categories) ?> categories found</p>

<div class="card">
  <div class="table-responsive">
    <table class="table ukn-admin-table" data-category-table>
      <thead>
        <tr>
          <th scope="col">Category</th>
          <th scope="col">Description</th>
          <th scope="col">Skills</th>
          <th scope="col">Status</th>
          <th scope="col">Updated</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $id => $cat): $isActive = $cat['status'] === 'active'; $count = $skillCounts[$id]; ?>
          <tr
            data-category-row
            data-category-id="<?= $id ?>"
            data-category-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>"
            data-category-status="<?= htmlspecialchars($cat['status']) ?>"
            data-category-skill-count="<?= $count ?>"
          >
            <td data-label="Category" class="fw-bold" data-category-cell="name"><?= htmlspecialchars($cat['name']) ?></td>
            <td data-label="Description" class="ukn-body-sm ukn-text-muted" data-category-cell="description"><?= htmlspecialchars($cat['description']) ?></td>
            <td data-label="Skills"><?= $count ?> Skill<?= $count === 1 ? '' : 's' ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$cat['status']] ?>" data-category-status-badge><?= htmlspecialchars($statusLabels[$cat['status']]) ?></span></td>
            <td data-label="Updated"><?= htmlspecialchars($cat['updated']) ?></td>
            <td data-label="Actions">
              <div class="d-flex gap-1 justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryFormModal" data-category-edit>Edit</button>
                <div class="dropdown">
                  <button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Category actions for <?= htmlspecialchars($cat['name']) ?>">
                    <span class="ms" aria-hidden="true">more_vert</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-category-deactivate
                        <?= $isActive ? '' : 'hidden' ?>
                        data-delete-title="Deactivate <?= htmlspecialchars($cat['name']) ?>?"
                        data-delete-message="<?= $count > 0 ? htmlspecialchars($cat['name']) . ' contains ' . $count . ' skill' . ($count === 1 ? '' : 's') . '. Deactivating is simulated only — those skills will keep showing this category.' : 'This is a frontend demo. The category status will only change in the current mock state.' ?>"
                        data-delete-confirm-label="Deactivate Category"
                        data-success-message="Category deactivated in demo mode."
                      >Deactivate</button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-category-activate
                        <?= $isActive ? 'hidden' : '' ?>
                        data-delete-title="Activate <?= htmlspecialchars($cat['name']) ?>?"
                        data-delete-message="This is a frontend demo. The category status will only change in the current mock state."
                        data-delete-confirm-label="Activate Category"
                        data-success-message="Category activated in demo mode."
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

  <div class="card-body" hidden data-category-empty>
    <?php ukn_empty_state([
        'icon' => 'category',
        'title' => 'No skill categories found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-category-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
</div>

<div class="modal fade" id="categoryFormModal" tabindex="-1" aria-labelledby="categoryFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="categoryFormModalLabel" data-category-form-title>Add Category</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <form data-category-form novalidate>
        <div class="modal-body">
          <input type="hidden" data-category-form-id>
          <div class="ukn-form-group">
            <label for="categoryNameInput" class="form-label">Category Name</label>
            <input type="text" class="form-control" id="categoryNameInput" name="name" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="name" data-message-required="Category name is required." data-message-duplicate="A category with this name already exists." hidden>
              <span class="ms" aria-hidden="true">error</span><span data-message-text>Category name is required.</span>
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="categoryDescriptionInput" class="form-label">Description</label>
            <textarea class="form-control" id="categoryDescriptionInput" name="description" rows="3" data-validate="required"></textarea>
            <div class="ukn-field-message is-invalid" data-error-for="description" hidden>
              <span class="ms" aria-hidden="true">error</span>Description is required.
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="categoryStatusInput" class="form-label">Status</label>
            <select class="form-select" id="categoryStatusInput" name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" data-category-form-submit>Add Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
