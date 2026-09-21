<?php
if (!function_exists('ukn_nav_groups_for_role')) {
    /**
     * $badgeCounts (optional): real per-request counts computed once in index.php —
     * 'learnerRequests' (mentor's pending mentoring_sessions), 'notifications' (unread
     * notifications), 'sessions' (learner's upcoming accepted sessions). Missing/absent keys
     * render no badge at all, matching how !empty($item['count']) already hides a zero badge.
     */
    function ukn_nav_groups_for_role(string $role, array $badgeCounts = []): array
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
                        $item('inbox', 'Learner Requests', 'learner-requests', $badgeCounts['learnerRequests'] ?? null),
                        $item('event', 'Sessions', 'sessions'),
                        $item('school', 'Teaching Skills', 'teaching-skills'),
                        $item('schedule', 'Availability', 'availability'),
                        $item('star', 'Ratings', 'ratings'),
                        $item('military_tech', 'Mentor Points', 'points'),
                    ]],
                    ['title' => 'You', 'items' => [
                        $item('notifications', 'Notifications', 'notifications', $badgeCounts['notifications'] ?? null),
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
                        $item('event', 'Sessions', 'sessions', $badgeCounts['sessions'] ?? null),
                        $item('military_tech', 'Points', 'points'),
                    ]],
                    ['title' => 'You', 'items' => [
                        $item('notifications', 'Notifications', 'notifications', $badgeCounts['notifications'] ?? null),
                        $item('person', 'Profile', 'my-profile'),
                        $item('hub', 'Skill Network', 'skill-network'),
                        $item('settings', 'Settings', 'settings'),
                    ]],
                ];
        }
    }
}
if (!function_exists('ukn_route_href')) {
    function ukn_route_href(string $route): string
    {
        return $route === '' ? 'index.php' : 'index.php?page=' . rawurlencode($route);
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
$activeNav = $activeNav ?? 'home';
$navRole = $currentUser['loggedIn']
    ? ($currentUser['dualRole'] ? $currentUser['activeRole'] : $currentUser['role'])
    : 'visitor';
$isDualRoleUser = $currentUser['loggedIn'] && $currentUser['dualRole'];
$rolesToRender = $isDualRoleUser ? ['learner', 'mentor'] : [$navRole];
// Real counts computed once in index.php; falls back to no badge if included standalone.
$badgeCounts = [
    'learnerRequests' => $pendingRequestCount ?? 0,
    'notifications' => $notificationCount ?? 0,
    'sessions' => $upcomingSessionCount ?? 0,
];
?>
<nav class="ukn-sidebar-left" aria-label="Primary">
  <?php foreach ($rolesToRender as $roleKey): ?>
    <div<?= $isDualRoleUser ? ' data-role="' . htmlspecialchars($roleKey) . '"' . ($roleKey === $navRole ? '' : ' hidden') : '' ?>>
      <?php foreach (ukn_nav_groups_for_role($roleKey, $badgeCounts) as $group): ?>
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