<?php
/**
 * Right sidebar — contextual, reusable sidebar module system.
 *
 * A page selects what renders here with ONE variable, set before
 * including includes/header.php:
 *
 *   $rightSidebarContext = 'dashboard-learner';
 *
 * Recognized contexts: home | dashboard-learner | dashboard-mentor |
 * skills | mentors | recommendations | sessions | points | post |
 * leaderboard | profile. Defaults to 'home' when unset — this file is
 * never hard-wired to Home specifically.
 *
 * To turn the sidebar off entirely (Settings, Notifications, error pages,
 * full-width views), set $showRightSidebar = false before including
 * includes/header.php. That existing mechanism (includes/header.php /
 * includes/footer.php) is what keeps this file from being included at
 * all and collapses .ukn-shell to two columns with no leftover gap — it
 * is untouched here.
 *
 * A page can still bypass the context system entirely by setting
 * $sidebarModules itself (same array shape ukn_sidebar_modules_for_context()
 * returns below) — the context lookup only fills $sidebarModules in when
 * a page hasn't already set it, so nothing that already relies on that
 * escape hatch breaks.
 *
 * Every module renders through ukn_render_sidebar_module() as one of a
 * small set of reusable TYPES, so new contexts never need bespoke markup:
 *   ranked-list   — rank/avatar + primary/secondary text + trailing value
 *   stat-rows     — label / value rows (Points, Session counts, ...)
 *   tag-list      — wrapped chips (Related Skills, Matching Factors, ...)
 *   link-list     — clickable title + meta rows (Trending Discussions, ...)
 *   progress-list — label + percentage bar (Current Goals)
 *   mini-session  — compact date-badge session card
 *   author-card   — avatar + identity + Follow button (About Author)
 * All mock data below is frontend-only — no database, no real stats.
 */

if (!function_exists('ukn_sidebar_modules_for_context')) {
    function ukn_sidebar_modules_for_context(string $context, array $currentUser): array
    {
        switch ($context) {
            case 'dashboard-learner':
                return [
                    [
                        'type' => 'stat-rows',
                        'title' => 'Learning Summary',
                        'items' => [
                            ['label' => 'Skills in progress', 'value' => '4'],
                            ['label' => 'Sessions this month', 'value' => '6'],
                            ['label' => 'Hours learned', 'value' => '11.5'],
                        ],
                    ],
                    [
                        'type' => 'link-list',
                        'title' => 'Upcoming Sessions',
                        'action' => 'View all sessions',
                        'actionHref' => ukn_route_href('sessions'),
                        'items' => [
                            ['href' => ukn_route_href('sessions'), 'title' => 'Python with Rahim Ahmed', 'meta' => 'Wed 6:00pm'],
                            ['href' => ukn_route_href('sessions'), 'title' => 'React with Nabila Rahman', 'meta' => 'Sat 11:00am'],
                        ],
                    ],
                    [
                        'type' => 'progress-list',
                        'title' => 'Current Goals',
                        'action' => 'View all goals',
                        'actionHref' => ukn_route_href('learning-goals'),
                        'items' => [
                            ['label' => 'Learn Python for Data Analysis', 'pct' => 70],
                            ['label' => 'Build a React project', 'pct' => 35],
                        ],
                    ],
                    [
                        'type' => 'stat-rows',
                        'title' => 'Learning Points',
                        'action' => 'View points',
                        'actionHref' => ukn_route_href('points'),
                        'items' => [
                            ['label' => 'Learning points', 'value' => '412', 'accent' => true],
                            ['label' => 'This month', 'value' => '+64'],
                        ],
                    ],
                    [
                        'type' => 'ranked-list',
                        'title' => 'Recommended Mentor',
                        'action' => 'View recommendations',
                        'actionHref' => ukn_route_href('recommendations'),
                        'items' => [
                            ['a' => 'Rahim Ahmed', 'c' => 'Computer Science · teaches Python', 'b' => '96% match'],
                        ],
                    ],
                ];

            case 'dashboard-mentor':
                return [
                    [
                        'type' => 'stat-rows',
                        'title' => 'Mentor Overview',
                        'items' => [
                            ['label' => 'Mentor points', 'value' => '520', 'accent' => true],
                            ['label' => 'Average rating', 'value' => '4.8'],
                            ['label' => 'Pending requests', 'value' => '3'],
                            ['label' => 'Upcoming sessions', 'value' => '2'],
                        ],
                    ],
                    [
                        'type' => 'link-list',
                        'title' => 'Upcoming Sessions',
                        'action' => 'View all sessions',
                        'actionHref' => ukn_route_href('sessions'),
                        'items' => [
                            ['href' => ukn_route_href('sessions'), 'title' => 'Python with Sara Khan', 'meta' => 'Thu 5:00pm'],
                            ['href' => ukn_route_href('sessions'), 'title' => 'Machine Learning with Imran Chowdhury', 'meta' => 'Sat 1:00pm'],
                        ],
                    ],
                    [
                        'type' => 'ranked-list',
                        'title' => 'Recent Learners',
                        'items' => [
                            ['a' => 'Sara Khan', 'b' => '2 sessions'],
                            ['a' => 'Nabila Rahman', 'b' => '1 session'],
                        ],
                    ],
                ];

            case 'skills':
                return [
                    [
                        'type' => 'ranked-list',
                        'title' => 'Popular Skills',
                        'items' => [
                            ['rank' => '1', 'a' => 'Python', 'b' => '24 mentors'],
                            ['rank' => '2', 'a' => 'React', 'b' => '11 mentors'],
                            ['rank' => '3', 'a' => 'UI/UX Design', 'b' => '9 mentors'],
                            ['rank' => '4', 'a' => 'Data Analysis', 'b' => '8 mentors'],
                        ],
                    ],
                    [
                        'type' => 'ranked-list',
                        'title' => 'Trending Skills',
                        'items' => [
                            ['rank' => '1', 'a' => 'Machine Learning', 'b' => '+18%'],
                            ['rank' => '2', 'a' => 'UI/UX Design', 'b' => '+12%'],
                            ['rank' => '3', 'a' => 'Public Speaking', 'b' => '+9%'],
                        ],
                    ],
                    [
                        'type' => 'stat-rows',
                        'title' => 'Skill Categories',
                        'items' => [
                            ['label' => 'Programming', 'value' => '18'],
                            ['label' => 'Design', 'value' => '11'],
                            ['label' => 'Data', 'value' => '9'],
                            ['label' => 'Communication', 'value' => '7'],
                            ['label' => 'Business', 'value' => '5'],
                        ],
                    ],
                    [
                        'type' => 'ranked-list',
                        'title' => 'Most Requested Skills',
                        'items' => [
                            ['rank' => '1', 'a' => 'Machine Learning', 'b' => '88 requests'],
                            ['rank' => '2', 'a' => 'React', 'b' => '61 requests'],
                            ['rank' => '3', 'a' => 'Public Speaking', 'b' => '44 requests'],
                        ],
                    ],
                ];

            case 'mentors':
                // Find Mentors — mentor-related context only, no learner stats here.
                return [
                    [
                        'type' => 'ranked-list',
                        'title' => 'Top Rated Mentors',
                        'items' => [
                            ['rank' => '1', 'a' => 'Rahim Ahmed', 'c' => 'Python', 'b' => '★ 4.9'],
                            ['rank' => '2', 'a' => 'Sara Khan', 'c' => 'UI/UX Design', 'b' => '★ 4.8'],
                            ['rank' => '3', 'a' => 'Hasan Mahmud', 'c' => 'Data Analysis', 'b' => '★ 4.7'],
                        ],
                    ],
                    [
                        'type' => 'link-list',
                        'title' => 'Available Today',
                        'items' => [
                            ['href' => ukn_route_href('find-mentors'), 'title' => 'Rahim Ahmed', 'meta' => 'Wed 6:00pm'],
                            ['href' => ukn_route_href('find-mentors'), 'title' => 'Sara Khan', 'meta' => 'Fri 10:00am'],
                            ['href' => ukn_route_href('find-mentors'), 'title' => 'Hasan Mahmud', 'meta' => 'Sat 2:00pm'],
                        ],
                    ],
                    [
                        'type' => 'ranked-list',
                        'title' => 'Most Experienced',
                        'items' => [
                            ['rank' => '1', 'a' => 'Rahim Ahmed', 'b' => '64 sessions'],
                            ['rank' => '2', 'a' => 'Hasan Mahmud', 'b' => '52 sessions'],
                            ['rank' => '3', 'a' => 'Sara Khan', 'b' => '47 sessions'],
                        ],
                    ],
                    [
                        'type' => 'stat-rows',
                        'title' => 'Popular Mentor Skills',
                        'items' => [
                            ['label' => 'Python', 'value' => '24'],
                            ['label' => 'UI/UX Design', 'value' => '12'],
                            ['label' => 'Data Analysis', 'value' => '14'],
                        ],
                    ],
                ];

            case 'recommendations':
                return [
                    [
                        'type' => 'stat-rows',
                        'title' => 'Your Preferences',
                        'items' => [
                            ['label' => 'Learning skill', 'value' => 'Python', 'accent' => true],
                            ['label' => 'Preferred availability', 'value' => 'Sat & Sun'],
                            ['label' => 'Top recommended skill', 'value' => 'Machine Learning'],
                        ],
                    ],
                    [
                        'type' => 'tag-list',
                        'title' => 'Matching Factors',
                        'items' => ['Skill', 'Availability', 'Rating', 'Experience', 'Mentor Points'],
                    ],
                ];

            case 'sessions':
                return [
                    [
                        'type' => 'stat-rows',
                        'title' => 'Session Overview',
                        'items' => [
                            ['label' => 'Pending', 'value' => '2'],
                            ['label' => 'Upcoming', 'value' => '3'],
                            ['label' => 'Completed', 'value' => '12'],
                            ['label' => 'Cancelled', 'value' => '1'],
                        ],
                    ],
                    [
                        'type' => 'mini-session',
                        'title' => 'Next Upcoming Session',
                        'items' => [
                            ['day' => '17', 'month' => 'Sep', 'title' => 'Python with Rahim Ahmed', 'meta' => 'Wed 6:00pm · 60 min'],
                        ],
                    ],
                ];

            case 'points':
                return [
                    [
                        'type' => 'stat-rows',
                        'title' => 'Point Summary',
                        'items' => [
                            ['label' => 'Learning points', 'value' => '412'],
                            ['label' => 'Mentor points', 'value' => '520'],
                            ['label' => 'Total', 'value' => '932', 'accent' => true],
                        ],
                    ],
                    [
                        'type' => 'stat-rows',
                        'title' => 'Recent Transactions',
                        'action' => 'View full history',
                        'actionHref' => ukn_route_href('points'),
                        'items' => [
                            ['label' => 'Python session completed', 'value' => '+10', 'accent' => true],
                            ['label' => 'Post upvoted ×12', 'value' => '+12', 'accent' => true],
                            ['label' => 'Goal completed', 'value' => '+40', 'accent' => true],
                        ],
                    ],
                ];

            case 'post':
                return [
                    [
                        'type' => 'author-card',
                        'title' => 'About Author',
                        'items' => [
                            ['initials' => 'NR', 'name' => 'Nabila Rahman', 'department' => 'Computer Science', 'role' => 'Learner', 'points' => '412', 'followLabel' => 'Follow'],
                        ],
                    ],
                    [
                        'type' => 'tag-list',
                        'title' => 'Related Skills',
                        'items' => ['Database', 'MySQL', 'Normalization'],
                    ],
                    [
                        'type' => 'link-list',
                        'title' => 'Related Discussions',
                        'items' => [
                            ['href' => 'index.php?page=post-details', 'title' => 'State management without libraries', 'meta' => '19 comments'],
                            ['href' => 'index.php?page=post-details', 'title' => 'First deployment mistakes', 'meta' => '12 comments'],
                        ],
                    ],
                ];

            case 'leaderboard':
                // Role-aware — same mock role state as everywhere else
                // (see the 'profile' case below), never a second role
                // system. Values are kept consistent with the visible
                // mock leaderboard in pages/leaderboard/leaderboard.php
                // (Learner: Nabila Rahman ranks #1 with 412 Learning
                // Points; Mentor: she ranks #2 with 520 Mentor Points,
                // behind Rahim Ahmed).
                $activeRole = !empty($currentUser['dualRole'])
                    ? ($currentUser['activeRole'] ?? 'learner')
                    : ($currentUser['role'] ?? 'learner');

                if ($activeRole === 'mentor') {
                    return [
                        [
                            'type' => 'stat-rows',
                            'title' => 'Your Standing',
                            'action' => 'View Leaderboard',
                            'actionHref' => ukn_route_href('leaderboard'),
                            'items' => [
                                ['label' => 'Your mentor rank', 'value' => '#2', 'accent' => true],
                                ['label' => 'Your mentor points', 'value' => '520'],
                                ['label' => 'Rank movement', 'value' => 'Up 1 this week'],
                                ['label' => 'Current leader', 'value' => 'Rahim Ahmed'],
                            ],
                        ],
                    ];
                }

                return [
                    [
                        'type' => 'stat-rows',
                        'title' => 'Your Standing',
                        'action' => 'View Leaderboard',
                        'actionHref' => ukn_route_href('leaderboard'),
                        'items' => [
                            ['label' => 'Your learning rank', 'value' => '#1', 'accent' => true],
                            ['label' => 'Your learning points', 'value' => '412'],
                            ['label' => 'Rank movement', 'value' => 'Up 1 this week'],
                            ['label' => 'Current leader', 'value' => 'You (Nabila Rahman)'],
                        ],
                    ],
                ];

            case 'profile':
                /**
                 * Role-aware, and it must FOLLOW A ROLE SWITCH.
                 *
                 * This used to pick one branch server-side from
                 * $currentUser['activeRole'] — which index.php hardcodes to
                 * 'learner' — so the mentor branch below was unreachable at
                 * runtime and the sidebar stayed on "Learner Overview" even
                 * while the rest of the page was acting as Mentor.
                 *
                 * A dual-role user now gets BOTH sets rendered, each tagged
                 * with its role and the inactive one `hidden`, exactly like
                 * includes/left-sidebar.php's navigation and my-profile.php's
                 * points card. assets/js/core/role-switch.js already toggles
                 * every [data-role] element in the document, so switching
                 * role swaps this sidebar with no new JavaScript.
                 *
                 * A single-role user gets only their own set, with no
                 * [data-role] wrapper — so a Learner can never be shown
                 * Mentor context, and vice versa.
                 *
                 * The values below are unchanged from before: nothing new
                 * was invented, the two sets were simply both made reachable.
                 */
                $activeRole = !empty($currentUser['dualRole'])
                    ? ($currentUser['activeRole'] ?? 'learner')
                    : ($currentUser['role'] ?? 'learner');
                $isDualRoleUser = !empty($currentUser['loggedIn']) && !empty($currentUser['dualRole']);

                $modulesByRole = [
                    'mentor' => [
                        [
                            'type' => 'stat-rows',
                            'title' => 'Mentor Overview',
                            'items' => [
                                ['label' => 'Mentor points', 'value' => '520', 'accent' => true],
                                ['label' => 'Rating', 'value' => '4.8'],
                                ['label' => 'Completed sessions', 'value' => '64'],
                                ['label' => 'Availability', 'value' => '9 hrs / week'],
                            ],
                        ],
                    ],
                    'learner' => [
                        [
                            'type' => 'stat-rows',
                            'title' => 'Learner Overview',
                            'items' => [
                                ['label' => 'Learning points', 'value' => '412', 'accent' => true],
                                ['label' => 'Completed sessions', 'value' => '18'],
                            ],
                        ],
                        [
                            'type' => 'tag-list',
                            'title' => 'Main Skills',
                            'items' => ['Python', 'React', 'UI/UX Design'],
                        ],
                        [
                            'type' => 'progress-list',
                            'title' => 'Current Goals',
                            'items' => [
                                ['label' => 'Learn Python for Data Analysis', 'pct' => 70],
                            ],
                        ],
                    ],
                ];

                $rolesToRender = $isDualRoleUser
                    ? ['learner', 'mentor']
                    : [$activeRole === 'mentor' ? 'mentor' : 'learner'];

                $profileModules = [];
                foreach ($rolesToRender as $roleKey) {
                    foreach ($modulesByRole[$roleKey] as $module) {
                        if ($isDualRoleUser) {
                            $module['role'] = $roleKey;
                            $module['hidden'] = ($roleKey !== $activeRole);
                        }
                        $profileModules[] = $module;
                    }
                }

                return $profileModules;

            case 'home':
            default:
                return [
                    [
                        'type' => 'ranked-list',
                        'title' => 'Top Skills',
                        'action' => 'Browse All Skills',
                        'actionHref' => ukn_route_href('skills'),
                        'items' => [
                            ['rank' => '1', 'a' => 'Python', 'c' => 'Programming', 'b' => '186 learners'],
                            ['rank' => '2', 'a' => 'React', 'c' => 'Programming', 'b' => '124 learners'],
                            ['rank' => '3', 'a' => 'UI/UX Design', 'c' => 'Design', 'b' => '98 learners'],
                            ['rank' => '4', 'a' => 'Data Analysis', 'c' => 'Data', 'b' => '87 learners'],
                            ['rank' => '5', 'a' => 'Public Speaking', 'c' => 'Communication', 'b' => '61 learners'],
                        ],
                    ],
                    [
                        'type' => 'ranked-list',
                        'title' => 'Top Mentors',
                        'action' => 'Find a Mentor',
                        'actionHref' => ukn_route_href('find-mentors'),
                        'items' => [
                            ['rank' => '1', 'a' => 'Rahim Ahmed', 'c' => 'Computer Science', 'b' => '520 pts'],
                            ['rank' => '2', 'a' => 'Sara Khan', 'c' => 'Business Administration', 'b' => '410 pts'],
                            ['rank' => '3', 'a' => 'Hasan Mahmud', 'c' => 'Electrical Engineering', 'b' => '365 pts'],
                        ],
                    ],
                    [
                        'type' => 'ranked-list',
                        'title' => 'Top Learners',
                        'items' => [
                            ['rank' => '1', 'a' => 'Nabila Rahman', 'c' => 'Computer Science', 'b' => '412 pts'],
                            ['rank' => '2', 'a' => 'Sara Khan', 'c' => 'Business Administration', 'b' => '366 pts'],
                            ['rank' => '3', 'a' => 'Imran Chowdhury', 'c' => 'English', 'b' => '318 pts'],
                        ],
                    ],
                    [
                        'type' => 'link-list',
                        'title' => 'Trending Discussions',
                        'items' => [
                            ['href' => 'index.php?page=post-details', 'title' => 'Database normalization confusion', 'meta' => '24 comments'],
                            ['href' => 'index.php?page=post-details', 'title' => 'Best way to learn React', 'meta' => '41 comments'],
                            ['href' => 'index.php?page=post-details', 'title' => 'Python data analysis resources', 'meta' => '17 comments'],
                            ['href' => 'index.php?page=post-details', 'title' => 'UI/UX portfolio review', 'meta' => '12 comments'],
                        ],
                    ],
                ];
        }
    }
}

if (!function_exists('ukn_render_sidebar_module')) {
    /** Renders one module — dispatches on $module['type'], see file docblock. */
    function ukn_render_sidebar_module(array $module): void
    {
        $type = $module['type'] ?? 'ranked-list';

        /**
         * Optional role scoping. A module may carry:
         *   'role'   => 'learner' | 'mentor'   -> emits data-role="..."
         *   'hidden' => true                    -> starts hidden
         *
         * That is the SAME [data-role] convention includes/left-sidebar.php
         * uses for navigation and pages/profile/my-profile.php uses for its
         * points card, so assets/js/core/role-switch.js's existing
         * document-wide applyRole() toggles these modules too — no new
         * JavaScript, and no second role system.
         *
         * Modules without a 'role' key render exactly as before, so every
         * other sidebar context is unaffected.
         */
        $roleAttrs = '';
        if (!empty($module['role'])) {
            $roleAttrs = ' data-role="' . htmlspecialchars($module['role']) . '"'
                . (!empty($module['hidden']) ? ' hidden' : '');
        }
        ?>
        <div class="ukn-shell-module"<?= $roleAttrs ?>>
          <div class="ukn-shell-module__header">
            <span class="ukn-eyebrow"><?= htmlspecialchars($module['title']) ?></span>
          </div>

          <?php switch ($type):
            case 'stat-rows': ?>
              <?php foreach ($module['items'] as $row): ?>
                <div class="ukn-shell-module__item ukn-row-between">
                  <span class="ukn-body-sm"><?= htmlspecialchars($row['label']) ?></span>
                  <span class="fw-bold<?= !empty($row['accent']) ? ' ukn-text-accent' : '' ?>"><?= htmlspecialchars($row['value']) ?></span>
                </div>
              <?php endforeach; ?>
            <?php break;

            case 'tag-list': ?>
              <div class="ukn-shell-module__item">
                <div class="d-flex flex-wrap gap-2">
                  <?php foreach ($module['items'] as $tag): ?>
                    <span class="ukn-tag-neutral"><?= htmlspecialchars($tag) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php break;

            case 'link-list': ?>
              <?php foreach ($module['items'] as $link): ?>
                <a href="<?= htmlspecialchars($link['href']) ?>" class="ukn-shell-module__item">
                  <span class="ukn-shell-module__item-text">
                    <span class="d-block ukn-nav-text ukn-truncate"><?= htmlspecialchars($link['title']) ?></span>
                    <?php if (!empty($link['meta'])): ?>
                      <span class="d-block ukn-body-sm ukn-truncate"><?= htmlspecialchars($link['meta']) ?></span>
                    <?php endif; ?>
                  </span>
                </a>
              <?php endforeach; ?>
            <?php break;

            case 'progress-list': ?>
              <?php foreach ($module['items'] as $goal): $pct = (int) $goal['pct']; ?>
                <div class="ukn-shell-module__item d-block">
                  <div class="ukn-row-between ukn-body-sm mb-1">
                    <span><?= htmlspecialchars($goal['label']) ?></span>
                    <span><?= $pct ?>%</span>
                  </div>
                  <div class="progress">
                    <div
                      class="progress-bar"
                      role="progressbar"
                      style="width: <?= $pct ?>%"
                      aria-valuenow="<?= $pct ?>"
                      aria-valuemin="0"
                      aria-valuemax="100"
                      aria-label="<?= htmlspecialchars($goal['label']) ?> progress"
                    ></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php break;

            case 'mini-session': ?>
              <?php foreach ($module['items'] as $session): ?>
                <div class="ukn-shell-module__item">
                  <span class="ukn-shell-module__session-date">
                    <span class="d-block fw-bold"><?= htmlspecialchars($session['day']) ?></span>
                    <span class="d-block ukn-body-sm text-uppercase"><?= htmlspecialchars($session['month']) ?></span>
                  </span>
                  <span class="ukn-shell-module__item-text">
                    <span class="d-block ukn-nav-text"><?= htmlspecialchars($session['title']) ?></span>
                    <span class="d-block ukn-body-sm"><?= htmlspecialchars($session['meta']) ?></span>
                  </span>
                </div>
              <?php endforeach; ?>
            <?php break;

            case 'author-card': ?>
              <?php foreach ($module['items'] as $author): ?>
                <div class="ukn-shell-module__item d-block">
                  <div class="ukn-cluster mb-2">
                    <span class="ukn-avatar ukn-avatar-lg" aria-hidden="true"><?= htmlspecialchars($author['initials']) ?></span>
                    <span>
                      <span class="d-block fw-bold"><?= htmlspecialchars($author['name']) ?></span>
                      <span class="ukn-body-sm"><?= htmlspecialchars($author['department']) ?></span>
                    </span>
                  </div>
                  <div class="ukn-row-between mb-2">
                    <span class="ukn-role-chip"><?= htmlspecialchars($author['role']) ?></span>
                    <span class="ukn-body-sm"><?= htmlspecialchars($author['points']) ?> pts</span>
                  </div>
                  <button type="button" class="btn btn-outline-primary btn-sm w-100"><?= htmlspecialchars($author['followLabel']) ?></button>
                </div>
              <?php endforeach; ?>
            <?php break;

            case 'ranked-list':
            default: ?>
              <?php foreach ($module['items'] as $item): ?>
                <div class="ukn-shell-module__item">
                  <?php if (!empty($item['rank'])): ?>
                    <span class="ukn-shell-module__rank"><?= htmlspecialchars($item['rank']) ?></span>
                  <?php endif; ?>
                  <span class="ukn-shell-module__item-text">
                    <span class="d-block ukn-nav-text ukn-truncate"><?= htmlspecialchars($item['a']) ?></span>
                    <?php if (!empty($item['c'])): ?>
                      <span class="d-block ukn-body-sm ukn-truncate"><?= htmlspecialchars($item['c']) ?></span>
                    <?php endif; ?>
                  </span>
                  <span class="ukn-body-sm flex-shrink-0"><?= htmlspecialchars($item['b']) ?></span>
                </div>
              <?php endforeach; ?>
          <?php endswitch; ?>

          <?php if (!empty($module['action'])): ?>
            <a href="<?= htmlspecialchars($module['actionHref'] ?? '#') ?>" class="ukn-shell-module__action">
              <?= htmlspecialchars($module['action']) ?>
            </a>
          <?php endif; ?>
        </div>
        <?php
    }
}

$currentUser = $currentUser ?? [
    'loggedIn'   => true,
    'role'       => 'learner',
    'dualRole'   => true,
    'activeRole' => 'learner',
    'name'       => 'Nabila Rahman',
    'initials'   => 'NR',
    'meta'       => 'Learner · Computer Science',
];
$rightSidebarContext = $rightSidebarContext ?? 'home';
$sidebarModules = $sidebarModules ?? ukn_sidebar_modules_for_context($rightSidebarContext, $currentUser);
?>
<aside class="ukn-sidebar-right" aria-label="Related">
  <?php foreach ($sidebarModules as $module): ?>
    <?php ukn_render_sidebar_module($module); ?>
  <?php endforeach; ?>

  <p class="ukn-sidebar-right__note">
    UKN Community Guidelines &middot; Help &middot; Privacy<br>
    Student Union Building, Room 214
  </p>
</aside>
