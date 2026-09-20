<?php
if (!function_exists('ukn_skill_card')) {
    function ukn_skill_card(array $skill, array $options = []): void
    {
        $variant = $options['variant'] ?? 'directory';
        if ($variant === 'learning' || $variant === 'teaching') {
            $skill += [
                'href' => '#', 'level' => '', 'meta' => '', 'progress' => 0,
                'primaryAction' => 'View Skill', 'primaryActionHref' => null, 'removeLabel' => 'Remove',
            ];
            $pct = max(0, min(100, (int) $skill['progress']));
            $kindLabel = $variant === 'teaching' ? 'Teaching' : 'Learning';
            ?>
            <div class="card ukn-card-marked-left mb-3" data-skill-name="<?= htmlspecialchars($skill['name']) ?>">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                  <h3 class="ukn-h4 mb-0"><a href="<?= htmlspecialchars($skill['href']) ?>"><?= htmlspecialchars($skill['name']) ?></a></h3>
                  <?php if ($skill['level']): ?><span class="ukn-status ukn-status-accent"><?= htmlspecialchars($skill['level']) ?></span><?php endif; ?>
                </div>
                <?php if ($skill['meta']): ?><div class="ukn-body-sm mt-1"><?= htmlspecialchars($skill['meta']) ?></div><?php endif; ?>
                <div class="d-flex align-items-center gap-3 mt-3">
                  <div class="progress flex-fill">
                    <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= htmlspecialchars($skill['name']) ?> progress"></div>
                  </div>
                  <span class="ukn-body-sm flex-shrink-0"><?= $pct ?>%</span>
                </div>
                <div class="d-flex gap-2 flex-wrap mt-3">
                  <?php if ($skill['primaryActionHref']): ?>
                    <a href="<?= htmlspecialchars($skill['primaryActionHref']) ?>" class="btn btn-outline-secondary btn-sm"><?= htmlspecialchars($skill['primaryAction']) ?></a>
                  <?php else: ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm"><?= htmlspecialchars($skill['primaryAction']) ?></button>
                  <?php endif; ?>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteConfirmationModal"
                    data-delete-title="Remove <?= htmlspecialchars($skill['name']) ?> from <?= $kindLabel ?> Skills?"
                    data-delete-message="This removes it from your <?= strtolower($kindLabel) ?> skills list. You can add it again later."
                    data-delete-confirm-label="Remove"
                    data-success-message="<?= htmlspecialchars($skill['name']) ?> removed from <?= $kindLabel ?> Skills."
                    data-remove-skill-card
                  ><?= htmlspecialchars($skill['removeLabel']) ?></button>
                </div>
              </div>
            </div>
            <?php
            return;
        }
        $skill += [
            'category' => '', 'mentors' => null, 'learners' => null,
            'description' => null, 'href' => '#', 'learningState' => null, 'teachingState' => null,
        ];
        ?>
        <div class="ukn-skill-card ukn-card-interactive mb-3" data-skill-name="<?= htmlspecialchars($skill['name']) ?>">
          <h3 class="ukn-skill-card__name">
            <a href="<?= htmlspecialchars($skill['href']) ?>"><?= htmlspecialchars($skill['name']) ?></a>
          </h3>
          <?php if ($skill['category']): ?>
            <div class="ukn-eyebrow mb-2"><?= htmlspecialchars($skill['category']) ?></div>
          <?php endif; ?>
          <?php if ($skill['description']): ?>
            <p class="ukn-body-sm ukn-clamp-2 mb-2"><?= htmlspecialchars($skill['description']) ?></p>
          <?php endif; ?>
          <div class="ukn-skill-card__stats mb-3">
            <?php if ($skill['mentors'] !== null): ?><span><?= (int) $skill['mentors'] ?> Mentors</span><?php endif; ?>
            <?php if ($skill['learners'] !== null): ?><span><?= (int) $skill['learners'] ?> Learners</span><?php endif; ?>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a href="<?= htmlspecialchars($skill['href']) ?>" class="btn btn-outline-secondary btn-sm">Explore</a>
            <?php if ($skill['learningState'] !== null):
              $isAdded = $skill['learningState'] === 'added';
            ?>
              <button type="button" class="btn btn-sm <?= $isAdded ? 'btn-outline-secondary' : 'btn-outline-primary' ?>" data-skill-toggle="learning" data-state="<?= $isAdded ? 'added' : 'add' ?>">
                <span class="ms" aria-hidden="true"><?= $isAdded ? 'check' : 'add' ?></span>
                <span data-skill-toggle-label><?= $isAdded ? 'Learning' : 'Add to Learning' ?></span>
              </button>
            <?php endif; ?>
            <?php if ($skill['teachingState'] !== null):
              $isTeaching = $skill['teachingState'] === 'added';
            ?>
              <button type="button" class="btn btn-sm <?= $isTeaching ? 'btn-outline-secondary' : 'btn-outline-primary' ?>" data-skill-toggle="teaching" data-state="<?= $isTeaching ? 'added' : 'add' ?>">
                <span class="ms" aria-hidden="true"><?= $isTeaching ? 'check' : 'add' ?></span>
                <span data-skill-toggle-label><?= $isTeaching ? 'Teaching' : 'Add to Teaching' ?></span>
              </button>
            <?php endif; ?>
          </div>
        </div>
        <?php
    }
}
