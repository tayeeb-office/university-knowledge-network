<?php
require_once __DIR__ . '/../../components/search-result-item.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
// TODO(auth): read from the logged-in user's own user_skills rows once a real session exists.
$myLearningSkills = ['Python', 'MySQL', 'Data Analysis', 'Public Speaking'];
$myTeachingSkills = ['Python', 'Database Design', 'Data Analysis'];
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


$mockPosts = [];
$mockSkills = [];
$mockMentors = [];
$mockLearners = [];
$searchDbError = false;

if ($q !== '') {
    try {
        $pdo = getDatabaseConnection();
        $like = '%' . $q . '%';

        $postsStmt = $pdo->prepare(
            "SELECT p.id, p.title, p.content AS excerpt, p.vote_score AS votes, p.comment_count AS comments,
                    u.full_name AS author
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.status = 'visible' AND (
                 p.title LIKE ? OR p.content LIKE ? OR u.full_name LIKE ? OR
                 EXISTS (SELECT 1 FROM post_skills ps JOIN skills sk ON sk.id = ps.skill_id
                         WHERE ps.post_id = p.id AND sk.name LIKE ?)
             )
             ORDER BY p.created_at DESC
             LIMIT 30"
        );
        $postsStmt->execute([$like, $like, $like, $like]);
        $mockPosts = $postsStmt->fetchAll();
        if ($mockPosts) {
            $postIds = array_column($mockPosts, 'id');
            $placeholders = implode(',', array_fill(0, count($postIds), '?'));
            $tagsStmt = $pdo->prepare(
                "SELECT ps.post_id, s.name FROM post_skills ps JOIN skills s ON s.id = ps.skill_id
                 WHERE ps.post_id IN ($placeholders)"
            );
            $tagsStmt->execute($postIds);
            $tagsByPost = [];
            foreach ($tagsStmt->fetchAll() as $row) {
                $tagsByPost[$row['post_id']][] = $row['name'];
            }
            foreach ($mockPosts as &$post) {
                $post['skills'] = $tagsByPost[$post['id']] ?? [];
            }
            unset($post);
        }

        $skillsStmt = $pdo->prepare(
            "SELECT s.id, s.name, sc.name AS category,
                    (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'teaching') AS mentors,
                    (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'learning') AS learners
             FROM skills s JOIN skill_categories sc ON sc.id = s.category_id
             WHERE s.status = 'active' AND (s.name LIKE ? OR sc.name LIKE ?)
             ORDER BY s.name
             LIMIT 30"
        );
        $skillsStmt->execute([$like, $like]);
        $mockSkills = array_map(static function (array $row) use ($isMentor, $myLearningSkills, $myTeachingSkills): array {
            $row['mentors'] = (int) $row['mentors'];
            $row['learners'] = (int) $row['learners'];
            $row['learningState'] = in_array($row['name'], $myLearningSkills, true) ? 'added' : 'add';
            $row['teachingState'] = in_array($row['name'], $myTeachingSkills, true) ? 'added' : 'add';
            return $row;
        }, $skillsStmt->fetchAll());

        $mentorsStmt = $pdo->prepare(
            "SELECT u.id, u.full_name AS name, u.initials, u.avg_rating AS rating, u.mentor_points AS points,
                    d.name AS department
             FROM users u LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.role IN ('mentor', 'dual') AND u.status = 'active' AND (
                 u.full_name LIKE ? OR d.name LIKE ? OR
                 EXISTS (SELECT 1 FROM user_skills us JOIN skills sk ON sk.id = us.skill_id
                         WHERE us.user_id = u.id AND us.skill_type = 'teaching' AND sk.name LIKE ?)
             )
             ORDER BY u.avg_rating DESC
             LIMIT 30"
        );
        $mentorsStmt->execute([$like, $like, $like]);
        $mockMentors = $mentorsStmt->fetchAll();
        if ($mockMentors) {
            $mentorIds = array_column($mockMentors, 'id');
            $placeholders = implode(',', array_fill(0, count($mentorIds), '?'));
            $mentorSkillsStmt = $pdo->prepare(
                "SELECT us.user_id, s.name
                 FROM user_skills us JOIN skills s ON s.id = us.skill_id
                 WHERE us.user_id IN ($placeholders) AND us.skill_type = 'teaching'
                 ORDER BY us.user_id, us.sessions_count DESC, us.proficiency DESC"
            );
            $mentorSkillsStmt->execute($mentorIds);
            $skillsByMentor = [];
            foreach ($mentorSkillsStmt->fetchAll() as $row) {
                $skillsByMentor[$row['user_id']][] = $row['name'];
            }
            foreach ($mockMentors as &$mentor) {
                $skillNames = $skillsByMentor[$mentor['id']] ?? [];
                $mentor['primarySkill'] = $skillNames[0] ?? '';
                $mentor['otherSkills'] = array_slice($skillNames, 1);
                $mentor['department'] = (string) ($mentor['department'] ?? '');
                $mentor['profileHref'] = ukn_route_href('mentor-profile') . '&id=' . $mentor['id'];
            }
            unset($mentor);
        }

        $learnersStmt = $pdo->prepare(
            "SELECT u.id, u.full_name AS name, u.learning_points AS points, d.name AS department
             FROM users u LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.role IN ('learner', 'dual') AND u.status = 'active' AND (
                 u.full_name LIKE ? OR d.name LIKE ? OR
                 EXISTS (SELECT 1 FROM user_skills us JOIN skills sk ON sk.id = us.skill_id
                         WHERE us.user_id = u.id AND us.skill_type = 'learning' AND sk.name LIKE ?)
             )
             ORDER BY u.learning_points DESC
             LIMIT 30"
        );
        $learnersStmt->execute([$like, $like, $like]);
        $mockLearners = $learnersStmt->fetchAll();
        if ($mockLearners) {
            $learnerIds = array_column($mockLearners, 'id');
            $placeholders = implode(',', array_fill(0, count($learnerIds), '?'));
            $learnerSkillsStmt = $pdo->prepare(
                "SELECT us.user_id, s.name
                 FROM user_skills us JOIN skills s ON s.id = us.skill_id
                 WHERE us.user_id IN ($placeholders) AND us.skill_type = 'learning'
                 ORDER BY us.user_id, s.name"
            );
            $learnerSkillsStmt->execute($learnerIds);
            $skillsByLearner = [];
            foreach ($learnerSkillsStmt->fetchAll() as $row) {
                $skillsByLearner[$row['user_id']][] = $row['name'];
            }
            foreach ($mockLearners as &$learner) {
                $learner['skills'] = $skillsByLearner[$learner['id']] ?? [];
                $learner['department'] = (string) ($learner['department'] ?? '');
                // TODO(auth): follow state needs the current session user; omitted for now.
                $learner['following'] = null;
                $learner['profileHref'] = ukn_route_href('learner-profile') . '&id=' . $learner['id'];
            }
            unset($learner);
        }
    } catch (Throwable $e) {
        error_log('[UKN search-results] ' . $e->getMessage());
        $searchDbError = true;
        $mockPosts = [];
        $mockSkills = [];
        $mockMentors = [];
        $mockLearners = [];
    }
}
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
<?php elseif ($searchDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to search right now.',
      'message' => 'Something went wrong while searching. Please try again shortly.',
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