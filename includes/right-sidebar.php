<?php
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/format.php';

// TODO(auth): replace with the real session user id; mirrors index.php's own hardcoded
// demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

if (!function_exists('ukn_sidebar_modules_for_context')) {
    /**
     * Builds the right-sidebar's per-context module data from the real database.
     * Each case runs its own small, targeted queries (see DATABASE_READ_INTEGRATION_PLAN.md
     * conventions already used across pages/*.php) rather than one large combined query.
     * Any DB failure, or a module with no real data to show, simply omits that module —
     * ukn_render_sidebar_module()'s rendering logic is unchanged, so an empty $items array
     * (or an empty overall return) renders nothing instead of a fabricated value.
     */
    function ukn_sidebar_modules_for_context(string $context, array $currentUser, ?PDO $pdo = null): array
    {
        try {
            $pdo = $pdo ?? getDatabaseConnection();
        } catch (Throwable $e) {
            error_log('[UKN right-sidebar] ' . $e->getMessage());
            return [];
        }

        try {
            switch ($context) {
                case 'dashboard-learner': {
                    $modules = [];

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND skill_type = 'learning'");
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $skillsInProgress = (int) $stmt->fetchColumn();

                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) FROM mentoring_sessions
                         WHERE learner_id = ? AND status = 'completed' AND completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $sessionsThisMonth = (int) $stmt->fetchColumn();

                    $stmt = $pdo->prepare(
                        "SELECT COALESCE(SUM(duration_minutes), 0) FROM mentoring_sessions
                         WHERE learner_id = ? AND status = 'completed'"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $hoursLearned = round(((int) $stmt->fetchColumn()) / 60, 1);

                    $modules[] = [
                        'type' => 'stat-rows',
                        'title' => 'Learning Summary',
                        'items' => [
                            ['label' => 'Skills in progress', 'value' => (string) $skillsInProgress],
                            ['label' => 'Sessions this month', 'value' => (string) $sessionsThisMonth],
                            ['label' => 'Hours learned', 'value' => (string) $hoursLearned],
                        ],
                    ];

                    $stmt = $pdo->prepare(
                        "SELECT ms.id, ms.scheduled_date, ms.scheduled_time, m.full_name AS mentor, sk.name AS skill
                         FROM mentoring_sessions ms
                         JOIN users m ON m.id = ms.mentor_id
                         JOIN skills sk ON sk.id = ms.skill_id
                         WHERE ms.learner_id = ? AND ms.status = 'accepted' AND ms.scheduled_date >= CURDATE()
                         ORDER BY ms.scheduled_date, ms.scheduled_time LIMIT 2"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $upcoming = array_map(static function (array $row): array {
                        $ts = strtotime($row['scheduled_date'] . ' ' . $row['scheduled_time']);
                        return [
                            'href' => ukn_route_href('sessions'),
                            'title' => $row['skill'] . ' with ' . $row['mentor'],
                            'meta' => date('D g:ia', $ts),
                        ];
                    }, $stmt->fetchAll());
                    if ($upcoming) {
                        $modules[] = ['type' => 'link-list', 'title' => 'Upcoming Sessions', 'action' => 'View all sessions', 'actionHref' => ukn_route_href('sessions'), 'items' => $upcoming];
                    }

                    $stmt = $pdo->prepare(
                        "SELECT title, progress FROM learning_goals
                         WHERE user_id = ? AND status = 'in-progress' ORDER BY target_date ASC LIMIT 2"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $goals = array_map(static function (array $row): array {
                        return ['label' => $row['title'], 'pct' => (int) $row['progress']];
                    }, $stmt->fetchAll());
                    if ($goals) {
                        $modules[] = ['type' => 'progress-list', 'title' => 'Current Goals', 'action' => 'View all goals', 'actionHref' => ukn_route_href('learning-goals'), 'items' => $goals];
                    }

                    $stmt = $pdo->prepare("SELECT learning_points FROM users WHERE id = ?");
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $learningPoints = (int) $stmt->fetchColumn();
                    $stmt = $pdo->prepare(
                        "SELECT COALESCE(SUM(amount), 0) FROM point_transactions
                         WHERE user_id = ? AND point_type = 'learning' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $learningMonth = (int) $stmt->fetchColumn();
                    $modules[] = [
                        'type' => 'stat-rows',
                        'title' => 'Learning Points',
                        'action' => 'View points',
                        'actionHref' => ukn_route_href('points'),
                        'items' => [
                            ['label' => 'Learning points', 'value' => (string) $learningPoints, 'accent' => true],
                            ['label' => 'This month', 'value' => ($learningMonth >= 0 ? '+' : '') . $learningMonth],
                        ],
                    ];

                    $stmt = $pdo->prepare(
                        "SELECT DISTINCT u.id, u.full_name AS name, u.avg_rating AS rating, d.name AS department
                         FROM users u
                         JOIN user_skills us ON us.user_id = u.id AND us.skill_type = 'teaching'
                         LEFT JOIN departments d ON d.id = u.department_id
                         WHERE u.role IN ('mentor', 'dual') AND u.status = 'active' AND u.id != ?
                         ORDER BY u.avg_rating DESC, u.sessions_as_mentor DESC LIMIT 1"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $topMentor = $stmt->fetch();
                    if ($topMentor) {
                        $skillStmt = $pdo->prepare(
                            "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
                             WHERE us.user_id = ? AND us.skill_type = 'teaching'
                             ORDER BY us.sessions_count DESC LIMIT 1"
                        );
                        $skillStmt->execute([$topMentor['id']]);
                        $primarySkill = $skillStmt->fetchColumn();
                        $modules[] = [
                            'type' => 'ranked-list',
                            'title' => 'Recommended Mentor',
                            'action' => 'View recommendations',
                            'actionHref' => ukn_route_href('recommendations'),
                            'items' => [[
                                'a' => $topMentor['name'],
                                'c' => (string) ($topMentor['department'] ?? '') . ($primarySkill ? ' · teaches ' . $primarySkill : ''),
                                'b' => $topMentor['rating'] !== null ? '★ ' . $topMentor['rating'] : '',
                            ]],
                        ];
                    }
                    return $modules;
                }

                case 'dashboard-mentor': {
                    $modules = [];

                    $stmt = $pdo->prepare("SELECT mentor_points, avg_rating, sessions_as_mentor FROM users WHERE id = ?");
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $user = $stmt->fetch();

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mentoring_sessions WHERE mentor_id = ? AND status = 'pending'");
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $pending = (int) $stmt->fetchColumn();

                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) FROM mentoring_sessions
                         WHERE mentor_id = ? AND status = 'accepted' AND scheduled_date >= CURDATE()"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $upcomingCount = (int) $stmt->fetchColumn();

                    $modules[] = [
                        'type' => 'stat-rows',
                        'title' => 'Mentor Overview',
                        'items' => [
                            ['label' => 'Mentor points', 'value' => (string) ($user['mentor_points'] ?? 0), 'accent' => true],
                            ['label' => 'Average rating', 'value' => $user && $user['avg_rating'] !== null ? (string) $user['avg_rating'] : '—'],
                            ['label' => 'Pending requests', 'value' => (string) $pending],
                            ['label' => 'Upcoming sessions', 'value' => (string) $upcomingCount],
                        ],
                    ];

                    $stmt = $pdo->prepare(
                        "SELECT ms.scheduled_date, ms.scheduled_time, l.full_name AS learner, sk.name AS skill
                         FROM mentoring_sessions ms
                         JOIN users l ON l.id = ms.learner_id
                         JOIN skills sk ON sk.id = ms.skill_id
                         WHERE ms.mentor_id = ? AND ms.status = 'accepted' AND ms.scheduled_date >= CURDATE()
                         ORDER BY ms.scheduled_date, ms.scheduled_time LIMIT 2"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $upcoming = array_map(static function (array $row): array {
                        $ts = strtotime($row['scheduled_date'] . ' ' . $row['scheduled_time']);
                        return [
                            'href' => ukn_route_href('sessions'),
                            'title' => $row['skill'] . ' with ' . $row['learner'],
                            'meta' => date('D g:ia', $ts),
                        ];
                    }, $stmt->fetchAll());
                    if ($upcoming) {
                        $modules[] = ['type' => 'link-list', 'title' => 'Upcoming Sessions', 'action' => 'View all sessions', 'actionHref' => ukn_route_href('sessions'), 'items' => $upcoming];
                    }

                    $stmt = $pdo->prepare(
                        "SELECT ms.created_at, l.id, l.full_name AS name, l.sessions_as_learner AS sessions
                         FROM mentoring_sessions ms
                         JOIN users l ON l.id = ms.learner_id
                         WHERE ms.mentor_id = ? AND ms.status IN ('accepted', 'completed')
                         ORDER BY ms.created_at DESC"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $recentLearners = [];
                    $seen = [];
                    foreach ($stmt->fetchAll() as $row) {
                        if (count($recentLearners) >= 2) {
                            break;
                        }
                        if (isset($seen[$row['id']])) {
                            continue;
                        }
                        $seen[$row['id']] = true;
                        $recentLearners[] = ['a' => $row['name'], 'b' => ((int) $row['sessions']) . ' sessions'];
                    }
                    if ($recentLearners) {
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Recent Learners', 'items' => $recentLearners];
                    }
                    return $modules;
                }

                case 'skills': {
                    $modules = [];

                    $stmt = $pdo->query(
                        "SELECT s.name, (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'teaching') AS mentors
                         FROM skills s WHERE s.status = 'active'
                         ORDER BY mentors DESC LIMIT 4"
                    );
                    $popular = [];
                    foreach ($stmt->fetchAll() as $i => $row) {
                        $popular[] = ['rank' => (string) ($i + 1), 'a' => $row['name'], 'b' => ((int) $row['mentors']) . ' mentors'];
                    }
                    if ($popular) {
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Popular Skills', 'action' => 'Browse All Skills', 'actionHref' => ukn_route_href('skills'), 'items' => $popular];
                    }

                    // "Trending" = skills with the most mentoring-session requests in the last
                    // 30 days — a real, non-fabricated recency signal (no growth-% metric exists
                    // in the schema, see DATABASE_READ_INTEGRATION_PLAN.md).
                    $stmt = $pdo->prepare(
                        "SELECT sk.name, COUNT(*) AS c FROM mentoring_sessions ms
                         JOIN skills sk ON sk.id = ms.skill_id
                         WHERE ms.requested_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                         GROUP BY sk.id ORDER BY c DESC LIMIT 3"
                    );
                    $stmt->execute();
                    $trending = [];
                    foreach ($stmt->fetchAll() as $i => $row) {
                        $trending[] = ['rank' => (string) ($i + 1), 'a' => $row['name'], 'b' => ((int) $row['c']) . ' requests this month'];
                    }
                    if ($trending) {
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Trending Skills', 'items' => $trending];
                    }

                    $stmt = $pdo->query(
                        "SELECT sc.name, COUNT(s.id) AS c FROM skill_categories sc
                         LEFT JOIN skills s ON s.category_id = sc.id AND s.status = 'active'
                         WHERE sc.status = 'active' GROUP BY sc.id ORDER BY c DESC LIMIT 5"
                    );
                    $categories = array_map(static function (array $row): array {
                        return ['label' => $row['name'], 'value' => (string) ((int) $row['c'])];
                    }, $stmt->fetchAll());
                    if ($categories) {
                        $modules[] = ['type' => 'stat-rows', 'title' => 'Skill Categories', 'items' => $categories];
                    }

                    // "Most Requested" = all-time mentoring-session count per skill.
                    $stmt = $pdo->query(
                        "SELECT sk.name, COUNT(*) AS c FROM mentoring_sessions ms
                         JOIN skills sk ON sk.id = ms.skill_id
                         GROUP BY sk.id ORDER BY c DESC LIMIT 3"
                    );
                    $requested = [];
                    foreach ($stmt->fetchAll() as $i => $row) {
                        $requested[] = ['rank' => (string) ($i + 1), 'a' => $row['name'], 'b' => ((int) $row['c']) . ' requests'];
                    }
                    if ($requested) {
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Most Requested Skills', 'items' => $requested];
                    }
                    return $modules;
                }

                case 'mentors': {
                    $modules = [];

                    $stmt = $pdo->query(
                        "SELECT DISTINCT u.id, u.full_name AS name, u.avg_rating AS rating
                         FROM users u JOIN user_skills us ON us.user_id = u.id AND us.skill_type = 'teaching'
                         WHERE u.role IN ('mentor', 'dual') AND u.status = 'active'
                         ORDER BY u.avg_rating DESC, u.sessions_as_mentor DESC LIMIT 3"
                    );
                    $topRated = $stmt->fetchAll();
                    if ($topRated) {
                        $mentorIds = array_column($topRated, 'id');
                        $placeholders = implode(',', array_fill(0, count($mentorIds), '?'));
                        $skillStmt = $pdo->prepare(
                            "SELECT us.user_id, s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
                             WHERE us.user_id IN ($placeholders) AND us.skill_type = 'teaching'
                             ORDER BY us.user_id, us.sessions_count DESC"
                        );
                        $skillStmt->execute($mentorIds);
                        $primaryByMentor = [];
                        foreach ($skillStmt->fetchAll() as $row) {
                            if (!isset($primaryByMentor[$row['user_id']])) {
                                $primaryByMentor[$row['user_id']] = $row['name'];
                            }
                        }
                        $items = [];
                        foreach ($topRated as $i => $row) {
                            $items[] = [
                                'rank' => (string) ($i + 1),
                                'a' => $row['name'],
                                'c' => $primaryByMentor[$row['id']] ?? '',
                                'b' => $row['rating'] !== null ? '★ ' . $row['rating'] : '—',
                            ];
                        }
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Top Rated Mentors', 'items' => $items];
                    }

                    $today = (int) date('w');
                    $stmt = $pdo->prepare(
                        "SELECT DISTINCT u.full_name AS name, ma.start_time, ma.end_time
                         FROM mentor_availability ma
                         JOIN users u ON u.id = ma.user_id
                         WHERE ma.day_of_week = ? AND ma.is_enabled = 1 AND u.status = 'active'
                         ORDER BY ma.start_time LIMIT 3"
                    );
                    $stmt->execute([$today]);
                    $availableToday = array_map(static function (array $row): array {
                        return [
                            'href' => ukn_route_href('find-mentors'),
                            'title' => $row['name'],
                            'meta' => date('g:ia', strtotime($row['start_time'])),
                        ];
                    }, $stmt->fetchAll());
                    if ($availableToday) {
                        $modules[] = ['type' => 'link-list', 'title' => 'Available Today', 'items' => $availableToday];
                    }

                    $stmt = $pdo->query(
                        "SELECT DISTINCT u.id, u.full_name AS name, u.sessions_as_mentor AS sessions
                         FROM users u JOIN user_skills us ON us.user_id = u.id AND us.skill_type = 'teaching'
                         WHERE u.role IN ('mentor', 'dual') AND u.status = 'active'
                         ORDER BY u.sessions_as_mentor DESC LIMIT 3"
                    );
                    $mostExperienced = [];
                    foreach ($stmt->fetchAll() as $i => $row) {
                        $mostExperienced[] = ['rank' => (string) ($i + 1), 'a' => $row['name'], 'b' => ((int) $row['sessions']) . ' sessions'];
                    }
                    if ($mostExperienced) {
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Most Experienced', 'items' => $mostExperienced];
                    }

                    $stmt = $pdo->query(
                        "SELECT s.name, COUNT(*) AS c FROM user_skills us JOIN skills s ON s.id = us.skill_id
                         WHERE us.skill_type = 'teaching' GROUP BY s.id ORDER BY c DESC LIMIT 3"
                    );
                    $popularMentorSkills = array_map(static function (array $row): array {
                        return ['label' => $row['name'], 'value' => (string) ((int) $row['c'])];
                    }, $stmt->fetchAll());
                    if ($popularMentorSkills) {
                        $modules[] = ['type' => 'stat-rows', 'title' => 'Popular Mentor Skills', 'items' => $popularMentorSkills];
                    }
                    return $modules;
                }

                case 'recommendations': {
                    $modules = [];

                    $stmt = $pdo->prepare(
                        "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
                         WHERE us.user_id = ? AND us.skill_type = 'learning'
                         ORDER BY us.sessions_count DESC LIMIT 1"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $primaryLearningSkill = $stmt->fetchColumn();
                    if ($primaryLearningSkill) {
                        $modules[] = [
                            'type' => 'stat-rows',
                            'title' => 'Your Preferences',
                            'items' => [
                                ['label' => 'Learning skill', 'value' => $primaryLearningSkill, 'accent' => true],
                            ],
                        ];
                    }

                    // No availability-preference field or recommendation-scoring engine exists in
                    // the schema, so "Preferred availability" / "Top recommended skill" are
                    // intentionally omitted rather than fabricated (see DATABASE_READ_INTEGRATION_PLAN.md).
                    $modules[] = [
                        'type' => 'tag-list',
                        'title' => 'Matching Factors',
                        'items' => ['Skill', 'Availability', 'Rating', 'Experience', 'Mentor Points'],
                    ];
                    return $modules;
                }

                case 'sessions': {
                    $activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
                    $column = $activeRole === 'mentor' ? 'mentor_id' : 'learner_id';

                    $stmt = $pdo->prepare(
                        "SELECT status, COUNT(*) AS c FROM mentoring_sessions WHERE {$column} = ? GROUP BY status"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $byStatus = array_column($stmt->fetchAll(), 'c', 'status');
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*) FROM mentoring_sessions WHERE {$column} = ? AND status = 'accepted' AND scheduled_date >= CURDATE()"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $upcomingCount = (int) $stmt->fetchColumn();

                    $modules = [[
                        'type' => 'stat-rows',
                        'title' => 'Session Overview',
                        'items' => [
                            ['label' => 'Pending', 'value' => (string) (int) ($byStatus['pending'] ?? 0)],
                            ['label' => 'Upcoming', 'value' => (string) $upcomingCount],
                            ['label' => 'Completed', 'value' => (string) (int) ($byStatus['completed'] ?? 0)],
                            ['label' => 'Cancelled', 'value' => (string) (int) ($byStatus['cancelled'] ?? 0)],
                        ],
                    ]];

                    $counterpartyCol = $activeRole === 'mentor' ? 'learner_id' : 'mentor_id';
                    $stmt = $pdo->prepare(
                        "SELECT ms.scheduled_date, ms.scheduled_time, u.full_name AS counterparty, sk.name AS skill
                         FROM mentoring_sessions ms
                         JOIN users u ON u.id = ms.{$counterpartyCol}
                         JOIN skills sk ON sk.id = ms.skill_id
                         WHERE ms.{$column} = ? AND ms.status = 'accepted' AND ms.scheduled_date >= CURDATE()
                         ORDER BY ms.scheduled_date, ms.scheduled_time LIMIT 1"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $next = $stmt->fetch();
                    if ($next) {
                        $ts = strtotime($next['scheduled_date'] . ' ' . $next['scheduled_time']);
                        $modules[] = [
                            'type' => 'mini-session',
                            'title' => 'Next Upcoming Session',
                            'items' => [[
                                'day' => date('j', $ts),
                                'month' => date('M', $ts),
                                'title' => $next['skill'] . ' with ' . $next['counterparty'],
                                'meta' => date('D g:ia', $ts),
                            ]],
                        ];
                    }
                    return $modules;
                }

                case 'points': {
                    $stmt = $pdo->prepare("SELECT learning_points, mentor_points FROM users WHERE id = ?");
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $user = $stmt->fetch();
                    $learningPoints = (int) ($user['learning_points'] ?? 0);
                    $mentorPoints = (int) ($user['mentor_points'] ?? 0);

                    $modules = [[
                        'type' => 'stat-rows',
                        'title' => 'Point Summary',
                        'items' => [
                            ['label' => 'Learning points', 'value' => (string) $learningPoints],
                            ['label' => 'Mentor points', 'value' => (string) $mentorPoints],
                            ['label' => 'Total', 'value' => (string) ($learningPoints + $mentorPoints), 'accent' => true],
                        ],
                    ]];

                    $stmt = $pdo->prepare(
                        "SELECT amount, reason FROM point_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 3"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $transactions = array_map(static function (array $row): array {
                        $amount = (int) $row['amount'];
                        return ['label' => $row['reason'], 'value' => ($amount >= 0 ? '+' : '') . $amount, 'accent' => $amount >= 0];
                    }, $stmt->fetchAll());
                    if ($transactions) {
                        $modules[] = ['type' => 'stat-rows', 'title' => 'Recent Transactions', 'action' => 'View full history', 'actionHref' => ukn_route_href('points'), 'items' => $transactions];
                    }
                    return $modules;
                }

                case 'post': {
                    $modules = [];
                    $postId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;

                    $selectBase = "SELECT p.id, u.id AS author_id, u.full_name AS name, u.initials, u.role,
                                u.learning_points, u.mentor_points, d.name AS department
                         FROM posts p JOIN users u ON u.id = p.user_id
                         LEFT JOIN departments d ON d.id = u.department_id
                         WHERE p.status = 'visible' ";
                    $postRow = false;
                    if ($postId) {
                        $stmt = $pdo->prepare($selectBase . "AND p.id = ?");
                        $stmt->execute([$postId]);
                        $postRow = $stmt->fetch();
                    }
                    if ($postRow === false) {
                        // No specific/visible post requested: fall back to the most recent visible
                        // post, mirroring pages/community/post-details.php's own fallback behaviour.
                        $stmt = $pdo->prepare($selectBase . "ORDER BY p.created_at DESC LIMIT 1");
                        $stmt->execute();
                        $postRow = $stmt->fetch();
                    }

                    if ($postRow !== false) {
                        $pid = (int) $postRow['id'];
                        $authorRole = in_array($postRow['role'], ['mentor', 'dual'], true) ? 'Mentor' : 'Learner';
                        $authorPoints = $authorRole === 'Mentor' ? (int) $postRow['mentor_points'] : (int) $postRow['learning_points'];
                        $modules[] = [
                            'type' => 'author-card',
                            'title' => 'About Author',
                            'items' => [[
                                'initials' => $postRow['initials'],
                                'name' => $postRow['name'],
                                'department' => (string) ($postRow['department'] ?? ''),
                                'role' => $authorRole,
                                'points' => (string) $authorPoints,
                                'followLabel' => 'Follow',
                            ]],
                        ];

                        $tagsStmt = $pdo->prepare(
                            "SELECT s.id, s.name FROM post_skills ps JOIN skills s ON s.id = ps.skill_id WHERE ps.post_id = ?"
                        );
                        $tagsStmt->execute([$pid]);
                        $tagRows = $tagsStmt->fetchAll();
                        if ($tagRows) {
                            $modules[] = ['type' => 'tag-list', 'title' => 'Related Skills', 'items' => array_column($tagRows, 'name')];
                        }

                        $skillIds = array_column($tagRows, 'id');
                        if ($skillIds) {
                            $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
                            $relatedStmt = $pdo->prepare(
                                "SELECT DISTINCT p2.id, p2.title, p2.comment_count
                                 FROM posts p2 JOIN post_skills ps2 ON ps2.post_id = p2.id
                                 WHERE ps2.skill_id IN ($placeholders) AND p2.id != ? AND p2.status = 'visible'
                                 ORDER BY p2.created_at DESC LIMIT 2"
                            );
                            $relatedStmt->execute(array_merge($skillIds, [$pid]));
                            $relatedRows = $relatedStmt->fetchAll();
                            if ($relatedRows) {
                                $modules[] = [
                                    'type' => 'link-list',
                                    'title' => 'Related Discussions',
                                    'items' => array_map(static function (array $r): array {
                                        return [
                                            'href' => ukn_route_href('post-details') . '&id=' . $r['id'],
                                            'title' => $r['title'],
                                            'meta' => ((int) $r['comment_count']) . ' comments',
                                        ];
                                    }, $relatedRows),
                                ];
                            }
                        }
                    }
                    return $modules;
                }

                case 'leaderboard': {
                    $activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
                    $isMentor = $activeRole === 'mentor';
                    $pointColumn = $isMentor ? 'mentor_points' : 'learning_points';

                    $stmt = $pdo->prepare("SELECT {$pointColumn} FROM users WHERE id = ?");
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $myPoints = (int) $stmt->fetchColumn();

                    $rankStmt = $pdo->prepare(
                        "SELECT COUNT(*) + 1 FROM users WHERE status = 'active' AND {$pointColumn} > ?"
                    );
                    $rankStmt->execute([$myPoints]);
                    $myRank = (int) $rankStmt->fetchColumn();

                    $leaderStmt = $pdo->query(
                        "SELECT full_name FROM users WHERE status = 'active' AND role IN ('" . ($isMentor ? 'mentor' : 'learner') . "', 'dual')
                         ORDER BY {$pointColumn} DESC LIMIT 1"
                    );
                    $leaderName = $leaderStmt->fetchColumn();

                    return [[
                        'type' => 'stat-rows',
                        'title' => 'Your Standing',
                        'action' => 'View Leaderboard',
                        'actionHref' => ukn_route_href('leaderboard'),
                        'items' => array_values(array_filter([
                            ['label' => 'Your ' . ($isMentor ? 'mentor' : 'learning') . ' rank', 'value' => '#' . $myRank, 'accent' => true],
                            ['label' => 'Your ' . ($isMentor ? 'mentor' : 'learning') . ' points', 'value' => (string) $myPoints],
                            $leaderName ? ['label' => 'Current leader', 'value' => ((int) $myRank === 1 ? 'You (' . $leaderName . ')' : $leaderName)] : null,
                        ])),
                    ]];
                }

                case 'profile': {
                    $activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
                    $isDualRoleUser = !empty($currentUser['loggedIn']) && !empty($currentUser['dualRole']);
                    $rolesToRender = $isDualRoleUser ? ['learner', 'mentor'] : [$activeRole === 'mentor' ? 'mentor' : 'learner'];

                    $stmt = $pdo->prepare(
                        "SELECT learning_points, mentor_points, avg_rating, sessions_as_learner, sessions_as_mentor
                         FROM users WHERE id = ?"
                    );
                    $stmt->execute([UKN_DEMO_USER_ID]);
                    $user = $stmt->fetch();

                    $profileModules = [];
                    foreach ($rolesToRender as $roleKey) {
                        $module = $roleKey === 'mentor'
                            ? [
                                'type' => 'stat-rows',
                                'title' => 'Mentor Overview',
                                'items' => [
                                    ['label' => 'Mentor points', 'value' => (string) ($user['mentor_points'] ?? 0), 'accent' => true],
                                    ['label' => 'Rating', 'value' => $user && $user['avg_rating'] !== null ? (string) $user['avg_rating'] : '—'],
                                    ['label' => 'Completed sessions', 'value' => (string) ($user['sessions_as_mentor'] ?? 0)],
                                ],
                            ]
                            : [
                                'type' => 'stat-rows',
                                'title' => 'Learner Overview',
                                'items' => [
                                    ['label' => 'Learning points', 'value' => (string) ($user['learning_points'] ?? 0), 'accent' => true],
                                    ['label' => 'Completed sessions', 'value' => (string) ($user['sessions_as_learner'] ?? 0)],
                                ],
                            ];
                        if ($isDualRoleUser) {
                            $module['role'] = $roleKey;
                            $module['hidden'] = ($roleKey !== $activeRole);
                        }
                        $profileModules[] = $module;

                        if ($roleKey === 'learner') {
                            // Matches the original module set exactly: only the learner branch
                            // has a skills tag-list ("Main Skills") and a goals progress-list.
                            $skillStmt = $pdo->prepare(
                                "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
                                 WHERE us.user_id = ? AND us.skill_type = 'learning'
                                 ORDER BY s.name LIMIT 6"
                            );
                            $skillStmt->execute([UKN_DEMO_USER_ID]);
                            $skillNames = $skillStmt->fetchAll(PDO::FETCH_COLUMN);
                            if ($skillNames) {
                                $tagModule = ['type' => 'tag-list', 'title' => 'Main Skills', 'items' => $skillNames];
                                if ($isDualRoleUser) {
                                    $tagModule['role'] = $roleKey;
                                    $tagModule['hidden'] = ($roleKey !== $activeRole);
                                }
                                $profileModules[] = $tagModule;
                            }

                            $goalStmt = $pdo->prepare(
                                "SELECT title, progress FROM learning_goals
                                 WHERE user_id = ? AND status = 'in-progress' ORDER BY target_date ASC LIMIT 1"
                            );
                            $goalStmt->execute([UKN_DEMO_USER_ID]);
                            $goalRows = $goalStmt->fetchAll();
                            if ($goalRows) {
                                $goalModule = [
                                    'type' => 'progress-list',
                                    'title' => 'Current Goals',
                                    'items' => array_map(static function (array $row): array {
                                        return ['label' => $row['title'], 'pct' => (int) $row['progress']];
                                    }, $goalRows),
                                ];
                                if ($isDualRoleUser) {
                                    $goalModule['role'] = $roleKey;
                                    $goalModule['hidden'] = ($roleKey !== $activeRole);
                                }
                                $profileModules[] = $goalModule;
                            }
                        }
                    }
                    return $profileModules;
                }

                case 'home':
                default: {
                    $modules = [];

                    $stmt = $pdo->query(
                        "SELECT s.name, sc.name AS category, COUNT(*) AS c
                         FROM user_skills us JOIN skills s ON s.id = us.skill_id
                         JOIN skill_categories sc ON sc.id = s.category_id
                         WHERE us.skill_type = 'learning' GROUP BY s.id ORDER BY c DESC LIMIT 5"
                    );
                    $topSkills = [];
                    foreach ($stmt->fetchAll() as $i => $row) {
                        $topSkills[] = ['rank' => (string) ($i + 1), 'a' => $row['name'], 'c' => $row['category'], 'b' => ((int) $row['c']) . ' learners'];
                    }
                    if ($topSkills) {
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Top Skills', 'action' => 'Browse All Skills', 'actionHref' => ukn_route_href('skills'), 'items' => $topSkills];
                    }

                    $stmt = $pdo->query(
                        "SELECT u.full_name AS name, u.mentor_points AS points, d.name AS department
                         FROM users u LEFT JOIN departments d ON d.id = u.department_id
                         WHERE u.role IN ('mentor', 'dual') AND u.status = 'active'
                         ORDER BY u.mentor_points DESC LIMIT 3"
                    );
                    $topMentors = [];
                    foreach ($stmt->fetchAll() as $i => $row) {
                        $topMentors[] = ['rank' => (string) ($i + 1), 'a' => $row['name'], 'c' => (string) ($row['department'] ?? ''), 'b' => ((int) $row['points']) . ' pts'];
                    }
                    if ($topMentors) {
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Top Mentors', 'action' => 'Find a Mentor', 'actionHref' => ukn_route_href('find-mentors'), 'items' => $topMentors];
                    }

                    $stmt = $pdo->query(
                        "SELECT u.full_name AS name, u.learning_points AS points, d.name AS department
                         FROM users u LEFT JOIN departments d ON d.id = u.department_id
                         WHERE u.role IN ('learner', 'dual') AND u.status = 'active'
                         ORDER BY u.learning_points DESC LIMIT 3"
                    );
                    $topLearners = [];
                    foreach ($stmt->fetchAll() as $i => $row) {
                        $topLearners[] = ['rank' => (string) ($i + 1), 'a' => $row['name'], 'c' => (string) ($row['department'] ?? ''), 'b' => ((int) $row['points']) . ' pts'];
                    }
                    if ($topLearners) {
                        $modules[] = ['type' => 'ranked-list', 'title' => 'Top Learners', 'items' => $topLearners];
                    }

                    $stmt = $pdo->query(
                        "SELECT id, title, comment_count FROM posts WHERE status = 'visible'
                         ORDER BY comment_count DESC, created_at DESC LIMIT 4"
                    );
                    $trendingPosts = array_map(static function (array $row): array {
                        return [
                            'href' => ukn_route_href('post-details') . '&id=' . $row['id'],
                            'title' => $row['title'],
                            'meta' => ((int) $row['comment_count']) . ' comments',
                        ];
                    }, $stmt->fetchAll());
                    if ($trendingPosts) {
                        $modules[] = ['type' => 'link-list', 'title' => 'Trending Discussions', 'items' => $trendingPosts];
                    }
                    return $modules;
                }
            }
        } catch (Throwable $e) {
            error_log('[UKN right-sidebar] ' . $e->getMessage());
            return [];
        }
    }
}
if (!function_exists('ukn_render_sidebar_module')) {
    function ukn_render_sidebar_module(array $module): void
    {
        $type = $module['type'] ?? 'ranked-list';

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
