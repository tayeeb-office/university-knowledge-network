<?php
require_once __DIR__ . '/../../backend/helpers/mentor-applications.php';
require_once __DIR__ . '/../../components/error-state.php';
// Apply to become a mentor (logged-in users only; the route guard in index.php enforces that).
// Posts to backend/mentor-applications/apply.php, which re-checks everything server-side.
$applicationUser = getCurrentUser();
$latestApplication = null;
$applicationDbError = false;
try {
    $latestApplication = uknLatestMentorApplication(getDatabaseConnection(), (int) $applicationUser['id']);
} catch (Throwable $e) {
    error_log('[UKN mentor-application page] ' . $e->getMessage());
    $applicationDbError = true;
}
$applicationBlocker = uknMentorApplicationBlocker($applicationUser, $latestApplication);
$formatDate = static fn (?string $ts): string => $ts ? date('M j, Y', strtotime($ts)) : '';
?>
<div class="ukn-page-header">
  <div>
    <h1>Apply to Become a Mentor</h1>
    <p class="ukn-page-header__sub">Share what you know with other students. Approved accounts keep full Learner access and gain Mentor mode, which you can switch to from your profile menu.</p>
  </div>
</div>
<?php if ($applicationDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your application.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php elseif ($applicationBlocker === 'already-mentor'): ?>
  <div class="card">
    <div class="card-body">
      <span class="ukn-status ukn-status-success">Mentor access active</span>
      <p class="ukn-body mt-3 mb-0">Your account already has mentor access. Use <strong>Switch to Mentor</strong> in your profile menu to act as a mentor.</p>
    </div>
  </div>
<?php elseif ($applicationBlocker === 'pending'): ?>
  <div class="card">
    <div class="card-body">
      <span class="ukn-status ukn-status-accent">Mentor Application Pending</span>
      <p class="ukn-body mt-3">Submitted <?= htmlspecialchars($formatDate($latestApplication['requested_at'])) ?>. An administrator will review it; you can keep using UKN as a learner in the meantime.</p>
      <div class="ukn-eyebrow mb-1">Your message</div>
      <p class="ukn-body ukn-prose mb-0"><?= nl2br(htmlspecialchars((string) $latestApplication['application_message'])) ?></p>
    </div>
  </div>
<?php elseif ($applicationBlocker === 'ineligible'): ?>
  <?php ukn_error_state([
      'title' => 'Your account cannot apply to become a mentor.',
      'message' => 'Only active, verified learner accounts can apply.',
  ]); ?>
<?php else: ?>
  <div class="row g-3 align-items-start">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <?php if ($latestApplication !== null && $latestApplication['status'] === 'rejected'): ?>
            <p class="ukn-body-sm mb-3">Your previous application (<?= htmlspecialchars($formatDate($latestApplication['requested_at'])) ?>) was not approved. You are welcome to apply again.</p>
          <?php endif; ?>
          <form action="backend/mentor-applications/apply.php" method="post" data-validated-form novalidate>
            <?= csrfField() ?>
            <div class="ukn-form-group">
              <label for="mentorApplicationMessage" class="form-label">Why would you like to become a mentor? <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <textarea class="form-control ukn-textarea-lg" id="mentorApplicationMessage" name="message" maxlength="<?= UKN_MENTOR_APPLICATION_MESSAGE_MAX ?>" placeholder="The skills you could teach, your experience, and how you would like to help other students." data-validate="required" data-bio-char-input></textarea>
              <div class="ukn-body-sm mt-1"><span data-bio-char-count>0</span> / <?= UKN_MENTOR_APPLICATION_MESSAGE_MAX ?> characters</div>
              <div class="ukn-field-message is-invalid" data-error-for="message" hidden>
                <span class="ms" aria-hidden="true">error</span>Tell us why you would like to become a mentor.
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit Application</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <div class="ukn-eyebrow mb-2">What happens next</div>
          <ul class="ukn-body-sm mb-0 ps-3">
            <li>An administrator reviews your application.</li>
            <li>If approved, your account keeps Learner access and gains Mentor mode.</li>
            <li>You can have one application waiting for review at a time.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
