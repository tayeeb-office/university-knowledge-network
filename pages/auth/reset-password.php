<?php
require_once __DIR__ . '/../../components/error-state.php';
// $resetTokenValid / $resetToken are set by index.php before any output (read-only check; the
// token is consumed only when this form is posted to backend/auth/reset-password.php).
$resetTokenValid = $resetTokenValid ?? false;
$resetToken = $resetToken ?? '';
$resetError = uknTakeFlash('reset_error');
?>
<div class="ukn-container-narrow">
  <div class="card ukn-auth-card">
    <div class="card-body">
      <h1 class="mb-0">Reset Password</h1>
      <?php if (!$resetTokenValid): ?>
        <p class="ukn-auth-card__sub">Choose a new password for your account.</p>
        <?php ukn_error_state([
            'title' => 'This password reset link is invalid or has expired.',
            'message' => 'Reset links can be used once and expire after 60 minutes. You can request a new one.',
        ]); ?>
        <p class="ukn-auth-card__footer"><a href="index.php?page=forgot-password">Request a new reset link</a></p>
        <p class="ukn-auth-card__footer"><a href="index.php?page=login">Back to Login</a></p>
      <?php else: ?>
        <p class="ukn-auth-card__sub">Choose a new password for your account.</p>
        <?php if ($resetError): ?>
          <div class="mb-3"><?php ukn_error_state(['title' => $resetError]); ?></div>
        <?php endif; ?>
        <form data-auth-form="reset-password" action="backend/auth/reset-password.php" method="post" novalidate>
          <?= csrfField() ?>
          <input type="hidden" name="token" value="<?= htmlspecialchars($resetToken) ?>">
          <div class="ukn-form-group">
            <label for="resetPassword" class="form-label">New Password</label>
            <div class="ukn-password-field">
              <input
                type="password"
                class="form-control"
                id="resetPassword"
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
              <span class="ms" aria-hidden="true">error</span>Please enter a new password.
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="resetConfirmPassword" class="form-label">Confirm New Password</label>
            <div class="ukn-password-field">
              <input
                type="password"
                class="form-control"
                id="resetConfirmPassword"
                name="confirmPassword"
                placeholder="Re-enter your new password"
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
              data-message-required="Please confirm your new password."
              data-message-mismatch="Passwords do not match."
              hidden
            >
              <span class="ms" aria-hidden="true">error</span><span data-message-text>Please confirm your new password.</span>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Reset Password</button>
        </form>
        <p class="ukn-auth-card__footer"><a href="index.php?page=login">Back to Login</a></p>
      <?php endif; ?>
    </div>
  </div>
</div>
