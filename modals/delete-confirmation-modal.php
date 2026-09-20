<?php
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