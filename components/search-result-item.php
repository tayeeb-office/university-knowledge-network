<?php
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