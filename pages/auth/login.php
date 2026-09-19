<?php
/**
 * Log In — main content only. Routed via index.php?page=login (see
 * index.php's $routes map). index.php also overrides $currentUser to
 * the signed-out Visitor shape for this route, so the header shows
 * "Log in / Register" and the left sidebar shows the Visitor nav group
 * (with "Log in" as the only active item) — nothing in this file needs
 * to touch that.
 *
 * Frontend-only mock form: assets/js/core/validation.js checks the
 * required/email rules, assets/js/pages/auth.js drives Show/Hide
 * Password, the mock "success -> toast" flow and the Forgot Password
 * placeholder. There is no real authentication, session, or backend
 * request anywhere here (prompt 10 section 37).
 */
?>
<div class="ukn-container-narrow">
  <div class="card ukn-auth-card">
    <div class="card-body">
      <h1 class="mb-0">Welcome Back</h1>
      <p class="ukn-auth-card__sub">Sign in to continue to University Knowledge Network.</p>

      <form data-mock-auth-form="login" data-success-message="Login successful. Demo mode only." novalidate>
        <div class="ukn-form-group">
          <label for="loginEmail" class="form-label">University Email</label>
          <input
            type="email"
            class="form-control"
            id="loginEmail"
            name="email"
            placeholder="name@university.edu"
            autocomplete="email"
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
          <button type="button" class="btn btn-link p-0" data-forgot-password>Forgot Password?</button>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>

      <p class="ukn-auth-card__footer">Don&rsquo;t have an account? <a href="index.php?page=register">Register</a></p>
    </div>
  </div>
</div>
