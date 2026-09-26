<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/private-contacts.php';
// The owner's private mobile number (only ever read here for the signed-in user).
$ownMobile = null;
$ownMobileError = false;
try {
    $ownMobile = uknGetOwnMobile(getDatabaseConnection(), UKN_CURRENT_USER_ID);
} catch (Throwable $e) {
    error_log('[UKN settings] could not load the private contact (' . get_class($e) . ')');
    $ownMobileError = true;
}
$activeRole =!empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$otherRole = $isMentor ? 'Learner' : 'Mentor';
$profile = [
    'name' => $currentUser['name'] ?? '',
    'initials' => $currentUser['initials'] ?? '',
    'department' => $currentUser['department'] ?? '',
    'email' => $currentUser['email'] ?? '',
];
$notificationOptions = $isMentor
    ? [
        ['id' => 'notifLearnerRequests', 'label' => 'Learner requests', 'help' => 'A learner requests a session with you', 'checked' => true],
        ['id' => 'notifSessionReminders', 'label' => 'Session reminders', 'help' => 'Before an upcoming session starts', 'checked' => true],
        ['id' => 'notifRatingReceived', 'label' => 'Rating received', 'help' => 'A learner rates a completed session', 'checked' => true],
        ['id' => 'notifCommunityReplies', 'label' => 'Community replies', 'help' => 'Someone comments on your post', 'checked' => false],
    ]
    : [
        ['id' => 'notifSessionUpdates', 'label' => 'Session updates', 'help' => 'A mentor accepts, rejects or reschedules a session', 'checked' => true],
        ['id' => 'notifSessionReminders', 'label' => 'Session reminders', 'help' => 'Before an upcoming session starts', 'checked' => true],
        ['id' => 'notifCommunityReplies', 'label' => 'Community replies', 'help' => 'Someone comments on your post', 'checked' => true],
        ['id' => 'notifFollowActivity', 'label' => 'Follow activity', 'help' => 'Someone starts following you', 'checked' => false],
    ];
?>
<div class="ukn-page-header">
  <div>
    <h1>Settings</h1>
    <p class="ukn-page-header__sub">Manage your profile preferences and application experience.</p>
  </div>
</div>
<div class="card mb-3">
  <div class="card-body d-flex align-items-center gap-3 flex-wrap">
    <?= uknAvatarHtml($currentUser['avatarPath'] ?? null, (string) $profile['initials'], 'ukn-avatar ukn-avatar-lg flex-shrink-0') ?>
    <div class="flex-fill ukn-min-w-0">
      <div class="fw-bold"><?= htmlspecialchars($profile['name']) ?></div>
      <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($profile['department']) ?> &middot; <?= htmlspecialchars($profile['email']) ?></div>
      <span class="ukn-role-chip mt-1" data-role-label><?= htmlspecialchars(ucfirst($activeRole)) ?></span>
    </div>
    <a href="<?= htmlspecialchars(ukn_route_href('edit-profile')) ?>" class="btn btn-outline-secondary btn-sm flex-shrink-0">Edit Profile</a>
  </div>
</div>
<div class="ukn-settings-layout">
  <nav class="ukn-settings-nav" aria-label="Settings sections" data-settings-nav>
    <a href="#account" data-settings-nav-link class="is-active">Account</a>
    <a href="#appearance" data-settings-nav-link>Appearance</a>
    <a href="#notifications" data-settings-nav-link>Notifications</a>
    <a href="#privacy" data-settings-nav-link>Privacy &amp; Role</a>
  </nav>
  <div class="ukn-settings-content">
    <section class="card mb-3" id="account" data-settings-section aria-labelledby="accountHeading">
      <div class="card-header"><h2 id="accountHeading" class="ukn-h4 mb-0">Account</h2></div>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>University Email</div>
          <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($profile['email']) ?> &middot; verified</div>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" data-mock-email-change>Change Email</button>
      </div>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>Password</div>
          <div class="ukn-body-sm ukn-text-muted">••••••••</div>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
      </div>
      <div class="ukn-settings-row" id="mobile">
        <form action="backend/profile/mobile.php" method="post" class="w-100" data-validated-form novalidate>
          <?= csrfField() ?>
          <label for="settingsMobile" class="form-label mb-1">Mobile Number</label>
          <div class="ukn-body-sm ukn-text-muted mb-2">
            Private. Only the mentor of a session you request can see it, while that request or session is open.
            <?php if ($ownMobile === null && !$ownMobileError): ?>
              <strong>Add your number before requesting a mentoring session.</strong>
            <?php endif; ?>
          </div>
          <div class="d-flex gap-2 flex-wrap align-items-start">
            <input
              type="tel" class="form-control flex-fill w-auto" id="settingsMobile" name="mobile_number"
              placeholder="01XXXXXXXXX" autocomplete="tel" inputmode="tel" maxlength="20"
              value="<?= htmlspecialchars((string) $ownMobile) ?>" data-validate="required"
            >
            <button type="submit" class="btn btn-outline-secondary btn-sm flex-shrink-0"><?= $ownMobile === null ? 'Add Number' : 'Update Number' ?></button>
          </div>
          <div class="ukn-field-message is-invalid mt-1" data-error-for="mobile_number" hidden>
            <span class="ms" aria-hidden="true">error</span>Enter your mobile number.
          </div>
        </form>
      </div>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>Language</div>
          <div class="ukn-body-sm ukn-text-muted">Interface language</div>
        </div>
        <span class="ukn-body-sm flex-shrink-0">English</span>
      </div>
    </section>
    <section class="card mb-3" id="appearance" data-settings-section aria-labelledby="appearanceHeading">
      <div class="card-header"><h2 id="appearanceHeading" class="ukn-h4 mb-0">Appearance</h2></div>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>Theme</div>
          <div class="ukn-body-sm ukn-text-muted">Choose how University Knowledge Network looks on this device.</div>
        </div>
        <div class="d-flex gap-3 flex-shrink-0" data-theme-setting>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="appearanceTheme" id="appearanceThemeLight" value="light">
            <label class="form-check-label" for="appearanceThemeLight">Light</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="appearanceTheme" id="appearanceThemeDark" value="dark">
            <label class="form-check-label" for="appearanceThemeDark">Dark</label>
          </div>
        </div>
      </div>
    </section>
    <section class="card mb-3" id="notifications" data-settings-section aria-labelledby="notificationsHeading">
      <div class="card-header"><h2 id="notificationsHeading" class="ukn-h4 mb-0">Notifications</h2></div>
      <?php foreach ($notificationOptions as $option): ?>
        <div class="ukn-settings-row">
          <div class="ukn-settings-row__text">
            <div><?= htmlspecialchars($option['label']) ?></div>
            <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($option['help']) ?></div>
          </div>
          <div class="form-check form-switch flex-shrink-0">
            <input class="form-check-input" type="checkbox" role="switch" id="<?= $option['id'] ?>" data-settings-toggle <?= $option['checked'] ? 'checked' : '' ?>>
            <label class="ukn-visually-hidden" for="<?= $option['id'] ?>"><?= htmlspecialchars($option['label']) ?></label>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
    <section class="card mb-3" id="privacy" data-settings-section aria-labelledby="privacyHeading">
      <div class="card-header"><h2 id="privacyHeading" class="ukn-h4 mb-0">Privacy &amp; Role</h2></div>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>Show my profile to visitors</div>
          <div class="ukn-body-sm ukn-text-muted">Logged-out students can see your skills and activity</div>
        </div>
        <div class="form-check form-switch flex-shrink-0">
          <input class="form-check-input" type="checkbox" role="switch" id="privacyProfileVisible" data-settings-toggle>
          <label class="ukn-visually-hidden" for="privacyProfileVisible">Show my profile to visitors</label>
        </div>
      </div>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>Show Learning Skills</div>
          <div class="ukn-body-sm ukn-text-muted">Display your learning skills on your public profile</div>
        </div>
        <div class="form-check form-switch flex-shrink-0">
          <input class="form-check-input" type="checkbox" role="switch" id="privacyLearningSkills" data-settings-toggle checked>
          <label class="ukn-visually-hidden" for="privacyLearningSkills">Show Learning Skills</label>
        </div>
      </div>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>Show Teaching Skills</div>
          <div class="ukn-body-sm ukn-text-muted">Display your teaching skills on your public profile</div>
        </div>
        <div class="form-check form-switch flex-shrink-0">
          <input class="form-check-input" type="checkbox" role="switch" id="privacyTeachingSkills" data-settings-toggle checked>
          <label class="ukn-visually-hidden" for="privacyTeachingSkills">Show Teaching Skills</label>
        </div>
      </div>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>Show Activity</div>
          <div class="ukn-body-sm ukn-text-muted">Show recent sessions and posts on your public profile</div>
        </div>
        <div class="form-check form-switch flex-shrink-0">
          <input class="form-check-input" type="checkbox" role="switch" id="privacyShowActivity" data-settings-toggle checked>
          <label class="ukn-visually-hidden" for="privacyShowActivity">Show Activity</label>
        </div>
      </div>
      <?php if ($isMentor): ?>
        <div class="ukn-settings-row">
          <div class="ukn-settings-row__text">
            <div>Accept new session requests</div>
            <div class="ukn-body-sm ukn-text-muted">Mentors only</div>
          </div>
          <div class="form-check form-switch flex-shrink-0">
            <input class="form-check-input" type="checkbox" role="switch" id="privacyAcceptRequests" data-settings-toggle checked>
            <label class="ukn-visually-hidden" for="privacyAcceptRequests">Accept new session requests</label>
          </div>
        </div>
      <?php endif; ?>
      <div class="ukn-settings-row">
        <div class="ukn-settings-row__text">
          <div>Active Role</div>
          <div class="ukn-body-sm ukn-text-muted">Which role the application opens in — <span data-role-label><?= htmlspecialchars(ucfirst($activeRole)) ?></span></div>
        </div>
        <?php if (!empty($currentUser['dualRole'])): ?>
          <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#roleSwitchModal">
            <span data-role-switch-label>Switch to <?= htmlspecialchars($otherRole) ?></span>
          </button>
        <?php endif; ?>
      </div>
    </section>
    <div class="d-flex gap-2 flex-wrap mb-3" data-settings-save-bar>
      <button type="button" class="btn btn-primary btn-sm" data-settings-save disabled>Save Changes</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-settings-reset disabled>Reset Preferences</button>
    </div>
    <div class="card ukn-danger-zone">
      <div class="card-body">
        <div class="ukn-danger-zone__title">Danger Zone</div>
        <p class="ukn-body-sm mb-3">Deactivating hides your profile and cancels upcoming sessions. Points are kept for one academic year.</p>
        <button
          type="button"
          class="btn btn-outline-danger btn-sm"
          data-bs-toggle="modal"
          data-bs-target="#deleteConfirmationModal"
          data-delete-title="Deactivate Account?"
          data-delete-message="This action is unavailable in frontend demo mode. Deactivating hides your profile and cancels upcoming sessions; points are kept for one academic year."
          data-delete-confirm-label="Deactivate Account"
          data-success-message="Account deactivation is unavailable in frontend demo mode. No account data was changed."
        >Deactivate Account</button>
      </div>
    </div>
  </div>
</div>