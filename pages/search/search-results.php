<?php
require_once __DIR__ . '/../../components/search-result-item.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/skills.php';
require_once __DIR__ . '/../../backend/helpers/community.php';
require_once __DIR__ . '/../../backend/helpers/search.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$mySkills = uknCurrentUserSkills();
$myLearningSkills = array_values($mySkills['learning']);
$myTeachingSkills = array_values($mySkills['teaching']);
$canToggleSkills = !empty($currentUser['loggedIn']);
// Step 42: string-only, whitespace-collapsed, length-capped query (arrays and invalid UTF-8 become '').
$q = uknSearchQueryFromRequest();
$qLower = mb_strtolower($q, 'UTF-8');
$searchRank = static function (string $qLower, string $primary, array $tier3 = [], array $tier4 = []): ?int {
    if ($qLower === '') {
        return null;
    }
    $primaryLower = mb_strtolower($primary, 'UTF-8');
    if ($primaryLower === $qLower) {
        return 1;
    }
    if (strpos($primaryLower, $qLower) !== false) {
        return 2;
    }
    foreach ($tier3 as $field) {
        if ($field !== '' && mb_stripos((string) $field, $qLower, 0, 'UTF-8') !== false) {
            return 3;
        }
    }
    foreach ($tier4 as $field) {
        if ($field !== '' && mb_stripos((string) $field, $qLower, 0, 'UTF-8') !== false) {
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
        // All LIKE patterns escape % _ ! and use ESCAPE '!'; values are always bound.
        $like = uknLikeContains($q);
        $limit = UKN_SEARCH_RESULT_LIMIT;

        // Posts: title/content go through the FULLTEXT(title, content) index in boolean mode
        // (prefix terms, relevance-ranked). Queries with no indexable word (e.g. "Go", "C#",
        // only stopwords) fall back to an escaped LIKE on title/content. Author names and
        // skill tags are matched with LIKE. Candidate ids are merged, then joined once.
        $ftTerms = uknFulltextTerms($q);
        if ($ftTerms !== '') {
            $textBranch = "SELECT id, MATCH(title, content) AGAINST (? IN BOOLEAN MODE) AS relevance
                           FROM posts WHERE MATCH(title, content) AGAINST (? IN BOOLEAN MODE)";
            $textParams = [$ftTerms, $ftTerms];
        } else {
            $textBranch = "SELECT id, 0 AS relevance FROM posts
                           WHERE title LIKE ? ESCAPE '!' OR content LIKE ? ESCAPE '!'";
            $textParams = [$like, $like];
        }
        $postsStmt = $pdo->prepare(
            "SELECT p.id, p.title, p.content AS excerpt, p.vote_score AS votes, p.comment_count AS comments,
                    u.id AS author_id, u.full_name AS author, MAX(m.relevance) AS relevance
             FROM (
                 {$textBranch}
                 UNION ALL
                 SELECT p2.id, 0 FROM posts p2 JOIN users u2 ON u2.id = p2.user_id
                 WHERE u2.full_name LIKE ? ESCAPE '!'
                 UNION ALL
                 SELECT ps.post_id, 0 FROM post_skills ps JOIN skills sk ON sk.id = ps.skill_id
                 WHERE sk.name LIKE ? ESCAPE '!'
             ) m
             JOIN posts p ON p.id = m.id
             JOIN users u ON u.id = p.user_id
             WHERE p.status = 'visible'
             GROUP BY p.id, p.title, p.content, p.vote_score, p.comment_count, p.created_at, u.id, u.full_name
             ORDER BY relevance DESC, p.created_at DESC, p.id DESC
             LIMIT {$limit}"
        );
        $postsStmt->execute(array_merge($textParams, [$like, $like]));
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
                    COUNT(CASE WHEN us.skill_type = 'teaching' THEN 1 END) AS mentors,
                    COUNT(CASE WHEN us.skill_type = 'learning' THEN 1 END) AS learners
             FROM skills s
             JOIN skill_categories sc ON sc.id = s.category_id
             LEFT JOIN user_skills us ON us.skill_id = s.id
             WHERE s.status = 'active' AND (s.name LIKE ? ESCAPE '!' OR sc.name LIKE ? ESCAPE '!')
             GROUP BY s.id, s.name, sc.name
             ORDER BY s.name, s.id
             LIMIT {$limit}"
        );
        $skillsStmt->execute([$like, $like]);
        $mockSkills = array_map(static function (array $row) use ($isMentor, $myLearningSkills, $myTeachingSkills): array {
            $row['mentors'] = (int) $row['mentors'];
            $row['learners'] = (int) $row['learners'];
            $row['learningState'] = in_array($row['name'], $myLearningSkills, true) ? 'added' : 'add';
            $row['teachingState'] = in_array($row['name'], $myTeachingSkills, true) ? 'added' : 'add';
            return $row;
        }, $skillsStmt->fetchAll());

        // Points come from the point_transactions ledger and ratings from session_ratings (via
        // the mentor_rating_summary VIEW), the same sources the leaderboard (Step 43) and
        // recommendations (Step 28) use.
        $mentorsStmt = $pdo->prepare(
            "SELECT u.id, u.full_name AS name, u.initials, r.avg_rating AS rating, COALESCE(lp.points, 0) AS points,
                    d.name AS department
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN mentor_rating_summary r ON r.mentor_id = u.id
             LEFT JOIN (SELECT user_id, SUM(amount) AS points FROM point_transactions
                        WHERE point_type = 'mentor' GROUP BY user_id) lp ON lp.user_id = u.id
             WHERE u.role = 'dual' AND u.status = 'active' AND (
                 u.full_name LIKE ? ESCAPE '!' OR d.name LIKE ? ESCAPE '!' OR
                 EXISTS (SELECT 1 FROM user_skills us JOIN skills sk ON sk.id = us.skill_id
                         WHERE us.user_id = u.id AND us.skill_type = 'teaching' AND sk.name LIKE ? ESCAPE '!')
             )
             ORDER BY r.avg_rating IS NULL, r.avg_rating DESC, u.full_name, u.id
             LIMIT {$limit}"
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
            "SELECT u.id, u.full_name AS name, COALESCE(lp.points, 0) AS points, d.name AS department
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN (SELECT user_id, SUM(amount) AS points FROM point_transactions
                        WHERE point_type = 'learning' GROUP BY user_id) lp ON lp.user_id = u.id
             WHERE u.role IN ('learner', 'dual') AND u.status = 'active' AND (
                 u.full_name LIKE ? ESCAPE '!' OR d.name LIKE ? ESCAPE '!' OR
                 EXISTS (SELECT 1 FROM user_skills us JOIN skills sk ON sk.id = us.skill_id
                         WHERE us.user_id = u.id AND us.skill_type = 'learning' AND sk.name LIKE ? ESCAPE '!')
             )
             ORDER BY points DESC, u.full_name, u.id
             LIMIT {$limit}"
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
                // Follow button for logged-in viewers, except on their own result.
                $learner['following'] = !empty($currentUser['loggedIn']) && (int) $learner['id'] !== UKN_CURRENT_USER_ID
                    ? isset(uknCurrentUserFollowingIds()[(int) $learner['id']])
                    : null;
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
// SQL already decided what matches; $searchRank only orders the tiers (4 = matched in SQL,
// e.g. a FULLTEXT word match, without the whole query appearing as a substring).
foreach ($mockPosts as $post) {
    $rank = $searchRank($qLower, $post['title'], array_merge($post['skills'], [$post['author']]), [$post['excerpt']]) ?? 4;
    $results[] = [
        'type' => 'post', 'rank' => $rank, 'title' => $post['title'],
        'meta' => $post['author'] . ' · ' . implode(', ', $post['skills']) . ' · ' . $post['votes'] . ' votes · ' . $post['comments'] . ' comments',
        'href' => ukn_route_href('post-details') . '&id=' . $post['id'],
    ];
}
foreach ($mockSkills as $skill) {
    $rank = $searchRank($qLower, $skill['name'], [$skill['category']]) ?? 4;
    $results[] = [
        'type' => 'skill', 'rank' => $rank, 'title' => $skill['name'],
        'meta' => $skill['category'] . ' · ' . $skill['mentors'] . ' mentors · ' . $skill['learners'] . ' learners',
        'href' => ukn_route_href('skill-details') . '&id=' . $skill['id'],
        'skillId' => (int) $skill['id'],
        'learningState' => $canToggleSkills && !$isMentor ? $skill['learningState'] : null,
        'teachingState' => $canToggleSkills && $isMentor ? $skill['teachingState'] : null,
    ];
}

foreach ($mockMentors as $mentor) {
    $rank = $searchRank($qLower, $mentor['name'], array_merge([$mentor['department'], $mentor['primarySkill']], $mentor['otherSkills'])) ?? 4;
    $results[] = [
        'type' => 'mentor', 'rank' => $rank, 'title' => $mentor['name'], 'mentorId' => (int) $mentor['id'],
        'meta' => $mentor['department'] . ' · teaches ' . $mentor['primarySkill'] . ($mentor['rating'] !== null ? ' · ★ ' . $mentor['rating'] : '') . ' · ' . $mentor['points'] . ' points',
        'href' => $mentor['profileHref'],
        'initials' => $mentor['initials'], 'department' => $mentor['department'], 'skill' => $mentor['primarySkill'], 'rating' => $mentor['rating'],
    ];
}
foreach ($mockLearners as $learner) {
    $rank = $searchRank($qLower, $learner['name'], array_merge([$learner['department']], $learner['skills'])) ?? 4;
    $results[] = [
        'type' => 'learner', 'rank' => $rank, 'title' => $learner['name'],
        'meta' => $learner['department'] . ' · learning ' . implode(', ', $learner['skills']) . ' · ' . $learner['points'] . ' points',
        'href' => $learner['profileHref'],
        'following' => $learner['following'], 'memberId' => (int) $learner['id'],
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
  <p class="ukn-body-sm mb-3" data-search-summary data-search-query="<?= htmlspecialchars($q) ?>" role="status"><?= $counts['all'] ?> result<?= $counts['all'] === 1 ? '' : 's' ?> for &ldquo;<?= htmlspecialchars($q) ?>&rdquo;</p>
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