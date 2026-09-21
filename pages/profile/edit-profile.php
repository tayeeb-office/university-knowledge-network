<?php
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
// TODO(auth): replace with the real session user id; mirrors index.php's own hardcoded
// demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

$years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$departments = [];
$currentName = $currentUser['name'] ?? 'Member';
$currentStudentId = '';
$currentDepartment = '';
$currentYear = '';
$currentBio = '';
$skillsLabel = $isMentor ? 'Skills you are teaching' : 'Skills you are learning';
$currentSkills = [];
$skillOptions = [];
$editProfileDbError = false;

try {
    $pdo = getDatabaseConnection();

    $departments = $pdo->query(
        "SELECT name FROM departments WHERE status = 'active' ORDER BY name"
    )->fetchAll(PDO::FETCH_COLUMN);

    $userStmt = $pdo->prepare(
        "SELECT u.full_name, u.university_id, u.year_of_study, u.bio, d.name AS department
         FROM users u LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.id = ?"
    );
    $userStmt->execute([UKN_DEMO_USER_ID]);
    $user = $userStmt->fetch();

    if ($user !== false) {
        $currentName = $user['full_name'];
        $currentStudentId = $user['university_id'];
        $currentDepartment = (string) ($user['department'] ?? '');
        $currentYear = (string) ($user['year_of_study'] ?? '');
        $currentBio = (string) ($user['bio'] ?? '');

        $skillsStmt = $pdo->prepare(
            "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id = ? AND us.skill_type = ?
             ORDER BY s.name"
        );
        $skillsStmt->execute([UKN_DEMO_USER_ID, $isMentor ? 'teaching' : 'learning']);
        $currentSkills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $skillOptions = $pdo->query(
        "SELECT name FROM skills WHERE status = 'active' ORDER BY name"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    error_log('[UKN edit-profile] ' . $e->getMessage());
    $editProfileDbError = true;
}
?>
<div class="ukn-page-header">
  <div>
    <h1>Edit Profile</h1>
    <p class="ukn-page-header__sub">Update your profile information and preferences.</p>
  </div>
</div>
<?php if ($editProfileDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your profile.',
      'message' => 'Something went wrong while loading your profile. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="row g-3 align-items-start">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <form id="editProfileForm" data-profile-form data-success-message="Profile updated successfully. Demo mode only." novalidate>
          <div class="ukn-form-row">
            <div class="ukn-form-group">
              <label for="editProfileName" class="form-label">Full Name <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <input type="text" class="form-control" id="editProfileName" name="name" value="<?= htmlspecialchars($currentName) ?>" data-validate="required">
              <div class="ukn-field-message is-invalid" data-error-for="name" hidden>
                <span class="ms" aria-hidden="true">error</span>Please enter your full name.
              </div>
            </div>
            <div class="ukn-form-group">
              <label for="editProfileStudentId" class="form-label">Student ID</label>
              <input type="text" class="form-control" id="editProfileStudentId" value="<?= htmlspecialchars($currentStudentId) ?>" disabled>
              <div class="ukn-body-sm mt-1">Student ID cannot be changed.</div>
            </div>
          </div>
          <div class="ukn-form-row">
            <div class="ukn-form-group">
              <label for="editProfileDepartment" class="form-label">Department <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <select class="form-select" id="editProfileDepartment" name="department" data-validate="required">
                <?php foreach ($departments as $dept): ?>
                  <option value="<?= htmlspecialchars($dept) ?>" <?= $dept === $currentDepartment ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="ukn-field-message is-invalid" data-error-for="department" hidden>
                <span class="ms" aria-hidden="true">error</span>Choose a department.
              </div>
            </div>
            <div class="ukn-form-group">
              <label for="editProfileYear" class="form-label">Year of Study</label>
              <select class="form-select" id="editProfileYear" name="year">
                <?php foreach ($years as $year): ?>
                  <option value="<?= htmlspecialchars($year) ?>" <?= $year === $currentYear ? 'selected' : '' ?>><?= htmlspecialchars($year) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="editProfileBio" class="form-label">Bio</label>
            <textarea class="form-control" id="editProfileBio" name="bio" maxlength="300" data-bio-char-input><?= htmlspecialchars($currentBio) ?></textarea>
            <div class="ukn-body-sm mt-1"><span data-bio-char-count><?= strlen($currentBio) ?></span> / 300 characters</div>
          </div>
          <div class="ukn-form-group">
            <label for="editProfileSkillInput" class="form-label"><?= htmlspecialchars($skillsLabel) ?></label>
            <div class="ukn-tag-input" data-skill-picker>
              <?php foreach ($currentSkills as $skill): ?>
                <button type="button" class="ukn-tag-skill" data-skill-tag="<?= htmlspecialchars($skill) ?>" aria-label="Remove <?= htmlspecialchars($skill) ?>">
                  <?= htmlspecialchars($skill) ?> <span class="ms" aria-hidden="true">close</span>
                </button>
              <?php endforeach; ?>
              <input type="text" id="editProfileSkillInput" placeholder="Type a skill, press Enter" list="editProfileSkillOptions" data-skill-input>
            </div>
            <datalist id="editProfileSkillOptions">
              <?php foreach ($skillOptions as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <input type="hidden" name="skills" data-skill-value value="<?= htmlspecialchars(implode('|', $currentSkills)) ?>">
          </div>
          <?php if ($isMentor): ?>
            <div class="ukn-form-group mb-0">
              <label class="form-label">Availability</label>
              <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap p-3 ukn-bg-surface-2 ukn-rounded-md">
                <span class="ukn-body-sm">Your weekly teaching hours are managed separately.</span>
                <a href="<?= htmlspecialchars(ukn_route_href('availability')) ?>" class="btn btn-outline-secondary btn-sm flex-shrink-0">Manage Availability</a>
              </div>
            </div>
          <?php endif; ?>
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            <a href="<?= htmlspecialchars(ukn_route_href('my-profile')) ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center">
        <div class="ukn-eyebrow mb-3">Profile Photo</div>
        <span class="ukn-avatar ukn-avatar-photo" aria-hidden="true" data-photo-preview data-photo-initials="<?= htmlspecialchars($currentUser['initials'] ?? 'NR') ?>"><?= htmlspecialchars($currentUser['initials'] ?? 'NR') ?></span>
        <input type="file" accept="image/*" hidden data-photo-input>
        <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-3" data-photo-upload-trigger>Upload New Photo</button>
        <button type="button" class="btn btn-outline-danger btn-sm w-100 mt-2" data-photo-remove-trigger>Remove Photo</button>
        <div class="ukn-body-sm mt-3">JPG or PNG, at least 200&times;200px, under 2MB.</div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>