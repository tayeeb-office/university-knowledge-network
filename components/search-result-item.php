<?php
/**
 * Search result item — one reusable row supporting multiple result types.
 * Used by: Search Results (pages/search/search-results.php) — the ONLY
 * result row type that page renders, for Posts/Skills/Mentors/Learners
 * alike, rather than mixing in the fuller post-card.php/mentor-card.php/
 * learner-card.php/skill-card.php cards (matching docs/ui's flat compact
 * result-row list for this screen).
 *
 * Usage:
 *   require_once __DIR__ . '/../components/search-result-item.php';
 *   foreach ($results as $result) { ukn_search_result_item($result); }
 *
 * $result shape:
 *   [
 *     'type'  => 'skill' | 'mentor' | 'learner' | 'post',
 *     'title' => 'Python',                        // or 'Rahim Ahmed', or a post title
 *     'meta'  => '124 mentors · 340 learners',     // type-appropriate secondary line
 *     'href'  => 'index.php?page=skill-details',
 *
 *     // optional, type-specific secondary action — omit whichever doesn't
 *     // apply and no action button renders for that row (title stays the
 *     // only, always-present click-through). Field names intentionally
 *     // match the same-purpose fields on the richer card components so
 *     // callers can lift values straight from that same mock data:
 *
 *     // type 'mentor' — renders "Request Session" via the ONE shared
 *     // modals/session-request-modal.php, exactly like mentor-card.php:
 *     'initials' => 'RA', 'department' => 'Computer Science', 'skill' => 'Python', 'rating' => 4.9,
 *
 *     // type 'skill' — renders "Add to Learning"/"Add to Teaching" via the
 *     // same [data-skill-toggle] behavior as skill-card.php's directory
 *     // variant (assets/js/pages/skills.js, already document-wide):
 *     'learningState' => null, 'teachingState' => null,   // null | 'add' | 'added'
 *
 *     // type 'learner' — renders "Follow"/"Following" via the same
 *     // [data-follow-toggle] behavior as learner-card.php
 *     // (assets/js/components/follow.js, already document-wide):
 *     'following' => null,                                 // null | true | false
 *   ]
 *
 * The type indicator is an icon + an uppercase text label together —
 * never the icon alone — so the result type is never conveyed by shape/
 * color alone. No backend for any of the above — same frontend-only mock
 * state rules as the components these actions were lifted from.
 */
if (!function_exists('ukn_search_result_item')) {
    function ukn_search_result_item(array $result): void
    {
        $result += [
            'meta' => '', 'href' => '#',
            'initials' => '', 'department' => '', 'skill' => '', 'rating' => null,
            'learningState' => null, 'teachingState' => null, 'following' => null,
        ];
        $typeMeta = [
            'skill'   => ['icon' => 'workspaces', 'label' => 'Skill'],
            'mentor'  => ['icon' => 'person_search', 'label' => 'Mentor'],
            'learner' => ['icon' => 'school', 'label' => 'Learner'],
            'post'    => ['icon' => 'forum', 'label' => 'Discussion'],
        ];
        $meta = $typeMeta[$result['type']] ?? ['icon' => 'search', 'label' => 'Result'];
        ?>
        <div class="ukn-search-result-item" data-search-result data-result-type="<?= htmlspecialchars($result['type']) ?>">
          <span class="ms ukn-search-result-item__icon" aria-hidden="true"><?= $meta['icon'] ?></span>
          <span class="ukn-search-result-item__text">
            <span class="d-flex align-items-center gap-2 flex-wrap">
              <a href="<?= htmlspecialchars($result['href']) ?>" class="ukn-nav-text ukn-truncate"><?= htmlspecialchars($result['title']) ?></a>
              <span class="ukn-role-chip flex-shrink-0"><?= $meta['label'] ?></span>
            </span>
            <?php if ($result['meta']): ?>
              <span class="ukn-body-sm d-block ukn-truncate"><?= htmlspecialchars($result['meta']) ?></span>
            <?php endif; ?>
          </span>

          <?php if ($result['type'] === 'mentor' && $result['department'] && $result['skill']): ?>
            <button
              type="button"
              class="btn btn-outline-primary btn-sm flex-shrink-0"
              data-bs-toggle="modal"
              data-bs-target="#sessionRequestModal"
              data-request-name="<?= htmlspecialchars($result['title']) ?>"
              data-request-initials="<?= htmlspecialchars($result['initials']) ?>"
              data-request-department="<?= htmlspecialchars($result['department']) ?>"
              data-request-skill="<?= htmlspecialchars($result['skill']) ?>"
              data-request-rating="<?= $result['rating'] !== null ? htmlspecialchars((string) $result['rating']) : '' ?>"
              data-request-skill-options="<?= htmlspecialchars($result['skill']) ?>"
            >Request Session</button>
          <?php elseif ($result['type'] === 'skill' && $result['learningState'] !== null):
            $isAdded = $result['learningState'] === 'added';
          ?>
            <button type="button" class="btn btn-sm flex-shrink-0 <?= $isAdded ? 'btn-outline-secondary' : 'btn-outline-primary' ?>" data-skill-toggle="learning" data-state="<?= $isAdded ? 'added' : 'add' ?>">
              <span class="ms" aria-hidden="true"><?= $isAdded ? 'check' : 'add' ?></span>
              <span data-skill-toggle-label><?= $isAdded ? 'Learning' : 'Add to Learning' ?></span>
            </button>
          <?php elseif ($result['type'] === 'skill' && $result['teachingState'] !== null):
            $isTeaching = $result['teachingState'] === 'added';
          ?>
            <button type="button" class="btn btn-sm flex-shrink-0 <?= $isTeaching ? 'btn-outline-secondary' : 'btn-outline-primary' ?>" data-skill-toggle="teaching" data-state="<?= $isTeaching ? 'added' : 'add' ?>">
              <span class="ms" aria-hidden="true"><?= $isTeaching ? 'check' : 'add' ?></span>
              <span data-skill-toggle-label><?= $isTeaching ? 'Teaching' : 'Add to Teaching' ?></span>
            </button>
          <?php elseif ($result['type'] === 'learner' && $result['following'] !== null): ?>
            <button
              type="button"
              class="btn btn-sm flex-shrink-0 <?= $result['following'] ? 'btn-outline-secondary' : 'btn-outline-primary' ?>"
              data-follow-toggle
              data-following="<?= $result['following'] ? 'true' : 'false' ?>"
              aria-pressed="<?= $result['following'] ? 'true' : 'false' ?>"
            ><span data-follow-label><?= $result['following'] ? 'Following' : 'Follow' ?></span></button>
          <?php endif; ?>
        </div>
        <?php
    }
}
