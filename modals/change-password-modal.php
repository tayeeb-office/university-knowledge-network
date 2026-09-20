<?php
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