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
     */
    function ukn_nav_groups_for_role(string $role): array
    {
        $item = static fn (string $icon, string $label, string $route, ?int $count = null): array
            => ['icon' => $icon, 'label' => $label, 'route' => $route, 'count' => $count];

        switch ($role) {
            case 'visitor':
                return [
                    ['title' => 'Browse', 'items' => [
                        $item('home', 'Home', 'home'),
                        $item('workspaces', 'Skills', 'skills'),
                        $item('person_search', 'Find Mentors', 'find-mentors'),
                        $item('leaderboard', 'Leaderboard', 'leaderboard'),
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
                        $item('inbox', 'Learner Requests', 'sessions', 5),
                        $item('event', 'Sessions', 'sessions'),
                        $item('school', 'Teaching Skills', 'teaching-skills'),
                        $item('schedule', 'Availability', 'availability'),
                        $item('star', 'Ratings', 'ratings'),
                        $item('military_tech', 'Mentor Points', 'points'),
                    ]],
                    ['title' => 'You', 'items' => [
                        $item('notifications', 'Notifications', 'notifications', 3),
                        $item('person', 'Profile', 'profile'),
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
                        $item('person', 'Profile', 'profile'),
                        $item('hub', 'Skill Network', 'skill-network'),
                        $item('settings', 'Settings', 'settings'),
                    ]],
                ];
        }
    }
}

if (!function_exists('ukn_route_href')) {
    /** Route slug -> page file, per docs/ui .../UKN-FRONTEND-STRUCTURE.md. */
    function ukn_route_href(string $route): string
    {
        $map = [
            'home'              => 'index.php',
            'skills'            => 'pages/skills/skills.php',
            'find-mentors'      => 'pages/mentors/find-mentors.php',
            'recommendations'   => 'pages/mentors/recommendations.php',
            'leaderboard'       => 'pages/leaderboard/leaderboard.php',
            'login'             => 'pages/auth/login.php',
            'register'          => 'pages/auth/register.php',
            'saved-posts'       => 'pages/community/saved-posts.php',
            'my-posts'          => 'pages/community/my-posts.php',
            'learner-dashboard' => 'pages/dashboard/learner-dashboard.php',
            'mentor-dashboard'  => 'pages/dashboard/mentor-dashboard.php',
            'learning-skills'   => 'pages/skills/learning-skills.php',
            'teaching-skills'   => 'pages/skills/teaching-skills.php',
            'learning-goals'    => 'pages/learning/learning-goals.php',
            'availability'      => 'pages/learning/availability.php',
            'sessions'          => 'pages/sessions/sessions.php',
            'points'            => 'pages/points/points.php',
            'ratings'           => 'pages/ratings/ratings.php',
            'notifications'     => 'pages/notifications/notifications.php',
            'profile'           => 'pages/profile/my-profile.php',
            'skill-network'     => 'pages/network/skill-network.php',
            'settings'          => 'pages/settings/settings.php',
        ];

        return $map[$route] ?? 'index.php';
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
$navGroups = ukn_nav_groups_for_role($navRole);
?>
<nav class="ukn-sidebar-left" aria-label="Primary">
  <?php foreach ($navGroups as $group): ?>
    <div class="ukn-nav-group">
      <span class="ukn-eyebrow ukn-nav-group__title"><?= htmlspecialchars($group['title']) ?></span>
      <?php foreach ($group['items'] as $item):
        $isActive = $item['route'] === $activeNav;
      ?>
        <a
          href="<?= htmlspecialchars(ukn_route_href($item['route'])) ?>"
          class="ukn-nav-link<?= $isActive ? ' is-active' : '' ?>"
          <?= $isActive ? 'aria-current="page"' : '' ?>
          data-bs-toggle="tooltip"
          data-bs-placement="right"
          title="<?= htmlspecialchars($item['label']) ?>"
        >
          <span class="ms" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
          <span class="ukn-nav-text"><?= htmlspecialchars($item['label']) ?></span>
          <?php if (!empty($item['count'])): ?>
            <span class="ukn-count-pill"><?= (int) $item['count'] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <p class="ukn-sidebar-left__footer">Peer learning at the University. Learn a skill, teach a skill, keep the points.</p>
</nav>
