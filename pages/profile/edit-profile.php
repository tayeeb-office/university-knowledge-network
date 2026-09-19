<?php
/**
 * Edit Profile — main center content only. Routed via
 * index.php?page=edit-profile (see index.php's $routes map). The header,
 * left sidebar and footer come from the shell — not from here. This page
 * is intentionally NOT in index.php's $sidebarContextByPage map, so it
 * renders full-width with no right sidebar (brand guide section 11: forms
 * don't need a contextual sidebar) — the second column here is the photo
 * panel instead.
 *
 * Frontend-only mock form: assets/js/pages/profile.js drives the bio
 * character count, the photo preview (temporary browser state only, never
 * localStorage, never uploaded anywhere) and the mock "Save Changes" ->
 * validate -> toast flow. The skill tag picker reuses the exact same
 * [data-skill-picker]/[data-skill-input]/[data-skill-tag] markup and
 * global listeners as modals/create-post-modal.php and edit-post-modal.php
 * (assets/js/core/modal.js) — no second tag-picker implementation. This
 * form deliberately does NOT use [data-mock-form]: that generic handler
 * (assets/js/core/modal.js) wipes every skill tag on "submit" so a modal
 * can reset for its next open, which would also erase this page's
 * pre-filled skills the moment Save Changes is clicked. No real
 * persistence happens either way.
 */
$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';

$departments = ['Computer Science', 'Electrical Engineering', 'Business Administration', 'English', 'Economics', 'Civil Engineering', 'Architecture'];
$years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$currentDepartment = 'Computer Science';
$currentYear = $isMentor ? '4th Year' : '3rd Year';
$currentBio = $isMentor
    ? 'Fourth-year CS student. I teach Python and the machine-learning coursework sequence, working through your actual assignment rather than generic examples.'
    : 'Third-year CS student. Learning Python and React, and slowly getting better at explaining what I just learned to other people.';

$skillsLabel = $isMentor ? 'Skills you are teaching' : 'Skills you are learning';
$currentSkills = $isMentor
    ? ['Python', 'Database Design', 'Data Analysis']
    : ['Python', 'MySQL', 'Data Analysis', 'Public Speaking'];
$skillOptions = ['Python', 'MySQL', 'React', 'UI/UX Design', 'Data Analysis', 'Public Speaking', 'Database Design', 'Machine Learning'];
?>
<div class="ukn-page-header">
  <div>
    <h1>Edit Profile</h1>
    <p class="ukn-page-header__sub">Update your profile information and preferences.</p>
  </div>
</div>

<div class="row g-3 align-items-start">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <form id="editProfileForm" data-profile-form data-success-message="Profile updated successfully. Demo mode only." novalidate>
          <div class="ukn-form-row">
            <div class="ukn-form-group">
              <label for="editProfileName" class="form-label">Full Name <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <input type="text" class="form-control" id="editProfileName" name="name" value="<?= htmlspecialchars($currentUser['name'] ?? 'Nabila Rahman') ?>" data-validate="required">
              <div class="ukn-field-message is-invalid" data-error-for="name" hidden>
                <span class="ms" aria-hidden="true">error</span>Please enter your full name.
              </div>
            </div>
            <div class="ukn-form-group">
              <label for="editProfileStudentId" class="form-label">Student ID</label>
              <input type="text" class="form-control" id="editProfileStudentId" value="2022-CSE-441" disabled>
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
