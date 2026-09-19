<?php
/**
 * Change Password modal — one shared modal, opened from
 * pages/settings/settings.php's Account section. Frontend-only: there is
 * no real current-password check, no hashing, no storage anywhere
 * (localStorage/sessionStorage/mock data). assets/js/pages/settings.js
 * validates required fields (via the same window.UKN.validateForm as
 * every other modal) plus a New/Confirm password match check — the exact
 * same pattern pages/auth/register.php already uses for its own Confirm
 * Password field, just re-implemented for this form's own field names
 * since that page's listener is scoped to [data-mock-auth-form] only.
 *
 * On mock success: a toast, an immediate form.reset() (clearing every
 * password field from the DOM right away) and the modal closes — nothing
 * is ever read back out of these fields after that.
 *
 * Show/Hide Password re-uses the existing generic [data-toggle-password]
 * behavior (assets/js/pages/auth.js) as-is — that listener already
 * applies to "any" such control, not just the auth pages.
 */
?>
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="changePasswordModalLabel">Change Password</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <form data-change-password-form novalidate>
        <div class="modal-body">
          <p class="ukn-body-sm ukn-text-muted">Frontend demo only — no account password is actually changed.</p>

          <div class="ukn-form-group">
            <label for="currentPasswordInput" class="form-label">Current Password</label>
            <div class="ukn-password-field">
              <input
                type="password"
                class="form-control"
                id="currentPasswordInput"
                name="currentPassword"
                autocomplete="current-password"
                data-validate="required"
              >
              <button type="button" class="btn-icon" data-toggle-password aria-label="Show password" aria-pressed="false">
                <span class="ms" aria-hidden="true">visibility</span>
              </button>
            </div>
            <div class="ukn-field-message is-invalid" data-error-for="currentPassword" hidden>
              <span class="ms" aria-hidden="true">error</span>Please enter your current password.
            </div>
          </div>

          <div class="ukn-form-group">
            <label for="newPasswordInput" class="form-label">New Password</label>
            <div class="ukn-password-field">
              <input
                type="password"
                class="form-control"
                id="newPasswordInput"
                name="newPassword"
                autocomplete="new-password"
                data-validate="required"
              >
              <button type="button" class="btn-icon" data-toggle-password aria-label="Show password" aria-pressed="false">
                <span class="ms" aria-hidden="true">visibility</span>
              </button>
            </div>
            <div class="ukn-field-message is-invalid" data-error-for="newPassword" hidden>
              <span class="ms" aria-hidden="true">error</span>Please create a new password.
            </div>
          </div>

          <div class="ukn-form-group">
            <label for="confirmNewPasswordInput" class="form-label">Confirm New Password</label>
            <div class="ukn-password-field">
              <input
                type="password"
                class="form-control"
                id="confirmNewPasswordInput"
                name="confirmNewPassword"
                autocomplete="new-password"
                data-validate="required"
                data-confirm-new-password-input
              >
              <button type="button" class="btn-icon" data-toggle-password aria-label="Show password" aria-pressed="false">
                <span class="ms" aria-hidden="true">visibility</span>
              </button>
            </div>
            <div
              class="ukn-field-message is-invalid"
              data-error-for="confirmNewPassword"
              data-message-required="Please confirm your new password."
              data-message-mismatch="New passwords do not match."
              hidden
            >
              <span class="ms" aria-hidden="true">error</span><span data-message-text>Please confirm your new password.</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
