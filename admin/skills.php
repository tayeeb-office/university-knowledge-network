<?php
/**
 * Admin Skills Management — the network's skill catalogue. Reuses the
 * shared admin/includes/taxonomy-data.php mock dataset; the category
 * filter and the Add/Edit form's category <select> both read
 * ukn_admin_mock_categories() directly rather than keeping a second
 * hardcoded category list (see that file's own docblock).
 *
 * Learner/Mentor counts are read-only demo metrics here (no field to
 * type them into) — only Name/Category/Description/Status are editable,
 * per this prompt's explicit "these counts conceptually come from
 * platform activity" instruction. "View in App" safely links to the
 * existing user-facing ?page=skill-details&id=... route, separate from
 * Admin Edit. Deactivate/Activate reuses the shared
 * modals/delete-confirmation-modal.php; permanent deletion of an
 * established, in-use skill is deliberately not implemented at all.
 */
require_once __DIR__ . '/includes/taxonomy-data.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/stat-card.php';

$adminActiveNav = 'skills';
$adminPageTitle = 'Skills';
$adminPageSub = 'Manage the skills learners can learn and mentors can teach.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/skills.js'];

$categories = ukn_admin_mock_categories();
$skills = ukn_admin_mock_skills();
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral'];

$mostPopular = null;
foreach ($skills as $skill) {
    if ($mostPopular === null || $skill['learners'] > $mostPopular['learners']) {
        $mostPopular = $skill;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#skillFormModal" data-skill-add>
    <span class="ms" aria-hidden="true">add</span> Add Skill
  </button>
</div>

<div class="ukn-admin-stat-grid mb-4">
  <?php
  ukn_stat_card(['label' => 'Total Skills', 'value' => (string) count($skills), 'icon' => 'workspaces']);
  ukn_stat_card(['label' => 'Active Skills', 'value' => (string) count(array_filter($skills, static fn ($s) => $s['status'] === 'active')), 'icon' => 'check_circle']);
  ukn_stat_card(['label' => 'Categories', 'value' => (string) count($categories), 'icon' => 'category']);
  ukn_stat_card(['label' => 'Most Popular', 'value' => $mostPopular['name'], 'icon' => 'trending_up', 'helper' => number_format($mostPopular['learners']) . ' learners']);
  ?>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="ukn-admin-filters">
      <div class="ukn-search ukn-admin-filters__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="skillSearchInput" class="ukn-visually-hidden">Search skills by name, category or description</label>
        <input type="search" id="skillSearchInput" class="form-control" placeholder="Search skills…" data-skill-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="skillCategoryFilter">Filter by category</label>
      <select id="skillCategoryFilter" class="form-select form-select-sm" data-skill-filter="category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $id => $cat): ?>
          <option value="<?= $id ?>"><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="ukn-visually-hidden" for="skillStatusFilter">Filter by status</label>
      <select id="skillStatusFilter" class="form-select form-select-sm" data-skill-filter="status">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <label class="ukn-visually-hidden" for="skillSort">Sort skills</label>
      <select id="skillSort" class="form-select form-select-sm" data-skill-sort>
        <option value="name-asc">Name A–Z</option>
        <option value="name-desc">Name Z–A</option>
        <option value="learners">Most Learners</option>
        <option value="mentors">Most Mentors</option>
        <option value="updated">Recently Updated</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-skill-clear-filters>Clear Filters</button>
    </div>
  </div>
</div>

<p class="ukn-body-sm ukn-text-muted" data-skill-result-count role="status"><?= count($skills) ?> skills found</p>

<div class="card">
  <div class="table-responsive">
    <table class="table ukn-admin-table" data-skill-table>
      <thead>
        <tr>
          <th scope="col">Skill</th>
          <th scope="col">Category</th>
          <th scope="col">Learners</th>
          <th scope="col">Mentors</th>
          <th scope="col">Status</th>
          <th scope="col">Updated</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($skills as $id => $skill): $isActive = $skill['status'] === 'active'; $cat = $categories[$skill['categoryId']]; ?>
          <tr
            data-skill-row
            data-skill-id="<?= $id ?>"
            data-skill-name="<?= htmlspecialchars(strtolower($skill['name'])) ?>"
            data-skill-category-id="<?= $skill['categoryId'] ?>"
            data-skill-category-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>"
            data-skill-description="<?= htmlspecialchars(strtolower($skill['description'])) ?>"
            data-skill-status="<?= htmlspecialchars($skill['status']) ?>"
            data-skill-learners="<?= (int) $skill['learners'] ?>"
            data-skill-mentors="<?= (int) $skill['mentors'] ?>"
            data-skill-updated-sort="<?= (int) $id ?>"
          >
            <td data-label="Skill">
              <div class="fw-bold" data-skill-cell="name"><?= htmlspecialchars($skill['name']) ?></div>
              <div class="ukn-body-sm ukn-text-muted ukn-truncate ukn-clamp-2" data-skill-cell="description"><?= htmlspecialchars($skill['description']) ?></div>
            </td>
            <td data-label="Category" data-skill-cell="category"><?= htmlspecialchars($cat['name']) ?></td>
            <td data-label="Learners"><?= number_format($skill['learners']) ?></td>
            <td data-label="Mentors"><?= number_format($skill['mentors']) ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$skill['status']] ?>" data-skill-status-badge><?= htmlspecialchars($statusLabels[$skill['status']]) ?></span></td>
            <td data-label="Updated"><?= htmlspecialchars($skill['updated']) ?></td>
            <td data-label="Actions">
              <div class="d-flex gap-1 justify-content-md-end">
                <a href="../index.php?page=skill-details&id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">View in App</a>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#skillFormModal" data-skill-edit>Edit</button>
                <div class="dropdown">
                  <button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Skill actions for <?= htmlspecialchars($skill['name']) ?>">
                    <span class="ms" aria-hidden="true">more_vert</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-skill-deactivate
                        <?= $isActive ? '' : 'hidden' ?>
                        data-delete-title="Deactivate <?= htmlspecialchars($skill['name']) ?>?"
                        data-delete-message="<?= htmlspecialchars($skill['name']) ?> is currently associated with <?= number_format($skill['learners']) ?> learners and <?= number_format($skill['mentors']) ?> mentors. Deactivation is simulated only and will not affect their learning/teaching skills."
                        data-delete-confirm-label="Deactivate Skill"
                        data-success-message="Skill deactivated in demo mode."
                      >Deactivate</button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-skill-activate
                        <?= $isActive ? 'hidden' : '' ?>
                        data-delete-title="Activate <?= htmlspecialchars($skill['name']) ?>?"
                        data-delete-message="This is a frontend demo. The skill status will only change in the current mock state."
                        data-delete-confirm-label="Activate Skill"
                        data-success-message="Skill activated in demo mode."
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

  <div class="card-body" hidden data-skill-empty>
    <?php ukn_empty_state([
        'icon' => 'workspaces',
        'title' => 'No skills found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-skill-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>

  <div class="card-body ukn-admin-pagination" data-skill-pagination>
    <span class="ukn-body-sm ukn-text-muted" data-skill-pagination-summary></span>
    <div class="d-flex gap-1" data-skill-pagination-pages></div>
  </div>
</div>

<div class="modal fade" id="skillFormModal" tabindex="-1" aria-labelledby="skillFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="skillFormModalLabel" data-skill-form-title>Add Skill</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <form data-skill-form novalidate>
        <div class="modal-body">
          <input type="hidden" data-skill-form-id>
          <div class="ukn-form-group">
            <label for="skillNameInput" class="form-label">Skill Name</label>
            <input type="text" class="form-control" id="skillNameInput" name="name" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="name" data-message-required="Skill name is required." data-message-duplicate="A skill with this name already exists." hidden>
              <span class="ms" aria-hidden="true">error</span><span data-message-text>Skill name is required.</span>
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="skillCategoryInput" class="form-label">Category</label>
            <select class="form-select" id="skillCategoryInput" name="categoryId">
              <?php foreach ($categories as $id => $cat): ?>
                <option value="<?= $id ?>"<?= $cat['status'] !== 'active' ? ' disabled' : '' ?>><?= htmlspecialchars($cat['name']) ?><?= $cat['status'] !== 'active' ? ' (Inactive)' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ukn-form-group">
            <label for="skillDescriptionInput" class="form-label">Description</label>
            <textarea class="form-control" id="skillDescriptionInput" name="description" rows="3" data-validate="required"></textarea>
            <div class="ukn-field-message is-invalid" data-error-for="description" hidden>
              <span class="ms" aria-hidden="true">error</span>Description is required.
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="skillStatusInput" class="form-label">Status</label>
            <select class="form-select" id="skillStatusInput" name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <p class="ukn-body-sm ukn-text-muted mb-0" data-skill-form-counts-note hidden>Learners/Mentors counts are read-only platform metrics and can't be edited here.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" data-skill-form-submit>Add Skill</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
