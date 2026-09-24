<?php
if (!function_exists('ukn_mentor_card')) {
    function ukn_mentor_card(array $mentor, array $options = []): void
    {
        $mentor += [
            'initials' => '?', 'department' => '', 'primarySkill' => '', 'otherSkills' => [],
            'rating' => null, 'sessions' => null, 'points' => null, 'availability' => '',
            'profileHref' => '#', 'match' => null, 'matchLabel' => null, 'skillOptions' => null,
            'id' => null, 'score' => null, 'reasons' => [],
        ];
        $isRecommendation = ($options['variant'] ?? 'default') === 'recommendation';
        $requestSkillOptions = $mentor['skillOptions'] ?? array_values(array_filter(array_merge([$mentor['primarySkill']], $mentor['otherSkills'])));
        $recommendationAttrs = $isRecommendation && $mentor['id'] !== null
            ? ' data-recommendation-mentor="' . (int) $mentor['id'] . '"'
                . ($mentor['score'] !== null ? ' data-recommendation-score="' . number_format((float) $mentor['score'], 2, '.', '') . '"' : '')
            : '';
        ?>
        <div class="card ukn-card-interactive mb-3"<?= $recommendationAttrs ?>>
          <div class="card-body">
            <div class="d-flex gap-3 align-items-start">
              <span class="ukn-avatar ukn-avatar-lg flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($mentor['initials']) ?></span>
              <div class="flex-fill ukn-min-w-0">
                <div class="d-flex align-items-baseline gap-2 flex-wrap">
                  <h3 class="ukn-person-card__name ukn-truncate"><?= htmlspecialchars($mentor['name']) ?></h3>
                  <span class="ukn-role-chip">Mentor</span>
                </div>
                <div class="ukn-body-sm ukn-truncate">
                  <?= htmlspecialchars($mentor['department']) ?><?php if ($mentor['primarySkill']): ?> · teaches <?= htmlspecialchars($mentor['primarySkill']) ?><?php endif; ?>
                </div>
              </div>
              <?php if ($isRecommendation && $mentor['match'] !== null): ?>
                <div class="text-end flex-shrink-0">
                  <div class="ukn-person-card__match"><?= (int) $mentor['match'] ?>%</div>
                  <div class="ukn-eyebrow">match</div>
                </div>
              <?php endif; ?>
            </div>
            <?php if ($isRecommendation && !empty($mentor['matchLabel'])):
              $isBest = $mentor['matchLabel'] === 'Best Match';
            ?>
              <span class="ukn-status <?= $isBest ? 'ukn-status-accent' : 'ukn-status-neutral' ?> mt-3 d-inline-flex"><?= htmlspecialchars($mentor['matchLabel']) ?></span>
            <?php endif; ?>
            <?php if ($isRecommendation && $mentor['reasons']): ?>
              <ul class="ukn-body-sm mt-3 mb-0 ps-3" data-recommendation-reasons>
                <?php foreach ($mentor['reasons'] as $reason): ?>
                  <li><?= htmlspecialchars($reason) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php if ($mentor['otherSkills']): ?>
              <div class="d-flex flex-wrap gap-2 my-3">
                <?php foreach ($mentor['otherSkills'] as $skill): ?>
                  <span class="ukn-tag-neutral"><?= htmlspecialchars($skill) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="ukn-person-card__stats">
              <div>
                <span class="ukn-eyebrow">Rating</span>
                <div><span class="ukn-stars" aria-hidden="true">★</span> <?= $mentor['rating'] !== null ? htmlspecialchars((string) $mentor['rating']) : '—' ?></div>
              </div>
              <div>
                <span class="ukn-eyebrow">Sessions</span>
                <div><?= $mentor['sessions'] !== null ? (int) $mentor['sessions'] : '—' ?></div>
              </div>
              <div>
                <span class="ukn-eyebrow">Points</span>
                <div><?= $mentor['points'] !== null ? (int) $mentor['points'] : '—' ?></div>
              </div>
            </div>
            <?php if ($mentor['availability']): ?>
              <div class="ukn-body-sm d-flex align-items-center gap-2 mb-3">
                <span class="ms ukn-text-success" aria-hidden="true">event_available</span><?= htmlspecialchars($mentor['availability']) ?>
              </div>
            <?php endif; ?>
            <div class="d-flex gap-2">
              <button
                type="button"
                class="btn btn-primary btn-sm flex-fill"
                data-bs-toggle="modal"
                data-bs-target="#sessionRequestModal"
                data-request-mentor-id="<?= $mentor['id'] !== null ? (int) $mentor['id'] : '' ?>"
                data-request-name="<?= htmlspecialchars($mentor['name']) ?>"
                data-request-initials="<?= htmlspecialchars($mentor['initials']) ?>"
                data-request-department="<?= htmlspecialchars($mentor['department']) ?>"
                data-request-skill="<?= htmlspecialchars($mentor['primarySkill']) ?>"
                data-request-rating="<?= $mentor['rating'] !== null ? htmlspecialchars((string) $mentor['rating']) : '' ?>"
                data-request-skill-options="<?= htmlspecialchars(implode('|', $requestSkillOptions)) ?>"
              >Request Session</button>
              <a href="<?= htmlspecialchars($mentor['profileHref']) ?>" class="btn btn-outline-secondary btn-sm flex-fill">View Profile</a>
            </div>
          </div>
        </div>
        <?php
    }
}