<?php
// Report a post or comment (backend/reports/create.php). One modal per page; the trigger's
// data-report-target-type / data-report-target-id fill it (comments.js). The server re-checks
// the target, the reason and the reporter (the session user).
require_once __DIR__ . '/../backend/helpers/reports.php';
?>
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="backend/reports/create.php" method="post" data-validated-form data-report-form novalidate>
        <?= csrfField() ?>
        <?= uknReturnToField() ?>
        <input type="hidden" name="target_type" value="" data-report-target-type-input>
        <input type="hidden" name="target_id" value="" data-report-target-id-input>
        <div class="modal-header">
          <h2 class="modal-title ukn-h3" id="reportModalLabel">Report <span data-report-target-label>content</span></h2>
          <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
            <span class="ms" aria-hidden="true">close</span>
          </button>
        </div>
        <div class="modal-body">
          <p class="ukn-body-sm mb-3">Reports are reviewed by the moderators. The author is not told who reported it.</p>
          <div class="ukn-form-group">
            <label for="reportReason" class="form-label">Reason <span class="ukn-text-danger" aria-hidden="true">*</span></label>
            <select class="form-select" id="reportReason" name="reason" data-validate="required">
              <option value="" selected disabled>Choose a reason</option>
              <?php foreach (uknReportReasons() as $reasonValue => $reasonLabel): ?>
                <option value="<?= htmlspecialchars($reasonValue) ?>"><?= htmlspecialchars($reasonLabel) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="ukn-field-message is-invalid" data-error-for="reason" hidden>
              <span class="ms" aria-hidden="true">error</span>Choose a reason for your report.
            </div>
          </div>
          <div class="ukn-form-group mb-0">
            <label for="reportDescription" class="form-label">Details (optional)</label>
            <textarea class="form-control" id="reportDescription" name="description" maxlength="<?= UKN_REPORT_DESCRIPTION_MAX ?>" placeholder="Anything that helps the moderators understand the problem."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Send Report</button>
        </div>
      </form>
    </div>
  </div>
</div>
