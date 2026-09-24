<?php
require_once __DIR__ . '/../../components/success-state.php';
require_once __DIR__ . '/../../components/error-state.php';
// $verificationResult is set by index.php before any output: verified | expired | invalid | error.
$verificationResult = $verificationResult ?? 'invalid';
$verificationMessages = [
    'expired' => [
        'title' => 'This verification link has expired.',
        'message' => 'Request a new verification link below.',
    ],
    'invalid' => [
        'title' => 'This verification link is invalid or has already been used.',
        'message' => 'If your email is already verified you can log in. Otherwise, request a new link below.',
    ],
    'error' => [
        'title' => 'We could not verify your email right now.',
        'message' => 'Please try the link again in a few minutes.',
    ],
];
?>
<div class="ukn-container-narrow">
  <div class="card ukn-auth-card">
    <div class="card-body">
      <h1 class="mb-0">Verify Email</h1>
      <p class="ukn-auth-card__sub">Confirm your university email to activate your account.</p>
      <?php if ($verificationResult === 'verified'): ?>
        <?php ukn_success_state([
            'message' => 'Your email has been verified.',
            'detail' => 'You can now log in to University Knowledge Network.',
            'action' => ['label' => 'Log in', 'href' => 'index.php?page=login'],
        ]); ?>
      <?php else: ?>
        <?php ukn_error_state($verificationMessages[$verificationResult] ?? $verificationMessages['invalid']); ?>
        <?php if ($verificationResult !== 'error' && empty($currentUser['loggedIn'])): ?>
          <form action="backend/auth/resend-verification.php" method="post" class="d-flex gap-2 mt-3" novalidate>
            <?= csrfField() ?>
            <label for="verifyResendEmail" class="ukn-visually-hidden">University Email</label>
            <input type="email" class="form-control form-control-sm" id="verifyResendEmail" name="email" placeholder="name@university.edu" autocomplete="email" required>
            <button type="submit" class="btn btn-outline-secondary btn-sm flex-shrink-0">Send new link</button>
          </form>
        <?php endif; ?>
        <p class="ukn-auth-card__footer"><a href="index.php?page=login">Back to Login</a></p>
      <?php endif; ?>
    </div>
  </div>
</div>
