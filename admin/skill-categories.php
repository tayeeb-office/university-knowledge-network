<?php
require_once __DIR__ . '/../backend/helpers/auth.php';
requireAdmin();
require_once __DIR__ . '/../backend/helpers/csrf.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'skill-categories';
$adminPageTitle = 'Skill Categories';
$adminPageSub = 'Organize skills into clear categories for learners and mentors.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/skills.js'];
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral'];

$categories = [];
$skillCounts = [];
$totalSkills = 0;
$categoriesDbError = false;

try {
    $pdo = getDatabaseConnection();
    // Step 46: skill counts grouped once for all categories.
    $stmt = $pdo->query(
        "SELECT sc.id, sc.name, sc.description, sc.status, sc.updated_at, COUNT(s.id) AS skill_count
         FROM skill_categories sc
         LEFT JOIN skills s ON s.category_id = sc.id
         GROUP BY sc.id, sc.name, sc.description, sc.status, sc.updated_at
         ORDER BY sc.name"
    );
    foreach ($stmt->fetchAll() as $row) {
        $row['updated'] = date('M j, Y', strtotime($row['updated_at']));
        $skillCounts[$row['id']] = (int) $row['skill_count'];
        $categories[$row['id']] = $row;
        $totalSkills += (int) $row['skill_count'];
    }
} catch (Throwable $e) {
    error_log('[UKN admin/skill-categories] ' . $e->getMessage());
    $categoriesDbError = true;
    $categories = [];
    $skillCounts = [];
}
require __DIR__ . '/includes/header.php';
?>
<?php if ($categoriesDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load skill categories.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryFormModal" data-category-add>
    <span class="ms" aria-hidden="true">add</span> Add Category
  </button>
</div>

<div class="ukn-admin-stat-grid mb-4">
  <?php
  ukn_stat_card(['label' => 'Total Categories', 'value' => (string) count($categories), 'icon' => 'category']);
  ukn_stat_card(['label' => 'Active Categories', 'value' => (string) count(array_filter($categories, static fn ($c) => $c['status'] === 'active')), 'icon' => 'check_circle']);
  ukn_stat_card(['label' => 'Total Skills', 'value' => (string) $totalSkills, 'icon' => 'workspaces']);
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
        <?php foreach ($categories as $id => $cat): $isActive = $cat['status'] === 'active'; $count = $skillCounts[$id]; $id = (int) $id; ?>
          <tr
            data-category-row
            data-category-id="<?= $id ?>"
            data-category-name="<?= htmlspecialchars(mb_strtolower($cat['name'], 'UTF-8')) ?>"
            data-category-status="<?= htmlspecialchars($cat['status']) ?>"
            data-category-skill-count="<?= $count ?>"
          >
            <td data-label="Category" class="fw-bold" data-category-cell="name"><?= htmlspecialchars($cat['name']) ?></td>
            <td data-label="Description" class="ukn-body-sm ukn-text-muted" data-category-cell="description"><?= htmlspecialchars($cat['description']) ?></td>
            <td data-label="Skills"><?= $count ?> Skill<?= $count === 1 ? '' : 's' ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$cat['status']] ?>" data-category-status-badge><?= htmlspecialchars($statusLabels[$cat['status']]) ?></span></td>
            <td data-label="Updated"><?= htmlspecialchars($cat['updated']) ?></td>
            <td data-label="Actions">
              <form action="../backend/admin/skill-categories/status.php" method="post" id="categoryStatus-<?= $id ?>" hidden>
                <?= csrfField() ?>
                <?= uknReturnToField() ?>
                <input type="hidden" name="category_id" value="<?= $id ?>">
                <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
              </form>
              <?php if ($count === 0): ?>
                <form action="../backend/admin/skill-categories/delete.php" method="post" id="categoryDelete-<?= $id ?>" hidden>
                  <?= csrfField() ?>
                  <?= uknReturnToField() ?>
                  <input type="hidden" name="category_id" value="<?= $id ?>">
                </form>
              <?php endif; ?>
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
                        data-delete-form="categoryStatus-<?= $id ?>"
                        <?php if ($isActive): ?>
                        data-delete-title="Deactivate <?= htmlspecialchars($cat['name']) ?>?"
                        data-delete-message="<?= htmlspecialchars($count > 0
                            ? 'The category is hidden from category filters and cannot receive new skills. Its ' . $count . ' skill' . ($count === 1 ? '' : 's') . ' keep this category and their own status.'
                            : 'The category is hidden from category filters and cannot receive new skills.') ?>"
                        data-delete-confirm-label="Deactivate Category"
                        <?php else: ?>
                        data-delete-title="Activate <?= htmlspecialchars($cat['name']) ?>?"
                        data-delete-message="The category becomes available in filters and for new skills again."
                        data-delete-confirm-label="Activate Category"
                        <?php endif; ?>
                      ><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                    </li>
                    <?php if ($count === 0): ?>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item ukn-text-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-delete-form="categoryDelete-<?= $id ?>"
                        data-delete-title="Delete <?= htmlspecialchars($cat['name']) ?>?"
                        data-delete-message="This category has no skills. It will be permanently deleted."
                        data-delete-confirm-label="Delete Category"
                      >Delete</button>
                    </li>
                    <?php endif; ?>
                  </ul>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-body"<?= $categories ? ' hidden' : '' ?> data-category-empty>
    <?php ukn_empty_state([
        'icon' => 'category',
        'title' => 'No skill categories found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-category-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
</div>
<?php endif; ?>
<div class="modal fade" id="categoryFormModal" tabindex="-1" aria-labelledby="categoryFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="categoryFormModalLabel" data-category-form-title>Add Category</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <form action="../backend/admin/skill-categories/create.php" method="post" data-category-form data-validated-form novalidate
            data-create-action="../backend/admin/skill-categories/create.php" data-update-action="../backend/admin/skill-categories/update.php">
        <?= csrfField() ?>
        <?= uknReturnToField() ?>
        <div class="modal-body">
          <input type="hidden" name="category_id" value="" data-category-form-id>
          <div class="ukn-form-group">
            <label for="categoryNameInput" class="form-label">Category Name</label>
            <input type="text" class="form-control" id="categoryNameInput" name="name" maxlength="60" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="name" data-message-required="Category name is required." hidden>
              <span class="ms" aria-hidden="true">error</span><span data-message-text>Category name is required.</span>
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="categoryDescriptionInput" class="form-label">Description</label>
            <textarea class="form-control" id="categoryDescriptionInput" name="description" rows="3" maxlength="255" data-validate="required"></textarea>
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