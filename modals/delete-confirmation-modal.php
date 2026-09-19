<?php
/**
 * Delete Confirmation modal — ONE reusable modal for every destructive
 * confirmation (Delete Post, Delete Comment, Remove Learning Goal,
 * Cancel a session, ...), rather than a separate modal per entity type.
 * Included once from includes/footer.php.
 *
 * Any trigger button opens it the normal Bootstrap way and customizes it
 * with data attributes — assets/js/core/modal.js reads these on
 * `show.bs.modal` and fills in the placeholders below:
 *
 *   <button type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
 *           data-delete-title="Delete Post?"
 *           data-delete-message="This action cannot be undone."
 *           data-delete-confirm-label="Delete"
 *           data-success-message="Post deleted.">
 *     Delete
 *   </button>
 *
 * All attributes except data-bs-target are optional — the defaults below
 * ("Delete item?" / "This action cannot be undone.") are shown if a
 * trigger doesn't set them. Frontend-only mock action — clicking the
 * confirm button just shows a toast and closes; nothing is deleted.
 */
?>
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="deleteConfirmationModalLabel" data-delete-title>Delete item?</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-3">
          <span class="ms ukn-delete-icon ukn-text-danger" aria-hidden="true">warning</span>
          <p class="ukn-body mb-0" data-delete-message>This action cannot be undone.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" data-delete-confirm data-success-message="Item deleted.">Delete</button>
      </div>
    </div>
  </div>
</div>
