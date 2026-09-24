<?php
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
        <?php if (!empty($currentUser['loggedIn']) && !empty($currentUser['dualRole'])): ?>
        <form action="backend/auth/switch-role.php" method="post" class="m-0">
          <?= function_exists('csrfField') ? csrfField() : '' ?>
          <div class="ukn-role-choice-grid">
            <button type="submit" name="role" value="learner" class="ukn-role-choice" data-role-choice="learner"<?= ($currentUser['activeRole'] ?? '') === 'learner' ? ' aria-current="true"' : '' ?>>
              <span class="ukn-role-choice__title"><span class="ms" aria-hidden="true">school</span>Learner</span>
              <span class="ukn-role-choice__desc">Find mentors, set goals, book sessions.</span>
            </button>
            <button type="submit" name="role" value="mentor" class="ukn-role-choice" data-role-choice="mentor"<?= ($currentUser['activeRole'] ?? '') === 'mentor' ? ' aria-current="true"' : '' ?>>
              <span class="ukn-role-choice__title"><span class="ms" aria-hidden="true">record_voice_over</span>Mentor</span>
              <span class="ukn-role-choice__desc">Accept requests, manage availability, earn mentor points.</span>
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>