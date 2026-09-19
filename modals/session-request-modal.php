<?php
/**
 * Session Request modal — ONE shared instance opened from any mentor's
 * "Request Session" action (components/mentor-card.php, Find Mentors,
 * Recommendations, Skill Details, the dashboards, Mentor Profile — every
 * page reuses this exact modal, never a per-page or per-card copy).
 *
 * Two ways this modal's content ends up matching whichever mentor was
 * actually clicked:
 *   1. components/mentor-card.php's Request Session button carries the
 *      mentor as data-request-* attributes; assets/js/core/modal.js reads
 *      them from event.relatedTarget on show.bs.modal and swaps the
 *      elements below (data-request-avatar / -name-el / -meta-el /
 *      -rating-el / the #sessionRequestSkill options) — this is what
 *      Find Mentors and Recommendations rely on, since a listing page has
 *      many mentor cards sharing this one modal.
 *   2. pages/profile/mentor-profile.php instead sets $requestMentor
 *      server-side before includes/footer.php includes this file, for its
 *      one single mentor — its trigger button carries none of the
 *      data-request-* attributes above, so assets/js/core/modal.js's
 *      listener leaves this PHP-rendered content untouched.
 *
 * $requestMentor below is just this file's own hardcoded fallback for
 * whichever of the two paths above didn't run (e.g. the very first paint
 * before any modal has been opened yet). Frontend-only: assets/js/core/modal.js
 * drives the mock "Send Request" flow; no real booking is created.
 */
$requestMentor = $requestMentor ?? [
    'name' => 'Rahim Ahmed', 'initials' => 'RA', 'department' => 'Computer Science',
    'skill' => 'Python', 'rating' => 4.9,
    'skillOptions' => ['Python', 'Machine Learning'],
];
?>
<div class="modal fade" id="sessionRequestModal" tabindex="-1" aria-labelledby="sessionRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form data-mock-form="session-request" data-success-message="Session request sent successfully." novalidate>
        <div class="modal-header">
          <h2 class="modal-title ukn-h3" id="sessionRequestModalLabel">Request a Session</h2>
          <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
            <span class="ms" aria-hidden="true">close</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="ukn-cluster mb-4 p-3 ukn-bg-surface-2 ukn-rounded-md">
            <span class="ukn-avatar ukn-avatar-lg" aria-hidden="true" data-request-avatar><?= htmlspecialchars($requestMentor['initials']) ?></span>
            <div>
              <div class="fw-bold" data-request-name-el><?= htmlspecialchars($requestMentor['name']) ?></div>
              <div class="ukn-body-sm" data-request-meta-el><?= htmlspecialchars($requestMentor['skill']) ?> Mentor · <?= htmlspecialchars($requestMentor['department']) ?></div>
              <div class="ukn-body-sm" data-request-rating-el>★ <?= htmlspecialchars((string) $requestMentor['rating']) ?></div>
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
                <option>60 minutes</option>
                <option>45 minutes</option>
                <option>30 minutes</option>
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
            <textarea class="form-control" id="sessionRequestMessage" name="message" placeholder="I need help understanding Python data analysis basics."></textarea>
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
