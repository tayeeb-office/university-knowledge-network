<?php
/**
 * Role-switch confirmation modal — matches the approved design's flow:
 * two role-choice cards with a short description, and picking one performs
 * the switch immediately (no separate "Confirm" button beyond that choice).
 *
 * Opened from includes/profile-dropdown.php and includes/mobile-nav.php
 * via data-bs-target="#roleSwitchModal". Included once, from includes/footer.php,
 * so it only exists in the DOM a single time regardless of how many
 * triggers point at it.
 *
 * assets/js/core/role-switch.js listens for clicks on [data-role-choice]
 * inside this modal: it applies the chosen role (toggling the pre-rendered
 * [data-role] navigation blocks in includes/left-sidebar.php and
 * includes/mobile-nav.php, and the profile dropdown's role text/links),
 * persists it to localStorage, and closes this modal. Frontend-only mock
 * state — no backend authorization.
 */
?>
<div class="modal fade" id="roleSwitchModal" tabindex="-1" aria-labelledby="roleSwitchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3 mb-0" id="roleSwitchModalLabel">Switch role</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="ukn-body ukn-text-muted">Switching changes your navigation, dashboard and right sidebar. Your points and history stay intact in both roles.</p>
        <div class="ukn-role-choice-grid">
          <button type="button" class="ukn-role-choice" data-role-choice="learner">
            <span class="ukn-role-choice__title"><span class="ms" aria-hidden="true">school</span>Learner</span>
            <span class="ukn-role-choice__desc">Find mentors, set goals, book sessions.</span>
          </button>
          <button type="button" class="ukn-role-choice" data-role-choice="mentor">
            <span class="ukn-role-choice__title"><span class="ms" aria-hidden="true">record_voice_over</span>Mentor</span>
            <span class="ukn-role-choice__desc">Accept requests, manage availability, earn mentor points.</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
