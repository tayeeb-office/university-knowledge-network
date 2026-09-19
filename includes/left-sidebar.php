<?php
/**
 * Left sidebar — role-aware primary navigation (desktop/tablet).
 *
 * Also defines ukn_nav_groups_for_role() and ukn_route_href(), the two
 * helpers includes/mobile-nav.php reuses so the offcanvas menu never
 * drifts out of sync with this sidebar. Both files are always wired
 * together through includes/header.php / includes/footer.php within the
 * same request, so the functions are already defined by the time
 * mobile-nav.php needs them.
 *
 * A page controls what renders here by setting, before including
 * includes/header.php:
 *   $currentUser = ['loggedIn' => true, 'role' => 'learner', 'dualRole' => true,
 *                    'activeRole' => 'learner', 'name' => '...', 'initials' => '...', 'meta' => '...'];
 *   $activeNav = 'find-mentors'; // route slug to highlight, see ukn_route_href()
 *
 * $currentUser / $activeNav are mock frontend state — replace with real
 * session + routing data once authentication exists.
 */

if (!function_exists('ukn_nav_groups_for_role')) {
    /**
     * Role -> grouped nav items, mirroring the approved design's navFor().
     * 'visitor' | 'mentor' | 'learner' (dual-role users pass their
     * currently active role — see brand guide section 10).
     *
     * Admin is intentionally not a case here: admin/*.php uses its own,
     * more data-oriented navigation (assets/css/admin/admin-layout.css),
     * not this member-facing sidebar — keeping the two systems separate
     * is what "admin-ready" means for this component.
     */
    function ukn_nav_groups_for_role(string $role): array
    {
        $item = static fn (string $icon, string $label, string $route, ?int $count = null): array
            => ['icon' => $icon, 'label' => $label, 'route' => $route, 'count' => $count];

        switch ($role) {
            case 'visitor':
                // Public navigation only — no Dashboard, Sessions, Points,
                // Saved Posts, Notifications or Profile management.
                return [
                    ['title' => 'Browse', 'items' => [
                        $item('home', 'Home', 'home'),
                        $item('workspaces', 'Skills', 'skills'),
                        $item('person_search', 'Find Mentors', 'find-mentors'),
                    ]],
                    ['title' => 'Account', 'items' => [
                        $item('login', 'Log in', 'login'),
                        $item('app_registration', 'Register', 'register'),
                    ]],
                ];

            case 'mentor':
                return [
                    ['title' => 'Community', 'items' => [
                        $item('home', 'Home', 'home'),
                        $item('bookmark', 'Saved Posts', 'saved-posts'),
                        $item('article', 'My Posts', 'my-posts'),
                    ]],
                    ['title' => 'Mentoring', 'items' => [
                        $item('dashboard', 'Dashboard', 'mentor-dashboard'),
                        $item('inbox', 'Learner Requests', 'learner-requests', 5),
                        $item('event', 'Sessions', 'sessions'),
                        $item('school', 'Teaching Skills', 'teaching-skills'),
                        $item('schedule', 'Availability', 'availability'),
                        $item('star', 'Ratings', 'ratings'),
                        $item('military_tech', 'Mentor Points', 'points'),
                    ]],
                    ['title' => 'You', 'items' => [
                        $item('notifications', 'Notifications', 'notifications', 3),
                        $item('person', 'Profile', 'my-profile'),
                        $item('hub', 'Skill Network', 'skill-network'),
                        $item('settings', 'Settings', 'settings'),
                    ]],
                ];

            case 'learner':
            default:
                return [
                    ['title' => 'Community', 'items' => [
                        $item('home', 'Home', 'home'),
                        $item('bookmark', 'Saved Posts', 'saved-posts'),
                        $item('article', 'My Posts', 'my-posts'),
                        $item('leaderboard', 'Leaderboard', 'leaderboard'),
                    ]],
                    ['title' => 'Learning', 'items' => [
                        $item('dashboard', 'Dashboard', 'learner-dashboard'),
                        $item('person_search', 'Find Mentors', 'find-mentors'),
                        $item('auto_awesome', 'Recommendations', 'recommendations'),
                        $item('workspaces', 'Skills', 'skills'),
                        $item('menu_book', 'My Learning', 'learning-skills'),
                        $item('flag', 'Learning Goals', 'learning-goals'),
                        $item('event', 'Sessions', 'sessions', 2),
                        $item('military_tech', 'Points', 'points'),
                    ]],
                    ['title' => 'You', 'items' => [
                        $item('notifications', 'Notifications', 'notifications', 3),
                        $item('person', 'Profile', 'my-profile'),
                        $item('hub', 'Skill Network', 'skill-network'),
                        $item('settings', 'Settings', 'settings'),
                    ]],
                ];
        }
    }
}

if (!function_exists('ukn_route_href')) {
    /**
     * Route slug -> the URL a nav link should point to.
     *
     * Every internal page now routes through index.php's front controller
     * (index.php?page=<slug>) instead of linking pages/**\/*.php files
     * directly — that was the bug where sidebar links opened an empty
     * page with no header/sidebar/footer at all, since those page files
     * hold only page-specific content, not the shell. index.php owns the
     * actual slug -> file whitelist ($routes there) used to decide what
     * to include; this function only ever needs to know the slug.
     */
    function ukn_route_href(string $route): string
    {
        return $route === '' ? 'index.php' : 'index.php?page=' . rawurlencode($route);
    }
}

$currentUser = $currentUser ?? [
    'loggedIn'   => true,
    'role'       => 'learner',   // 'visitor' | 'learner' | 'mentor'
    'dualRole'   => true,
    'activeRole' => 'learner',   // which nav set a dual-role user currently sees
    'name'       => 'Nabila Rahman',
    'initials'   => 'NR',
    'meta'       => 'Learner · Computer Science',
];
$activeNav = $activeNav ?? 'home';

$navRole = $currentUser['loggedIn']
    ? ($currentUser['dualRole'] ? $currentUser['activeRole'] : $currentUser['role'])
    : 'visitor';

/**
 * Dual-role users get BOTH role's navigation rendered up front, each
 * wrapped in a [data-role] block — assets/js/core/role-switch.js just
 * toggles which one is `hidden`, so switching role never needs a reload
 * and never needs JS to know what a role's navigation contains (that
 * stays entirely owned by ukn_nav_groups_for_role() above). Everyone
 * else (single-role members, visitors) only ever gets the one relevant
 * set, with no [data-role] wrapper at all.
 */
$isDualRoleUser = $currentUser['loggedIn'] && $currentUser['dualRole'];
$rolesToRender = $isDualRoleUser ? ['learner', 'mentor'] : [$navRole];
?>
<nav class="ukn-sidebar-left" aria-label="Primary">
  <?php foreach ($rolesToRender as $roleKey): ?>
    <div<?= $isDualRoleUser ? ' data-role="' . htmlspecialchars($roleKey) . '"' . ($roleKey === $navRole ? '' : ' hidden') : '' ?>>
      <?php foreach (ukn_nav_groups_for_role($roleKey) as $group): ?>
        <div class="ukn-nav-group">
          <span class="ukn-eyebrow ukn-nav-group__title"><?= htmlspecialchars($group['title']) ?></span>
          <?php foreach ($group['items'] as $item):
            $isActive = $item['route'] === $activeNav;
          ?>
            <a
              href="<?= htmlspecialchars(ukn_route_href($item['route'])) ?>"
              class="ukn-nav-link<?= $isActive ? ' is-active' : '' ?>"
              <?= $isActive ? 'aria-current="page"' : '' ?>
              title="<?= htmlspecialchars($item['label']) ?>"
            >
              <span class="ms" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
              <span class="ukn-nav-text"><?= htmlspecialchars($item['label']) ?></span>
              <?php if (!empty($item['count'])): ?>
                <span class="ukn-count-pill"<?= $item['route'] === 'notifications' ? ' data-notification-badge' : '' ?>><?= (int) $item['count'] ?></span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <p class="ukn-sidebar-left__footer">Peer learning at the University. Learn a skill, teach a skill, keep the points.</p>
</nav>
