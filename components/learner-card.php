<?php
require_once __DIR__ . '/../backend/helpers/avatars.php';
require_once __DIR__ . '/follow-button.php';
if (!function_exists('ukn_learner_card')) {
    function ukn_learner_card(array $learner): void
    {
        $learner += [
            'initials' => '?', 'department' => '', 'skills' => [],
            'points' => null, 'sessions' => null, 'profileHref' => '#', 'following' => null,
        ];
        ?>
        <div class="card ukn-card-interactive mb-3">
          <div class="card-body">
            <div class="d-flex gap-3 align-items-start">
              <?= uknAvatarHtml($learner['avatar_path'] ?? null, (string) $learner['initials'], 'ukn-avatar ukn-avatar-lg flex-shrink-0') ?>
              <div class="flex-fill ukn-min-w-0">
                <div class="d-flex align-items-baseline gap-2 flex-wrap">
                  <h3 class="ukn-person-card__name ukn-truncate"><?= htmlspecialchars($learner['name']) ?></h3>
                  <span class="ukn-role-chip">Learner</span>
                </div>
                <div class="ukn-body-sm ukn-truncate"><?= htmlspecialchars($learner['department']) ?></div>
              </div>
            </div>
            <?php if ($learner['skills']): ?>
              <div class="d-flex flex-wrap gap-2 my-3">
                <?php foreach ($learner['skills'] as $skill): ?>
                  <span class="ukn-tag-neutral"><?= htmlspecialchars($skill) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="ukn-person-card__stats ukn-person-card__stats--2up">
              <div>
                <span class="ukn-eyebrow">Learning Points</span>
                <div><?= $learner['points'] !== null ? (int) $learner['points'] : '—' ?></div>
              </div>
              <div>
                <span class="ukn-eyebrow">Sessions</span>
                <div><?= $learner['sessions'] !== null ? (int) $learner['sessions'] : '—' ?></div>
              </div>
            </div>
            <div class="d-flex gap-2">
              <a href="<?= htmlspecialchars($learner['profileHref']) ?>" class="btn btn-outline-secondary btn-sm flex-fill">View Profile</a>
              <?php if ($learner['following'] !== null && !empty($learner['id'])): ?>
                <?php ukn_follow_form((int) $learner['id'], (bool) $learner['following'], 'btn btn-sm w-100 ' . ($learner['following'] ? 'btn-outline-secondary' : 'btn-primary'), 'd-flex flex-fill'); ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php
    }
}