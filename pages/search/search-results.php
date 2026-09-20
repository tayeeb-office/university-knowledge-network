<?php
require_once __DIR__ . '/../../components/search-result-item.php';
require_once __DIR__ . '/../../components/empty-state.php';
$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$q = trim((string) ($_GET['q'] ?? ''));
$qLower = strtolower($q);
$searchRank = static function (string $qLower, string $primary, array $tier3 = [], array $tier4 = []): ?int {
    if ($qLower === '') {
        return null;
    }
    $primaryLower = strtolower($primary);
    if ($primaryLower === $qLower) {
        return 1;
    }
    if (strpos($primaryLower, $qLower) !== false) {
        return 2;
    }
    foreach ($tier3 as $field) {
        if ($field !== '' && stripos((string) $field, $qLower) !== false) {
            return 3;
        }
    }
    foreach ($tier4 as $field) {
        if ($field !== '' && stripos((string) $field, $qLower) !== false) {
            return 4;
        }
    }
    return null;
};


$mockPosts = [
    ['id' => 1, 'title' => 'Need Help Understanding Database Normalization', 'author' => 'Nabila Rahman', 'skills' => ['Database', 'MySQL', 'DBMS'], 'excerpt' => 'A learner asking about the practical difference between 2NF and 3NF.', 'votes' => 24, 'comments' => 8],
    ['id' => 2, 'title' => 'A Simple Way to Start Learning Python for Data Analysis', 'author' => 'Rahim Ahmed', 'skills' => ['Python', 'Data Analysis'], 'excerpt' => 'Skip the theory-heavy courses at first and start with pandas on a dataset you actually care about.', 'votes' => 48, 'comments' => 12],
    ['id' => 3, 'title' => 'Best Resources for Learning MySQL Joins?', 'author' => 'Ayesha Rahman', 'skills' => ['MySQL', 'Database'], 'excerpt' => 'Looking for practice problems that go beyond simple INNER JOIN examples.', 'votes' => 31, 'comments' => 6],
];
$mockSkills = [
    ['id' => 1, 'name' => 'Python', 'category' => 'Programming', 'mentors' => 124, 'learners' => 340, 'learningState' => 'added', 'teachingState' => 'added'],
    ['id' => 2, 'name' => 'MySQL', 'category' => 'Data', 'mentors' => 82, 'learners' => 214, 'learningState' => 'added', 'teachingState' => 'add'],
    ['id' => 5, 'name' => 'Data Analysis', 'category' => 'Data', 'mentors' => 91, 'learners' => 256, 'learningState' => 'added', 'teachingState' => 'added'],
];
$mockMentors = [
    ['name' => 'Rahim Ahmed', 'initials' => 'RA', 'department' => 'Computer Science', 'primarySkill' => 'Python', 'otherSkills' => ['Database Design', 'Data Analysis'], 'rating' => 4.9, 'points' => 520, 'profileHref' => ukn_route_href('mentor-profile') . '&id=2'],
    ['name' => 'Farhan Kabir', 'initials' => 'FK', 'department' => 'Computer Science', 'primarySkill' => 'MySQL', 'otherSkills' => ['Database Design', 'Backend Development'], 'rating' => 4.9, 'points' => 462, 'profileHref' => ukn_route_href('mentor-profile')],
    ['name' => 'Ayesha Rahman', 'initials' => 'AR', 'department' => 'Computer Science', 'primarySkill' => 'React', 'otherSkills' => ['JavaScript', 'UI/UX Design'], 'rating' => 4.7, 'points' => 368, 'profileHref' => ukn_route_href('mentor-profile')],
];
$mockLearners = [
    ['name' => 'Nabila Rahman', 'department' => 'Computer Science', 'skills' => ['Python', 'MySQL', 'Data Analysis'], 'points' => 412, 'profileHref' => ukn_route_href('my-profile'), 'following' => null],
    ['name' => 'Imran Chowdhury', 'department' => 'English', 'skills' => ['Public Speaking', 'Presentation'], 'points' => 318, 'profileHref' => ukn_route_href('learner-profile') . '&id=1', 'following' => false],
];
$results = [];
foreach ($mockPosts as $post) {
    $rank = $searchRank($qLower, $post['title'], array_merge($post['skills'], [$post['author']]), [$post['excerpt']]);
    if ($rank === null) {
        continue;
    }
    $results[] = [
        'type' => 'post', 'rank' => $rank, 'title' => $post['title'],
        'meta' => $post['author'] . ' · ' . implode(', ', $post['skills']) . ' · ' . $post['votes'] . ' votes · ' . $post['comments'] . ' comments',
        'href' => ukn_route_href('post-details') . '&id=' . $post['id'],
    ];
}
foreach ($mockSkills as $skill) {
    $rank = $searchRank($qLower, $skill['name'], [$skill['category']]);
    if ($rank === null) {
        continue;
    }
    $results[] = [
        'type' => 'skill', 'rank' => $rank, 'title' => $skill['name'],
        'meta' => $skill['category'] . ' · ' . $skill['mentors'] . ' mentors · ' . $skill['learners'] . ' learners',
        'href' => ukn_route_href('skill-details') . '&id=' . $skill['id'],
        'learningState' => $isMentor ? null : $skill['learningState'],
        'teachingState' => $isMentor ? $skill['teachingState'] : null,
    ];
}

foreach ($mockMentors as $mentor) {
    $rank = $searchRank($qLower, $mentor['name'], array_merge([$mentor['department'], $mentor['primarySkill']], $mentor['otherSkills']));
    if ($rank === null) {
        continue;
    }
    $results[] = [
        'type' => 'mentor', 'rank' => $rank, 'title' => $mentor['name'],
        'meta' => $mentor['department'] . ' · teaches ' . $mentor['primarySkill'] . ' · ★ ' . $mentor['rating'] . ' · ' . $mentor['points'] . ' points',
        'href' => $mentor['profileHref'],
        'initials' => $mentor['initials'], 'department' => $mentor['department'], 'skill' => $mentor['primarySkill'], 'rating' => $mentor['rating'],
    ];
}
foreach ($mockLearners as $learner) {
    $rank = $searchRank($qLower, $learner['name'], array_merge([$learner['department']], $learner['skills']));
    if ($rank === null) {
        continue;
    }
    $results[] = [
        'type' => 'learner', 'rank' => $rank, 'title' => $learner['name'],
        'meta' => $learner['department'] . ' · learning ' . implode(', ', $learner['skills']) . ' · ' . $learner['points'] . ' points',
        'href' => $learner['profileHref'],
        'following' => $learner['following'],
    ];
}
usort($results, static fn (array $a, array $b): int => $a['rank'] <=> $b['rank']);
$counts = ['all' => count($results), 'skill' => 0, 'mentor' => 0, 'learner' => 0, 'post' => 0];
foreach ($results as $result) {
    $counts[$result['type']]++;
}

$tabs = [
    'all'     => 'All',
    'skill'   => 'Skills',
    'mentor'  => 'Mentors',
    'learner' => 'Learners',
    'post'    => 'Posts',
];
$typeEmptyCopy = [
    'skill'   => 'skills',
    'mentor'  => 'mentors',
    'learner' => 'learners',
    'post'    => 'posts',
];
?>
<div class="ukn-page-header">
  <div>
    <h1>Search Results</h1>
    <p class="ukn-page-header__sub">
      <?php if ($q === ''): ?>
        Use the search bar to find skills, mentors, learners and discussions.
      <?php else: ?>
        Results for &ldquo;<?= htmlspecialchars($q) ?>&rdquo;
      <?php endif; ?>
    </p>
  </div>
</div>
<?php if ($q === ''): ?>
  <?php ukn_empty_state([
      'icon' => 'search',
      'title' => 'Search University Knowledge Network',
      'message' => 'Use the search bar above to find skills, mentors, learners and discussions.',
      'dashed' => true,
  ]); ?>
<?php elseif ($counts['all'] === 0): ?>
  <?php ukn_empty_state([
      'icon' => 'search_off',
      'title' => 'No results found for “' . $q . '”.',
      'message' => 'Try another keyword or search a broader topic.',
      'action' => ['label' => 'Clear Search', 'href' => ukn_route_href('search')],
      'dashed' => true,
  ]); ?>
<?php else: ?>
  <div class="ukn-tabs-pill mb-3" data-search-filters role="group" aria-label="Filter search results by type">
    <?php foreach ($tabs as $type => $label): ?>
      <button type="button" class="ukn-tab-pill<?= $type === 'all' ? ' is-active' : '' ?>" data-search-filter="<?= $type ?>" aria-pressed="<?= $type === 'all' ? 'true' : 'false' ?>"><?= $label ?> (<?= $counts[$type] ?>)</button>
    <?php endforeach; ?>
  </div>
  <p class="ukn-body-sm mb-3" data-search-summary role="status"><?= $counts['all'] ?> result<?= $counts['all'] === 1 ? '' : 's' ?> for &ldquo;<?= htmlspecialchars($q) ?>&rdquo;</p>
  <div data-search-results>
    <?php foreach ($results as $result): ukn_search_result_item($result); endforeach; ?>
  </div>

  <?php foreach ($typeEmptyCopy as $type => $label): ?>
    <div hidden data-search-empty="<?= $type ?>">
      <?php ukn_empty_state([
          'icon' => 'search_off',
          'title' => 'No ' . $label . ' found for “' . $q . '”.',
          'message' => 'Switch to another category or try a different keyword.',
          'dashed' => true,
      ]); ?>
    </div>
  <?php endforeach; ?>

<?php endif; ?>