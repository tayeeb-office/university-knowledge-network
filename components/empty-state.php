<?php
/**
 * Empty state — reusable "nothing here yet" placeholder.
 * Used by: any list once its (mock) data is empty — e.g. "No mentors
 * found.", "No saved posts yet.", "No upcoming sessions."
 *
 * Usage:
 *   require_once __DIR__ . '/../components/empty-state.php';
 *   ukn_empty_state([
 *     'icon'    => 'person_search',
 *     'title'   => 'No mentors found',
 *     'message' => 'Try removing the availability filter, or ask in the community feed.',
 *     'action'  => ['label' => 'Clear filters', 'href' => '#'],   // optional
 *     'dashed'  => true,                                          // optional, default false
 *   ]);
 *
 * $state['action'] may also carry 'attrs' — a raw extra-attributes string
 * (e.g. 'data-skills-clear-filters') for the handful of callers whose
 * action needs to be caught by a page-specific JS listener rather than
 * actually navigating anywhere (pages/skills/skills.php's "Clear
 * Filters"). Omit it and the action behaves exactly as before — a plain
 * link to 'href'.
 *
 * No Lorem Ipsum — every call site supplies its own real, specific copy.
 */
if (!function_exists('ukn_empty_state')) {
    function ukn_empty_state(array $state): void
    {
        $state += ['icon' => 'inbox', 'message' => '', 'action' => null, 'dashed' => false];
        $class = 'ukn-state' . ($state['dashed'] ? ' ukn-state--dashed' : '');
        ?>
        <div class="<?= $class ?>">
          <span class="ms" aria-hidden="true"><?= htmlspecialchars($state['icon']) ?></span>
          <div class="ukn-state__title"><?= htmlspecialchars($state['title']) ?></div>
          <?php if ($state['message']): ?>
            <p class="ukn-state__text"><?= htmlspecialchars($state['message']) ?></p>
          <?php endif; ?>
          <?php if ($state['action']): ?>
            <a href="<?= htmlspecialchars($state['action']['href'] ?? '#') ?>" class="btn btn-primary btn-sm"<?= !empty($state['action']['attrs']) ? ' ' . $state['action']['attrs'] : '' ?>><?= htmlspecialchars($state['action']['label']) ?></a>
          <?php endif; ?>
        </div>
        <?php
    }
}
