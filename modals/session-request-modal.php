<?php
// Filled per trigger by modal.js (data-request-* attributes); a page may preset $requestMentor
// (the mentor profile does). The server re-validates the mentor and skill (backend/sessions/request.php).
require_once __DIR__ . '/../backend/helpers/avatars.php';
$requestMentor = ($requestMentor ?? []) + [
    'id' => null, 'name' => '', 'initials' => '', 'avatar_path' => null, 'department' => '', 'skill' => '', 'rating' => null, 'skillOptions' => [],
];
?>
<div class="modal fade" id="sessionRequestModal" tabindex="-1" aria-labelledby="sessionRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="backend/sessions/request.php" method="post" data-validated-form novalidate>
        <?= function_exists('csrfField') ? csrfField() : '' ?>
        <?= function_exists('uknReturnToField') ? uknReturnToField() : '' ?>
        <input type="hidden" name="mentor_id" value="<?= $requestMentor['id'] !== null ? (int) $requestMentor['id'] : '' ?>" data-request-mentor-id-input>
        <div class="modal-header">
          <h2 class="modal-title ukn-h3" id="sessionRequestModalLabel">Request a Session</h2>
          <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
            <span class="ms" aria-hidden="true">close</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="ukn-cluster mb-4 p-3 ukn-bg-surface-2 ukn-rounded-md">
            <?= uknAvatarHtml($requestMentor['avatar_path'], (string) $requestMentor['initials'], 'ukn-avatar ukn-avatar-lg', 'data-request-avatar') ?>
            <div>
              <div class="fw-bold" data-request-name-el><?= htmlspecialchars($requestMentor['name']) ?></div>
              <div class="ukn-body-sm" data-request-meta-el><?= $requestMentor['skill'] !== '' ? htmlspecialchars($requestMentor['skill']) . ' Mentor · ' : '' ?><?= htmlspecialchars($requestMentor['department']) ?></div>
              <div class="ukn-body-sm" data-request-rating-el><?= $requestMentor['rating'] !== null ? '★ ' . htmlspecialchars((string) $requestMentor['rating']) : '' ?></div>
            </div>
          </div>
          <div class="ukn-form-row">
            <div class="ukn-form-group">
              <label for="sessionRequestSkill" class="form-label">Skill <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <select class="form-select" id="sessionRequestSkill" name="skill" data-validate="required">
                <option value="" selected disabled>Choose a skill</option>
                <?php foreach ($requestMentor['skillOptions'] as $skill): ?>
                  <option value="<?= htmlspecialchars($skill) ?>"><?= htmlspecialchars($skill) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="ukn-field-message is-invalid" data-error-for="skill" hidden>
                <span class="ms" aria-hidden="true">error</span>Choose a skill.
              </div>
            </div>
            <div class="ukn-form-group">
              <label for="sessionRequestDuration" class="form-label">Duration</label>
              <select class="form-select" id="sessionRequestDuration" name="duration">
                <option value="60">60 minutes</option>
                <option value="45">45 minutes</option>
                <option value="30">30 minutes</option>
              </select>
            </div>
          </div>
          <div class="ukn-form-row">
            <div class="ukn-form-group">
              <label for="sessionRequestDate" class="form-label">Preferred Date <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <input type="date" class="form-control" id="sessionRequestDate" name="date" data-validate="required">
              <div class="ukn-field-message is-invalid" data-error-for="date" hidden>
                <span class="ms" aria-hidden="true">error</span>Choose a preferred date.
              </div>
            </div>
            <div class="ukn-form-group">
              <label for="sessionRequestTime" class="form-label">Preferred Time <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <input type="time" class="form-control" id="sessionRequestTime" name="time" data-validate="required">
              <div class="ukn-field-message is-invalid" data-error-for="time" hidden>
                <span class="ms" aria-hidden="true">error</span>Choose a preferred time.
              </div>
            </div>
          </div>
          <div class="ukn-form-group mb-0">
            <label for="sessionRequestMessage" class="form-label">Message (optional)</label>
            <textarea class="form-control" id="sessionRequestMessage" name="message" maxlength="1000" placeholder="I need help understanding Python data analysis basics."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Send Request</button>
        </div>
      </form>
    </div>
  </div>
</div>