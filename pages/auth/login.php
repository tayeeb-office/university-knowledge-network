<?php
require_once __DIR__ . '/../../components/success-state.php';
require_once __DIR__ . '/../../components/error-state.php';
$flashSuccess = uknTakeFlash('flash_success');
$loginError = uknTakeFlash('login_error');
$oldEmail = (string) uknTakeFlash('login_old_email', '');
$showResend = (bool) uknTakeFlash('login_unverified', false);
?>
<div class="ukn-container-narrow">
  <div class="card ukn-auth-card">
    <div class="card-body">
      <h1 class="mb-0">Welcome Back</h1>
      <p class="ukn-auth-card__sub">Sign in to continue to University Knowledge Network.</p>
      <?php if ($flashSuccess): ?>
        <div class="mb-3"><?php ukn_success_state(['message' => $flashSuccess]); ?></div>
      <?php endif; ?>
      <?php if ($loginError): ?>
        <div class="mb-3"><?php ukn_error_state(['title' => $loginError]); ?></div>
      <?php endif; ?>
      <form data-auth-form="login" action="backend/auth/login.php" method="post" novalidate>
        <?= csrfField() ?>
        <div class="ukn-form-group">
          <label for="loginEmail" class="form-label">University Email</label>
          <input
            type="email"
            class="form-control"
            id="loginEmail"
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
        <div class="ukn-form-group">
          <label for="loginPassword" class="form-label">Password</label>
          <div class="ukn-password-field">
            <input
              type="password"
              class="form-control"
              id="loginPassword"
              name="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              data-validate="required"
            >
            <button type="button" class="btn-icon" data-toggle-password aria-label="Show password" aria-pressed="false">
              <span class="ms" aria-hidden="true">visibility</span>
            </button>
          </div>
          <div class="ukn-field-message is-invalid" data-error-for="password" hidden>
            <span class="ms" aria-hidden="true">error</span>Please enter your password.
          </div>
        </div>
        <div class="ukn-auth-row">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="loginRememberMe" name="rememberMe">
            <label class="form-check-label" for="loginRememberMe">Remember Me</label>
          </div>
          <a href="index.php?page=forgot-password">Forgot Password?</a>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
      <p class="ukn-auth-card__footer">Don&rsquo;t have an account? <a href="index.php?page=register">Register</a></p>
      <details class="ukn-auth-card__footer"<?= $showResend ? ' open' : '' ?>>
        <summary>Resend verification email</summary>
        <form action="backend/auth/resend-verification.php" method="post" class="d-flex gap-2 mt-2" novalidate>
          <?= csrfField() ?>
          <label for="resendEmail" class="ukn-visually-hidden">University Email</label>
          <input type="email" class="form-control form-control-sm" id="resendEmail" name="email" placeholder="name@university.edu" autocomplete="email" value="<?= htmlspecialchars($oldEmail) ?>" required>
          <button type="submit" class="btn btn-outline-secondary btn-sm flex-shrink-0">Send link</button>
        </form>
      </details>
    </div>
  </div>
</div>