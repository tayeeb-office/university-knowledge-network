<?php
require_once __DIR__ . '/../../components/error-state.php';
// Active departments from the database (Step 45: admins manage this list); the backend
// validates the choice against the same active rows.
$departments = [];
try {
    require_once __DIR__ . '/../../backend/config/database.php';
    $departments = getDatabaseConnection()
        ->query("SELECT name FROM departments WHERE status = 'active' ORDER BY id")
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    error_log('[UKN register] ' . $e->getMessage());
}
// Server-side validation results from backend/auth/register.php (one-time session values).
$registerErrors = (array) uknTakeFlash('register_errors', []);
$registerOld = (array) uknTakeFlash('register_old', []);
$errorFor = static fn (string $key): ?string => isset($registerErrors[$key]) ? (string) $registerErrors[$key] : null;
$hiddenUnless = static fn (string $key): string => $errorFor($key) === null ? ' hidden' : '';
$invalidIf = static fn (string $key): string => $errorFor($key) === null ? '' : ' is-invalid';
$oldValue = static fn (string $key): string => htmlspecialchars((string) ($registerOld[$key] ?? ''));
?>
<div class="ukn-container-narrow">
  <div class="card ukn-auth-card">
    <div class="card-body">
      <h1 class="mb-0">Create Your Account</h1>
      <p class="ukn-auth-card__sub">Join the University Knowledge Network to learn, teach and connect.</p>
      <?php if ($errorFor('form') !== null): ?>
        <div class="mb-3"><?php ukn_error_state(['title' => $errorFor('form')]); ?></div>
      <?php endif; ?>
      <form data-auth-form="register" action="backend/auth/register.php" method="post" novalidate>
        <?= csrfField() ?>
        <div class="ukn-form-group">
          <label for="registerName" class="form-label">Full Name</label>
          <input
            type="text"
            class="form-control<?= $invalidIf('full_name') ?>"
            id="registerName"
            name="fullName"
            placeholder="Nabila Rahman"
            autocomplete="name"
            value="<?= $oldValue('fullName') ?>"
            data-validate="required"
          >
          <div class="ukn-field-message is-invalid" data-error-for="fullName"<?= $hiddenUnless('full_name') ?>>
            <span class="ms" aria-hidden="true">error</span><?= htmlspecialchars($errorFor('full_name') ?? 'Please enter your full name.') ?>
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerEmail" class="form-label">University Email</label>
          <input
            type="email"
            class="form-control<?= $invalidIf('email') ?>"
            id="registerEmail"
            name="email"
            placeholder="name@university.edu"
            autocomplete="email"
            value="<?= $oldValue('email') ?>"
            data-validate="required email"
          >
          <div
            class="ukn-field-message is-invalid"
            data-error-for="email"
            data-message-required="Please enter your university email."
            data-message-email="Enter a valid email address."
            <?= $hiddenUnless('email') ?>
          >
            <span class="ms" aria-hidden="true">error</span><span data-message-text><?= htmlspecialchars($errorFor('email') ?? 'Please enter your university email.') ?></span>
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerUniversityId" class="form-label">University ID</label>
          <input
            type="text"
            class="form-control<?= $invalidIf('university_id') ?>"
            id="registerUniversityId"
            name="universityId"
            placeholder="e.g. 0112310123"
            autocomplete="off"
            value="<?= $oldValue('universityId') ?>"
            data-validate="required"
          >
          <div class="ukn-field-message is-invalid" data-error-for="universityId"<?= $hiddenUnless('university_id') ?>>
            <span class="ms" aria-hidden="true">error</span><?= htmlspecialchars($errorFor('university_id') ?? 'Please enter your University ID.') ?>
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerMobile" class="form-label">Mobile Number</label>
          <input
            type="tel"
            class="form-control<?= $invalidIf('mobile') ?>"
            id="registerMobile"
            name="mobileNumber"
            placeholder="01XXXXXXXXX"
            autocomplete="tel"
            inputmode="tel"
            maxlength="20"
            value="<?= $oldValue('mobileNumber') ?>"
            data-validate="required"
            aria-describedby="registerMobileHelp"
          >
          <div class="ukn-body-sm ukn-text-muted mt-1" id="registerMobileHelp">Your mobile number is private and will only be shared with the mentor you request a session with.</div>
          <div class="ukn-field-message is-invalid" data-error-for="mobileNumber"<?= $hiddenUnless('mobile') ?>>
            <span class="ms" aria-hidden="true">error</span><?= htmlspecialchars($errorFor('mobile') ?? 'Please enter your mobile number.') ?>
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerDepartment" class="form-label">Department</label>
          <select class="form-select<?= $invalidIf('department') ?>" id="registerDepartment" name="department" data-validate="required">
            <option value="" <?= ($registerOld['department'] ?? '') === '' ? 'selected ' : '' ?>disabled>Select your department</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?= htmlspecialchars($department) ?>"<?= ($registerOld['department'] ?? '') === $department ? ' selected' : '' ?>><?= htmlspecialchars($department) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="ukn-field-message is-invalid" data-error-for="department"<?= $hiddenUnless('department') ?>>
            <span class="ms" aria-hidden="true">error</span><?= htmlspecialchars($errorFor('department') ?? 'Please select your department.') ?>
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerPassword" class="form-label">Password</label>
          <div class="ukn-password-field">
            <input
              type="password"
              class="form-control<?= $invalidIf('password') ?>"
              id="registerPassword"
              name="password"
              placeholder="At least 8 characters"
              autocomplete="new-password"
              data-validate="required"
              data-password-strength-input
            >
            <button type="button" class="btn-icon" data-toggle-password aria-label="Show password" aria-pressed="false">
              <span class="ms" aria-hidden="true">visibility</span>
            </button>
          </div>
          <div class="ukn-strength" data-password-strength hidden aria-live="polite">
            <span class="ukn-strength__track"><span class="ukn-strength__bar"></span></span>
            <span class="ukn-strength__label" data-password-strength-label>Weak</span>
          </div>
          <div class="ukn-field-message is-invalid" data-error-for="password"<?= $hiddenUnless('password') ?>>
            <span class="ms" aria-hidden="true">error</span><?= htmlspecialchars($errorFor('password') ?? 'Please create a password.') ?>
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerConfirmPassword" class="form-label">Confirm Password</label>
          <div class="ukn-password-field">
            <input
              type="password"
              class="form-control<?= $invalidIf('confirm_password') ?>"
              id="registerConfirmPassword"
              name="confirmPassword"
              placeholder="Re-enter your password"
              autocomplete="new-password"
              data-validate="required"
              data-confirm-password-input
            >
            <button type="button" class="btn-icon" data-toggle-password aria-label="Show password" aria-pressed="false">
              <span class="ms" aria-hidden="true">visibility</span>
            </button>
          </div>
          <div
            class="ukn-field-message is-invalid"
            data-error-for="confirmPassword"
            data-message-required="Please confirm your password."
            data-message-mismatch="Passwords do not match."
            <?= $hiddenUnless('confirm_password') ?>
          >
            <span class="ms" aria-hidden="true">error</span><span data-message-text><?= htmlspecialchars($errorFor('confirm_password') ?? 'Please confirm your password.') ?></span>
          </div>
        </div>
        <div class="ukn-form-group" data-validate-group="required" data-group-name="terms">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="registerTerms" name="terms">
            <label class="form-check-label" for="registerTerms">I agree to the <a href="#">Terms</a> and <a href="#">Privacy Policy</a>.</label>
          </div>
          <div class="ukn-field-message is-invalid" data-error-for="terms"<?= $hiddenUnless('terms') ?>>
            <span class="ms" aria-hidden="true">error</span><?= htmlspecialchars($errorFor('terms') ?? 'You must agree to the Terms and Privacy Policy to continue.') ?>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Create Account</button>
      </form>
      <p class="ukn-auth-card__footer">Already have an account? <a href="index.php?page=login">Login</a></p>
    </div>
  </div>
</div>
