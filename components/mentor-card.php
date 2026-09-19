<?php
/**
 * Mentor card — reusable mentor summary component.
 * Used by: Find Mentors, Recommendations (with the match variant),
 * Learner Dashboard, Skill Details, right-sidebar variants where a
 * fuller card (not the compact ranked-list row) is appropriate.
 *
 * Usage:
 *   require_once __DIR__ . '/../components/mentor-card.php';
 *   foreach ($mentors as $mentor) { ukn_mentor_card($mentor); }
 *   // Recommendation variant:
 *   ukn_mentor_card($mentor, ['variant' => 'recommendation']);
 *
 * $mentor shape:
 *   [
 *     'name' => 'Rahim Ahmed', 'initials' => 'RA',
 *     'department' => 'Computer Science',
 *     'primarySkill' => 'Python',
 *     'otherSkills' => ['Machine Learning', 'Data Structures'],
 *     'rating' => 4.9, 'sessions' => 127, 'points' => 520,
 *     'availability' => 'Available Wed, Sat evenings',
 *     'profileHref' => 'index.php?page=mentor-profile',
 *     'skillOptions' => null,             // optional — skills the learner can
 *                                          // pick when requesting THIS mentor
 *                                          // (see below). Omit it and it's
 *                                          // derived automatically from
 *                                          // primarySkill + otherSkills.
 *     // recommendation variant only:
 *     'match' => 98, 'matchLabel' => 'Best Match', // 'Best Match' | 'Good Match' | null
 *   ]
 * $options: ['variant' => 'default' | 'recommendation']
 *
 * "Request Session" has no backend — it's a visual mock action only. Its
 * button carries this mentor's data as data-request-* attributes;
 * assets/js/core/modal.js reads them from event.relatedTarget on
 * modals/session-request-modal.php's show.bs.modal to swap that ONE
 * shared modal's avatar/name/meta/rating/skill-options to whichever
 * mentor card was actually clicked — so every page using this component
 * (Find Mentors, Recommendations, Skill Details, the dashboards) gets a
 * correctly-contextual request modal for free, with no second modal and
 * no per-card modal copy. A page that already sets $requestMentor
 * server-side before includes/footer.php runs (pages/profile/mentor-profile.php)
 * is unaffected — that trigger simply carries none of these attributes,
 * so assets/js/core/modal.js's listener leaves its PHP-rendered content as-is.
 */
if (!function_exists('ukn_mentor_card')) {
    function ukn_mentor_card(array $mentor, array $options = []): void
    {
        $mentor += [
            'initials' => '?', 'department' => '', 'primarySkill' => '', 'otherSkills' => [],
            'rating' => null, 'sessions' => null, 'points' => null, 'availability' => '',
            'profileHref' => '#', 'match' => null, 'matchLabel' => null, 'skillOptions' => null,
        ];
        $isRecommendation = ($options['variant'] ?? 'default') === 'recommendation';
        $requestSkillOptions = $mentor['skillOptions'] ?? array_values(array_filter(array_merge([$mentor['primarySkill']], $mentor['otherSkills'])));
        ?>
        <div class="card ukn-card-interactive mb-3">
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
