<?php
$departments = [
    'Computer Science', 'Electrical Engineering', 'Business Administration',
    'English', 'Economics', 'Civil Engineering', 'Architecture',
];
?>
<div class="ukn-container-narrow">
  <div class="card ukn-auth-card">
    <div class="card-body">
      <h1 class="mb-0">Create Your Account</h1>
      <p class="ukn-auth-card__sub">Join the University Knowledge Network to learn, teach and connect.</p>
      <form data-mock-auth-form="register" data-success-message="Account registration form completed successfully. Demo mode only." novalidate>
        <div class="ukn-form-group">
          <label for="registerName" class="form-label">Full Name</label>
          <input
            type="text"
            class="form-control"
            id="registerName"
            name="fullName"
            placeholder="Nabila Rahman"
            autocomplete="name"
            data-validate="required"
          >
          <div class="ukn-field-message is-invalid" data-error-for="fullName" hidden>
            <span class="ms" aria-hidden="true">error</span>Please enter your full name.
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerEmail" class="form-label">University Email</label>
          <input
            type="email"
            class="form-control"
            id="registerEmail"
            name="email"
            placeholder="name@university.edu"
            autocomplete="email"
            data-validate="required email"
          >
          <div
            class="ukn-field-message is-invalid"
            data-error-for="email"
            data-message-required="Please enter your university email."
            data-message-email="Enter a valid email address."
            hidden
          >
            <span class="ms" aria-hidden="true">error</span><span data-message-text>Please enter your university email.</span>
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerUniversityId" class="form-label">University ID</label>
          <input
            type="text"
            class="form-control"
            id="registerUniversityId"
            name="universityId"
            placeholder="e.g. 0112310123"
            autocomplete="off"
            data-validate="required"
          >
          <div class="ukn-field-message is-invalid" data-error-for="universityId" hidden>
            <span class="ms" aria-hidden="true">error</span>Please enter your University ID.
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerDepartment" class="form-label">Department</label>
          <select class="form-select" id="registerDepartment" name="department" data-validate="required">
            <option value="" selected disabled>Select your department</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?= htmlspecialchars($department) ?>"><?= htmlspecialchars($department) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="ukn-field-message is-invalid" data-error-for="department" hidden>
            <span class="ms" aria-hidden="true">error</span>Please select your department.
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerPassword" class="form-label">Password</label>
          <div class="ukn-password-field">
            <input
              type="password"
              class="form-control"
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
          <div class="ukn-field-message is-invalid" data-error-for="password" hidden>
            <span class="ms" aria-hidden="true">error</span>Please create a password.
          </div>
        </div>
        <div class="ukn-form-group">
          <label for="registerConfirmPassword" class="form-label">Confirm Password</label>
          <div class="ukn-password-field">
            <input
              type="password"
              class="form-control"
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
            hidden
          >
            <span class="ms" aria-hidden="true">error</span><span data-message-text>Please confirm your password.</span>
          </div>
        </div>
        <div class="ukn-form-group" data-validate-group="required" data-group-name="terms">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="registerTerms" name="terms">
            <label class="form-check-label" for="registerTerms">I agree to the <a href="#">Terms</a> and <a href="#">Privacy Policy</a>.</label>
          </div>
          <div class="ukn-field-message is-invalid" data-error-for="terms" hidden>
            <span class="ms" aria-hidden="true">error</span>You must agree to the Terms and Privacy Policy to continue.
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Create Account</button>
      </form>
      <p class="ukn-auth-card__footer">Already have an account? <a href="index.php?page=login">Login</a></p>
      <p class="ukn-auth-card__footer" data-continue-to-login hidden>
        <span class="ms" aria-hidden="true">check_circle</span> Registered (demo only) — <a href="index.php?page=login">Continue to Login</a>
      </p>
    </div>
  </div>
</div>