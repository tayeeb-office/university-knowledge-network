<?php
// Step 48: suspend form shared by admin/users.php and admin/user-details.php. Triggers carry
// data-suspend-user-id / data-suspend-user-name; assets/js/admin/users.js fills them in.
?>
<div class="modal fade" id="suspendUserModal" tabindex="-1" aria-labelledby="suspendUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="suspendUserModalLabel">Suspend <span data-suspend-user-name>user</span>?</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <form action="../backend/admin/users/suspend.php" method="post" data-validated-form novalidate>
        <?= csrfField() ?>
        <?= uknReturnToField() ?>
        <input type="hidden" name="user_id" value="" data-suspend-user-id-input>
        <div class="modal-body">
          <p class="ukn-body-sm">The member is signed out on their next request and cannot sign in while suspended. Their posts, comments, sessions, ratings and points are kept.</p>
          <div class="ukn-form-group mb-0">
            <label for="suspendReasonInput" class="form-label">Reason</label>
            <textarea class="form-control" id="suspendReasonInput" name="reason" rows="3" maxlength="255" data-validate="required"></textarea>
            <div class="ukn-field-message is-invalid" data-error-for="reason" hidden>
              <span class="ms" aria-hidden="true">error</span>A reason is required.
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm">Suspend User</button>
        </div>
      </form>
    </div>
  </div>
</div>
