<?php
require_once __DIR__ . '/../../components/success-state.php';
require_once __DIR__ . '/../../components/error-state.php';
// Posts to backend/auth/request-password-reset.php, which always answers with the same message.
$flashSuccess = uknTakeFlash('flash_success');
$forgotError = uknTakeFlash('forgot_error');
$oldEmail = (string) uknTakeFlash('forgot_old_email', '');
?>
<div class="ukn-container-narrow">
  <div class="card ukn-auth-card">
    <div class="card-body">
      <h1 class="mb-0">Forgot Password</h1>
      <p class="ukn-auth-card__sub">Enter your university email and we&rsquo;ll send you a link to choose a new password.</p>
      <?php if ($flashSuccess): ?>
        <div class="mb-3"><?php ukn_success_state([
            'message' => $flashSuccess,
            'detail' => 'Check your inbox and spam folder. The link expires in 60 minutes.',
        ]); ?></div>
      <?php endif; ?>
      <?php if ($forgotError): ?>
        <div class="mb-3"><?php ukn_error_state(['title' => $forgotError]); ?></div>
      <?php endif; ?>
      <form data-auth-form="forgot-password" action="backend/auth/request-password-reset.php" method="post" novalidate>
        <?= csrfField() ?>
        <div class="ukn-form-group">
          <label for="forgotEmail" class="form-label">University Email</label>
          <input
            type="email"
            class="form-control"
            id="forgotEmail"
            name="email"
            placeholder="name@university.edu"
            autocomplete="email"
            value="<?= htmlspecialchars($oldEmail) ?>"
            data-validate="required email"
          >
          <div
            class="ukn-field-message is-invalid"
            data-error-for="email"
            data-message-required="Please enter your email."
            data-message-email="Enter a valid email address."
            hidden
          >
            <span class="ms" aria-hidden="true">error</span><span data-message-text>Please enter your email.</span>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
      </form>
      <p class="ukn-auth-card__footer"><a href="index.php?page=login">Back to Login</a></p>
    </div>
  </div>
</div>
