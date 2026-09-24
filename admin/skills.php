<?php
require_once __DIR__ . '/../backend/helpers/auth.php';
requireAdmin();
require_once __DIR__ . '/../backend/helpers/csrf.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'skills';
$adminPageTitle = 'Skills';
$adminPageSub = 'Manage the skills learners can learn and mentors can teach.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/skills.js'];
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral'];

$categories = [];
$skills = [];
$mostPopular = null;
$skillsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $catStmt = $pdo->query("SELECT id, name, status FROM skill_categories ORDER BY name");
    foreach ($catStmt->fetchAll() as $row) {
        $categories[$row['id']] = $row;
    }

    // Step 47: learner/mentor counts and "is anything still using this skill" in grouped
    // joins (one pass per referencing table), never one query per skill.
    $stmt = $pdo->query(
        "SELECT s.id, s.name, s.description, s.status, s.category_id AS categoryId, s.updated_at,
                COALESCE(us.learners, 0) AS learners, COALESCE(us.mentors, 0) AS mentors,
                (COALESCE(us.total, 0) + COALESCE(ps.n, 0) + COALESCE(lg.n, 0) + COALESCE(ms.n, 0)
                 + COALESCE(sr.n, 0) + COALESCE(rel.n, 0)) AS refs
         FROM skills s
         LEFT JOIN (SELECT skill_id, SUM(skill_type = 'learning') AS learners, SUM(skill_type = 'teaching') AS mentors,
                           COUNT(*) AS total FROM user_skills GROUP BY skill_id) us ON us.skill_id = s.id
         LEFT JOIN (SELECT skill_id, COUNT(*) AS n FROM post_skills GROUP BY skill_id) ps ON ps.skill_id = s.id
         LEFT JOIN (SELECT skill_id, COUNT(*) AS n FROM learning_goals WHERE skill_id IS NOT NULL GROUP BY skill_id) lg ON lg.skill_id = s.id
         LEFT JOIN (SELECT skill_id, COUNT(*) AS n FROM mentoring_sessions GROUP BY skill_id) ms ON ms.skill_id = s.id
         LEFT JOIN (SELECT skill_id, COUNT(*) AS n FROM session_ratings WHERE skill_id IS NOT NULL GROUP BY skill_id) sr ON sr.skill_id = s.id
         LEFT JOIN (SELECT skill_id, COUNT(*) AS n FROM (SELECT source_skill_id AS skill_id FROM skill_relations
                                                        UNION ALL SELECT target_skill_id FROM skill_relations) r
                    GROUP BY skill_id) rel ON rel.skill_id = s.id
         ORDER BY s.name"
    );
    foreach ($stmt->fetchAll() as $row) {
        $row['learners'] = (int) $row['learners'];
        $row['mentors'] = (int) $row['mentors'];
        $row['refs'] = (int) $row['refs'];
        $row['updated'] = date('M j, Y', strtotime($row['updated_at']));
        $row['updated_sort'] = strtotime($row['updated_at']);
        $skills[$row['id']] = $row;
        if ($mostPopular === null || $row['learners'] > $mostPopular['learners']) {
            $mostPopular = $row;
        }
    }
} catch (Throwable $e) {
    error_log('[UKN admin/skills] ' . $e->getMessage());
    $skillsDbError = true;
    $categories = [];
    $skills = [];
}
require __DIR__ . '/includes/header.php';
?>
<?php if ($skillsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load skills.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
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
  ukn_stat_card($mostPopular !== null
      ? ['label' => 'Most Popular', 'value' => $mostPopular['name'], 'icon' => 'trending_up', 'helper' => number_format($mostPopular['learners']) . ' learners']
      : ['label' => 'Most Popular', 'value' => '—', 'icon' => 'trending_up']);
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
        <?php foreach ($skills as $id => $skill): $isActive = $skill['status'] === 'active'; $cat = $categories[$skill['categoryId']]; $id = (int) $id; ?>
          <tr
            data-skill-row
            data-skill-id="<?= $id ?>"
            data-skill-name="<?= htmlspecialchars(mb_strtolower($skill['name'], 'UTF-8')) ?>"
            data-skill-category-id="<?= (int) $skill['categoryId'] ?>"
            data-skill-category-name="<?= htmlspecialchars(mb_strtolower($cat['name'], 'UTF-8')) ?>"
            data-skill-description="<?= htmlspecialchars(mb_strtolower($skill['description'], 'UTF-8')) ?>"
            data-skill-status="<?= htmlspecialchars($skill['status']) ?>"
            data-skill-learners="<?= (int) $skill['learners'] ?>"
            data-skill-mentors="<?= (int) $skill['mentors'] ?>"
            data-skill-updated-sort="<?= (int) $skill['updated_sort'] ?>"
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
              <form action="../backend/admin/skills/status.php" method="post" id="skillStatus-<?= $id ?>" hidden>
                <?= csrfField() ?>
                <?= uknReturnToField() ?>
                <input type="hidden" name="skill_id" value="<?= $id ?>">
                <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
              </form>
              <?php if ($skill['refs'] === 0): ?>
                <form action="../backend/admin/skills/delete.php" method="post" id="skillDelete-<?= $id ?>" hidden>
                  <?= csrfField() ?>
                  <?= uknReturnToField() ?>
                  <input type="hidden" name="skill_id" value="<?= $id ?>">
                </form>
              <?php endif; ?>
              <div class="d-flex gap-1 justify-content-md-end">
                <a href="../index.php?page=skill-details&amp;id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">View in App</a>
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
                        data-delete-form="skillStatus-<?= $id ?>"
                        <?php if ($isActive): ?>
                        data-delete-title="Deactivate <?= htmlspecialchars($skill['name']) ?>?"
                        data-delete-message="<?= htmlspecialchars($skill['name'] . ' leaves the skill directory, search, the skill network and skill pickers. Its ' . number_format($skill['learners']) . ' learners and ' . number_format($skill['mentors']) . ' mentors keep it on their profiles; goals, posts and sessions are unchanged.') ?>"
                        data-delete-confirm-label="Deactivate Skill"
                        <?php else: ?>
                        data-delete-title="Activate <?= htmlspecialchars($skill['name']) ?>?"
                        data-delete-message="The skill becomes available in the directory, search, the skill network and skill pickers again."
                        data-delete-confirm-label="Activate Skill"
                        <?php endif; ?>
                      ><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                    </li>
                    <?php if ($skill['refs'] === 0): ?>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item ukn-text-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-delete-form="skillDelete-<?= $id ?>"
                        data-delete-title="Delete <?= htmlspecialchars($skill['name']) ?>?"
                        data-delete-message="Nothing uses this skill yet (no members, posts, goals, sessions, ratings or relations). It will be permanently deleted."
                        data-delete-confirm-label="Delete Skill"
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
  <div class="card-body"<?= $skills ? ' hidden' : '' ?> data-skill-empty>
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
<?php endif; ?>
<div class="modal fade" id="skillFormModal" tabindex="-1" aria-labelledby="skillFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="skillFormModalLabel" data-skill-form-title>Add Skill</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <form action="../backend/admin/skills/create.php" method="post" data-skill-form data-validated-form novalidate
            data-create-action="../backend/admin/skills/create.php" data-update-action="../backend/admin/skills/update.php">
        <?= csrfField() ?>
        <?= uknReturnToField() ?>
        <div class="modal-body">
          <input type="hidden" name="skill_id" value="" data-skill-form-id>
          <div class="ukn-form-group">
            <label for="skillNameInput" class="form-label">Skill Name</label>
            <input type="text" class="form-control" id="skillNameInput" name="name" maxlength="80" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="name" data-message-required="Skill name is required." hidden>
              <span class="ms" aria-hidden="true">error</span><span data-message-text>Skill name is required.</span>
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="skillCategoryInput" class="form-label">Category</label>
            <select class="form-select" id="skillCategoryInput" name="category_id">
              <?php foreach ($categories as $id => $cat): ?>
                <option value="<?= $id ?>"<?= $cat['status'] !== 'active' ? ' disabled' : '' ?>><?= htmlspecialchars($cat['name']) ?><?= $cat['status'] !== 'active' ? ' (Inactive)' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ukn-form-group">
            <label for="skillDescriptionInput" class="form-label">Description</label>
            <textarea class="form-control" id="skillDescriptionInput" name="description" rows="3" maxlength="255" data-validate="required"></textarea>
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