<?php
/**
 * Right sidebar — contextual, not permanently tied to one content set.
 *
 * A page controls what renders here by setting $sidebarModules before
 * including includes/header.php:
 *
 *   $sidebarModules = [
 *     [
 *       'title'      => 'Upcoming Sessions',
 *       'action'     => 'View all sessions',   // optional footer link label
 *       'actionHref' => 'pages/sessions/sessions.php',
 *       'items'      => [
 *         ['rank' => '1', 'a' => 'Python with Rahim Ahmed', 'b' => 'Wed 6:00pm', 'c' => '60 min'],
 *       ],
 *     ],
 *   ];
 *
 * 'rank' and 'c' are optional per item. When a page doesn't set
 * $sidebarModules at all, realistic Home-feed discovery modules (Top
 * Skills / Top Mentors / Top Learners / Trending Discussions) are shown —
 * see brand guide section 11. Included by includes/footer.php, and only
 * when the page hasn't opted out via $showRightSidebar = false.
 */
$sidebarModules = $sidebarModules ?? [
    [
        'title'      => 'Top Skills',
        'action'     => 'Browse all skills',
        'actionHref' => 'pages/skills/skills.php',
        'items'      => [
            ['rank' => '1', 'a' => 'Python', 'b' => '186 learners', 'c' => 'Programming'],
            ['rank' => '2', 'a' => 'React', 'b' => '124 learners', 'c' => 'Programming'],
            ['rank' => '3', 'a' => 'UI/UX Design', 'b' => '98 learners', 'c' => 'Design'],
            ['rank' => '4', 'a' => 'Data Analysis', 'b' => '87 learners', 'c' => 'Data'],
            ['rank' => '5', 'a' => 'Public Speaking', 'b' => '61 learners', 'c' => 'Communication'],
        ],
    ],
    [
        'title'      => 'Top Mentors',
        'action'     => 'Find a mentor',
        'actionHref' => 'pages/mentors/find-mentors.php',
        'items'      => [
            ['rank' => '1', 'a' => 'Rahim Ahmed', 'b' => '520 pts', 'c' => 'Computer Science'],
            ['rank' => '2', 'a' => 'Sara Khan', 'b' => '410 pts', 'c' => 'Business Administration'],
            ['rank' => '3', 'a' => 'Hasan Mahmud', 'b' => '365 pts', 'c' => 'Electrical Engineering'],
        ],
    ],
    [
        'title' => 'Top Learners',
        'items' => [
            ['rank' => '1', 'a' => 'Nabila Rahman', 'b' => '412 pts', 'c' => 'Computer Science'],
            ['rank' => '2', 'a' => 'Sara Khan', 'b' => '366 pts', 'c' => 'Business Administration'],
            ['rank' => '3', 'a' => 'Imran Chowdhury', 'b' => '318 pts', 'c' => 'English'],
        ],
    ],
    [
        'title' => 'Trending Discussions',
        'items' => [
            ['a' => 'Database normalization, plainly', 'b' => '24'],
            ['a' => 'Which laptop for ML coursework?', 'b' => '41'],
            ['a' => 'Free UI kits worth using', 'b' => '17'],
        ],
    ],
];
?>
<aside class="ukn-sidebar-right" aria-label="Related">
  <?php foreach ($sidebarModules as $module): ?>
    <div class="ukn-shell-module">
      <div class="ukn-shell-module__header">
        <span class="ukn-eyebrow"><?= htmlspecialchars($module['title']) ?></span>
      </div>
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
      <?php if (!empty($module['action'])): ?>
        <a href="<?= htmlspecialchars($module['actionHref'] ?? '#') ?>" class="ukn-shell-module__action">
          <?= htmlspecialchars($module['action']) ?>
        </a>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <p class="ukn-sidebar-right__note">
    UKN Community Guidelines &middot; Help &middot; Privacy<br>
    Student Union Building, Room 214
  </p>
</aside>
