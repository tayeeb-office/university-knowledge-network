<?php
if (!function_exists('ukn_goal_card')) {
    function ukn_goal_card(array $goal): void
    {
        $goal += [
            'id' => null, 'skill' => '', 'progress' => 0, 'targetDate' => '', 'targetDateRaw' => '',
            'status' => 'in-progress', 'showActions' => true, 'showDelete' => false,
        ];
        $pct = max(0, min(100, (int) $goal['progress']));
        $isCompleted = $goal['status'] === 'completed';
        ?>
        <div
          class="card mb-3<?= $isCompleted ? '' : ' ukn-card-marked-left' ?>"
          data-goal-id="<?= htmlspecialchars((string) $goal['id']) ?>"
          data-goal-title="<?= htmlspecialchars($goal['title']) ?>"
          data-goal-skill="<?= htmlspecialchars($goal['skill']) ?>"
          data-goal-target-date="<?= htmlspecialchars($goal['targetDateRaw']) ?>"
          data-goal-progress="<?= $pct ?>"
          data-goal-status="<?= htmlspecialchars($goal['status']) ?>"
        >
          <div class="card-body">
            <div class="d-flex align-items-start gap-3">
              <div class="flex-fill ukn-min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <h3 class="ukn-h4 mb-0"><?= htmlspecialchars($goal['title']) ?></h3>
                  <span class="ukn-status <?= $isCompleted ? 'ukn-status-success' : 'ukn-status-accent' ?>"><?= $isCompleted ? 'Completed' : 'In Progress' ?></span>
                </div>
                <div class="ukn-body-sm mt-1" data-goal-meta>
                  <?php if ($goal['skill']): ?><?= htmlspecialchars($goal['skill']) ?> · <?php endif; ?><?= $isCompleted ? 'Completed' : 'target' ?> <?= htmlspecialchars($goal['targetDate']) ?>
                </div>
              </div>
              <?php if ($goal['showActions']): ?>
                <div class="d-flex gap-2 flex-shrink-0">
                  <button type="button" class="btn-icon btn-icon-sm" aria-label="Edit goal" data-goal-edit>
                    <span class="ms" aria-hidden="true">edit</span>
                  </button>
                  <?php if (!$isCompleted): ?>
                    <button type="button" class="btn-icon btn-icon-sm" aria-label="Mark goal complete" data-goal-complete>
                      <span class="ms" aria-hidden="true">check_circle</span>
                    </button>
                  <?php endif; ?>
                  <?php if ($goal['showDelete']): ?>
                    <button
                      type="button"
                      class="btn-icon btn-icon-sm"
                      aria-label="Delete goal"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteConfirmationModal"
                      data-delete-title="Delete Learning Goal?"
                      data-delete-message="This goal will be removed from your current list."
                      data-delete-confirm-label="Delete"
                      data-success-message="<?= htmlspecialchars($goal['title']) ?> deleted."
                      data-remove-goal-card
                    >
                      <span class="ms" aria-hidden="true">delete</span>
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3 mt-3">
              <div class="progress flex-fill">
                <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= htmlspecialchars($goal['title']) ?> progress"></div>
              </div>
              <span class="ukn-body-sm flex-shrink-0" data-goal-progress-label><?= $pct ?>%</span>
            </div>
          </div>
        </div>
        <?php
    }
}