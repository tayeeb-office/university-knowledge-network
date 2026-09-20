<?php
$adminActiveNav = 'settings';
$adminPageTitle = 'Admin Settings';
$adminPageSub = 'Configure frontend administration preferences for the University Knowledge Network.';
$adminPageStyles = ['../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/settings.js'];
function ukn_admin_settings_switch(string $id, string $label, string $help, bool $checked): void
{
    ?>
    <div class="ukn-admin-row ukn-admin-row--wrap">
      <div class="flex-fill ukn-min-w-0">
        <div><?= htmlspecialchars($label) ?></div>
        <?php if ($help): ?><div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($help) ?></div><?php endif; ?>
      </div>
      <div class="form-check form-switch flex-shrink-0">
        <input class="form-check-input" type="checkbox" role="switch" id="<?= $id ?>" name="<?= $id ?>" data-admin-settings-field <?= $checked ? 'checked' : '' ?>>
        <label class="ukn-visually-hidden" for="<?= $id ?>"><?= htmlspecialchars($label) ?></label>
      </div>
    </div>
    <?php
}
function ukn_admin_settings_display_row(string $label, string $value, string $help = ''): void
{
    ?>
    <div class="ukn-admin-row ukn-admin-row--wrap">
      <div class="flex-fill ukn-min-w-0">
        <div><?= htmlspecialchars($label) ?></div>
        <?php if ($help): ?><div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($help) ?></div><?php endif; ?>
      </div>
      <span class="ukn-body-sm fw-bold flex-shrink-0"><?= htmlspecialchars($value) ?></span>
    </div>
    <?php
}
require __DIR__ . '/includes/header.php';
?>
<div class="nav nav-tabs mb-3" role="tablist">
  <button class="nav-link active" id="tabGeneral" data-bs-toggle="tab" data-bs-target="#paneGeneral" type="button" role="tab" aria-controls="paneGeneral" aria-selected="true">General</button>
  <button class="nav-link" id="tabPlatform" data-bs-toggle="tab" data-bs-target="#panePlatform" type="button" role="tab" aria-controls="panePlatform" aria-selected="false">Platform</button>
  <button class="nav-link" id="tabSessions" data-bs-toggle="tab" data-bs-target="#paneSessions" type="button" role="tab" aria-controls="paneSessions" aria-selected="false">Sessions &amp; Points</button>
  <button class="nav-link" id="tabCommunity" data-bs-toggle="tab" data-bs-target="#paneCommunity" type="button" role="tab" aria-controls="paneCommunity" aria-selected="false">Community &amp; Moderation</button>
  <button class="nav-link" id="tabAppearance" data-bs-toggle="tab" data-bs-target="#paneAppearance" type="button" role="tab" aria-controls="paneAppearance" aria-selected="false">Appearance</button>
  <button class="nav-link" id="tabProfile" data-bs-toggle="tab" data-bs-target="#paneProfile" type="button" role="tab" aria-controls="paneProfile" aria-selected="false">Admin Profile</button>
</div>
<form data-admin-settings-form novalidate>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="paneGeneral" role="tabpanel" aria-labelledby="tabGeneral">
      <div class="card mb-3">
        <div class="card-header"><h2 class="ukn-h4 mb-0">General</h2></div>
        <div class="card-body">
          <div class="ukn-form-group">
            <label class="form-label" for="platformName">Platform Name</label>
            <input type="text" class="form-control" id="platformName" name="platformName" value="University Knowledge Network" data-validate="required" data-admin-settings-field>
            <div class="ukn-field-message is-invalid" data-error-for="platformName" hidden><span class="ms" aria-hidden="true">error</span>Platform name is required.</div>
            <div class="ukn-body-sm ukn-text-muted mt-1">Shown in the Admin header and page titles across this demo.</div>
          </div>
          <div class="ukn-form-group">
            <label class="form-label" for="platformShortName">Short Name</label>
            <input type="text" class="form-control" id="platformShortName" name="platformShortName" value="UKN" data-admin-settings-field>
          </div>
          <div class="ukn-form-group mb-0">
            <label class="form-label" for="supportContact">Support Contact</label>
            <input type="email" class="form-control" id="supportContact" name="supportContact" value="support@university.edu" data-validate="required email" data-admin-settings-field>
            <div
              class="ukn-field-message is-invalid"
              data-error-for="supportContact"
              data-message-required="Support contact is required."
              data-message-email="Enter a valid email address."
              hidden
            ><span class="ms" aria-hidden="true">error</span><span data-message-text>Support contact is required.</span></div>
          </div>
        </div>
      </div>
      <div class="card mb-3">
        <div class="card-body">
          <?php ukn_admin_settings_display_row('Default Language', 'English', 'Interface language for this demo — not yet configurable.'); ?>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <?php ukn_admin_settings_switch('maintenanceMode', 'Maintenance Mode', 'Demo preference only — does not block access to the application.', false); ?>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="panePlatform" role="tabpanel" aria-labelledby="tabPlatform">
      <div class="card mb-3">
        <div class="card-header"><h2 class="ukn-h4 mb-0">Feature Availability</h2></div>
        <div class="card-body">
          <?php
            ukn_admin_settings_switch('allowRegistrations', 'Allow New Registrations', 'Preview only — does not disable the Register page.', true);
            ukn_admin_settings_switch('enableCommunityPosts', 'Enable Community Posts', 'Preview only — the public Community feed stays available.', true);
            ukn_admin_settings_switch('enableMentorRequests', 'Enable Mentor Requests', 'Preview only — existing Learner Request behavior is unaffected.', true);
            ukn_admin_settings_switch('enableSkillNetwork', 'Enable Skill Network', 'Preview only — the Skill Network page stays available.', true);
            ukn_admin_settings_switch('enableLeaderboard', 'Enable Leaderboard', 'Preview only — the Leaderboard page stays available.', true);
          ?>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h2 class="ukn-h4 mb-0">User Policy</h2></div>
        <div class="card-body">
          <div class="ukn-form-group">
            <label class="form-label" for="defaultUserRole">Default New User Role</label>
            <select class="form-select" id="defaultUserRole" name="defaultUserRole" data-admin-settings-field>
              <option value="learner" selected>Learner</option>
              <option value="mentor">Mentor</option>
            </select>
          </div>
          <div class="ukn-form-group mb-0">
            <label class="form-label" for="profileVisibilityDefault">Default Profile Visibility</label>
            <select class="form-select" id="profileVisibilityDefault" name="profileVisibilityDefault" data-admin-settings-field>
              <option value="community" selected>University Community</option>
              <option value="private">Private</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="paneSessions" role="tabpanel" aria-labelledby="tabSessions">
      <div class="card mb-3">
        <div class="card-header"><h2 class="ukn-h4 mb-0">Session Defaults</h2></div>
        <div class="card-body">
          <div class="ukn-form-group">
            <label class="form-label" for="defaultSessionDuration">Default Session Duration</label>
            <select class="form-select" id="defaultSessionDuration" name="defaultSessionDuration" data-admin-settings-field>
              <option value="30">30 minutes</option>
              <option value="45">45 minutes</option>
              <option value="60" selected>60 minutes</option>
              <option value="90">90 minutes</option>
            </select>
          </div>
          <div class="ukn-form-group">
            <label class="form-label" for="minSessionDuration">Minimum Session Duration</label>
            <select class="form-select" id="minSessionDuration" name="minSessionDuration" data-admin-settings-field>
              <option value="30" selected>30 minutes</option>
              <option value="45">45 minutes</option>
              <option value="60">60 minutes</option>
            </select>
          </div>
          <div class="ukn-form-group">
            <label class="form-label" for="maxSessionDuration">Maximum Session Duration</label>
            <select class="form-select" id="maxSessionDuration" name="maxSessionDuration" data-admin-settings-field>
              <option value="60">60 minutes</option>
              <option value="90" selected>90 minutes</option>
            </select>
            <div class="ukn-field-message is-invalid" data-error-for="maxSessionDuration" hidden><span class="ms" aria-hidden="true">error</span>Maximum duration must not be less than the minimum duration.</div>
          </div>
          <div class="ukn-form-group mb-0">
            <label class="form-label" for="sessionRequestExpiry">Session Request Expiry</label>
            <select class="form-select" id="sessionRequestExpiry" name="sessionRequestExpiry" data-admin-settings-field>
              <option value="24">24 hours</option>
              <option value="48" selected>48 hours</option>
              <option value="72">72 hours</option>
            </select>
            <div class="ukn-body-sm ukn-text-muted mt-1">Demo preference only — does not add timers or automatically expire existing mock requests.</div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h2 class="ukn-h4 mb-0">Point Rules</h2></div>
        <div class="card-body">
          <?php
            ukn_admin_settings_display_row('Completed Session (Learner)', '+25', 'Awarded on completion.');
            ukn_admin_settings_display_row('Completed Session (Mentor)', '+30', 'Awarded on completion.');
            ukn_admin_settings_display_row('Late Cancellation', '−10', 'Within 6 hours of the scheduled start.');
          ?>
          <p class="ukn-body-sm ukn-text-muted mt-2 mb-0">Reference only here — this page does not recalculate any learner/mentor points or the Leaderboard.</p>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="paneCommunity" role="tabpanel" aria-labelledby="tabCommunity">
      <div class="card mb-3">
        <div class="card-header"><h2 class="ukn-h4 mb-0">Community</h2></div>
        <div class="card-body">
          <?php
            ukn_admin_settings_switch('postsRequireModeration', 'Posts Require Moderation', 'Preview only — the public Create Post workflow is unaffected.', false);
            ukn_admin_settings_switch('allowComments', 'Allow Comments', 'Preview only — does not disable existing public comments.', true);
            ukn_admin_settings_switch('allowReplies', 'Allow Replies', 'Preview only — does not disable existing public replies.', true);
            ukn_admin_settings_switch('allowPostVoting', 'Allow Post Voting', 'Preview only — voting.js is unaffected.', true);
            ukn_admin_settings_switch('allowSavePost', 'Allow Save Post', 'Preview only — Save Post stays available.', true);
          ?>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h2 class="ukn-h4 mb-0">Moderation</h2></div>
        <div class="card-body">
          <?php ukn_admin_settings_switch('autoHideAfterReports', 'Auto-hide Content After Report Threshold', 'Demo preference only — content is never hidden automatically. Hiding still happens manually from Posts/Comments Moderation.', false); ?>
          <div class="ukn-form-group mt-3">
            <label class="form-label" for="reportThreshold">Report Threshold</label>
            <input type="number" class="form-control" id="reportThreshold" name="reportThreshold" value="3" min="1" max="20" step="1" data-admin-settings-field>
            <div class="ukn-field-message is-invalid" data-error-for="reportThreshold" hidden><span class="ms" aria-hidden="true">error</span>Enter a whole number between 1 and 20.</div>
            <div class="ukn-body-sm ukn-text-muted mt-1">Used only to demonstrate a moderation preference — reports are still reviewed manually on the Reports page.</div>
          </div>
          <?php
            ukn_admin_settings_switch('showReportedPriority', 'Show Reported Content Priority', 'Sorts reported content first as a display preference on Posts/Comments Moderation.', true);
            ukn_admin_settings_switch('requireAdminNote', 'Require Admin Note on Resolution', 'Demo preference only — Reports Resolve/Dismiss does not currently enforce this.', false);
          ?>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="paneAppearance" role="tabpanel" aria-labelledby="tabAppearance">
      <div class="card">
        <div class="card-header"><h2 class="ukn-h4 mb-0">Appearance</h2></div>
        <div class="card-body">
          <div class="ukn-admin-row ukn-admin-row--wrap">
            <div class="flex-fill ukn-min-w-0">
              <div>Theme</div>
              <div class="ukn-body-sm ukn-text-muted">Applies immediately across the entire application, including every other Admin page and the user-facing app.</div>
            </div>
            <div class="d-flex gap-3 flex-shrink-0" data-admin-theme-setting>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="adminAppearanceTheme" id="adminThemeLight" value="light">
                <label class="form-check-label" for="adminThemeLight">Light</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="adminAppearanceTheme" id="adminThemeDark" value="dark">
                <label class="form-check-label" for="adminThemeDark">Dark</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="paneProfile" role="tabpanel" aria-labelledby="tabProfile">
      <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
          <span class="ukn-avatar ukn-avatar-lg flex-shrink-0" aria-hidden="true">AU</span>
          <div class="flex-fill ukn-min-w-0">
            <div class="fw-bold">Admin User</div>
            <div class="ukn-body-sm ukn-text-muted">Administrator</div>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h2 class="ukn-h4 mb-0">Profile Details</h2></div>
        <div class="card-body">
          <div class="ukn-form-group">
            <label class="form-label" for="adminDisplayName">Display Name</label>
            <input type="text" class="form-control" id="adminDisplayName" name="adminDisplayName" value="Admin User" data-validate="required" data-admin-settings-field>
            <div class="ukn-field-message is-invalid" data-error-for="adminDisplayName" hidden><span class="ms" aria-hidden="true">error</span>Please enter a display name.</div>
          </div>
          <div class="ukn-form-group mb-0">
            <label class="form-label" for="adminEmail">Email</label>
            <input type="email" class="form-control" id="adminEmail" name="adminEmail" value="admin@university.edu" data-validate="required email" data-admin-settings-field>
            <div
              class="ukn-field-message is-invalid"
              data-error-for="adminEmail"
              data-message-required="Please enter an email address."
              data-message-email="Enter a valid email address."
              hidden
            ><span class="ms" aria-hidden="true">error</span><span data-message-text>Please enter an email address.</span></div>
          </div>
          <p class="ukn-body-sm ukn-text-muted mt-2 mb-0">Frontend demo identity only — no real account record is read or updated, and no password is ever stored.</p>
        </div>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap mt-3" data-admin-settings-save-bar>
    <button type="submit" class="btn btn-primary btn-sm" data-admin-settings-save disabled>Save Changes</button>
    <button type="button" class="btn btn-outline-secondary btn-sm" data-admin-settings-reset disabled>Reset Changes</button>
  </div>
</form>
<?php require __DIR__ . '/../modals/change-password-modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>