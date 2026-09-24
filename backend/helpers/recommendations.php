<?php
// Step 28 — mentor recommendations for the logged-in learner.
//
// Score (0–100) = weighted average of the factors that have real data:
//
//   Skill Match   40  100 × (1/N) × Σ over the learner's N needed skills of q(p), where the
//                     mentor teaches that skill with proficiency p; q = Beginner 1/3,
//                     Intermediate 2/3, Advanced 1 (ordinal rank / 3), unspecified = 1/3.
//                     Needed skills = learning skills ∪ skills of in-progress learning goals.
//   Availability  20  100 × (distinct weekdays with an enabled slot) / 7.
//   Rating        20  100 × (AVG(session_ratings.overall) − 1) / 4 — only for mentors that
//                     have at least one rating; otherwise this factor is left out for them.
//   Experience    10  100 × completed sessions / most completed sessions among candidates —
//                     left out for everyone while no candidate has completed a session.
//   Mentor Points 10  100 × max(0, mentor-ledger points) / highest among candidates —
//                     left out for everyone while no candidate has positive points.
//
//   Final = Σ(weight × factor) / Σ(weight of factors used for that mentor)
//
// The users.avg_rating / sessions_as_mentor / mentor_points columns are hand-seeded display
// values that nothing keeps in sync, so rating, experience and points are computed from the
// source tables (session_ratings, mentoring_sessions, point_transactions) instead.
require_once __DIR__ . '/../config/database.php';

if (!defined('UKN_RECOMMENDATION_WEIGHTS')) {
    define('UKN_RECOMMENDATION_WEIGHTS', [
        'skill' => 40, 'availability' => 20, 'rating' => 20, 'experience' => 10, 'points' => 10,
    ]);
}
if (!defined('UKN_PROFICIENCY_QUALITY')) {
    define('UKN_PROFICIENCY_QUALITY', ['Beginner' => 1 / 3, 'Intermediate' => 2 / 3, 'Advanced' => 1.0]);
}

if (!function_exists('uknScoreMentor')) {
    /**
     * Pure scoring for one mentor. $factors maps factor => 0..100 score, or null when the
     * factor has no real data for this mentor (it is then excluded and the rest renormalised).
     */
    function uknScoreMentor(array $factors): float
    {
        $weighted = 0.0;
        $totalWeight = 0;
        foreach (UKN_RECOMMENDATION_WEIGHTS as $factor => $weight) {
            if (!isset($factors[$factor])) {
                continue;
            }
            $weighted += $weight * $factors[$factor];
            $totalWeight += $weight;
        }
        return $totalWeight > 0 ? max(0.0, min(100.0, $weighted / $totalWeight)) : 0.0;
    }
}

if (!function_exists('uknRecommendMentors')) {
    /**
     * Ranked mentor recommendations for $learnerId (always the session user; never taken from
     * the request). Returns ['needs' => [skillId => ['name', 'goals' => [...], 'learning' => bool]],
     * 'mentors' => [...ranked...]]. Mentors that match none of the needed skills are not included.
     */
    function uknRecommendMentors(int $learnerId): array
    {
        $pdo = getDatabaseConnection();

        // 1. What the learner needs: learning skills + skills of in-progress goals.
        $stmt = $pdo->prepare(
            "SELECT us.skill_id, s.name, NULL AS goal_title
             FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id = ? AND us.skill_type = 'learning' AND s.status = 'active'
             UNION ALL
             SELECT lg.skill_id, s.name, lg.title
             FROM learning_goals lg JOIN skills s ON s.id = lg.skill_id
             WHERE lg.user_id = ? AND lg.status = 'in-progress' AND s.status = 'active'"
        );
        $stmt->execute([$learnerId, $learnerId]);
        $needs = [];
        foreach ($stmt->fetchAll() as $row) {
            $skillId = (int) $row['skill_id'];
            $needs[$skillId] ??= ['name' => $row['name'], 'learning' => false, 'goals' => []];
            if ($row['goal_title'] === null) {
                $needs[$skillId]['learning'] = true;
            } else {
                $needs[$skillId]['goals'][] = $row['goal_title'];
            }
        }
        if ($needs === []) {
            return ['needs' => [], 'mentors' => []];
        }

        // 2. Eligible mentors teaching at least one needed skill, with those matched skills.
        $skillIds = array_keys($needs);
        $in = implode(',', array_fill(0, count($skillIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT u.id, u.full_name, u.initials, d.name AS department, us.skill_id, us.proficiency
             FROM users u
             JOIN user_skills us ON us.user_id = u.id AND us.skill_type = 'teaching'
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.role IN ('mentor', 'dual') AND u.status = 'active'
               AND u.email_verified_at IS NOT NULL AND u.id <> ?
               AND us.skill_id IN ($in)"
        );
        $stmt->execute(array_merge([$learnerId], $skillIds));
        $mentors = [];
        foreach ($stmt->fetchAll() as $row) {
            $id = (int) $row['id'];
            $mentors[$id] ??= [
                'id' => $id, 'name' => $row['full_name'], 'initials' => $row['initials'],
                'department' => (string) ($row['department'] ?? ''), 'matched' => [],
            ];
            $mentors[$id]['matched'][(int) $row['skill_id']] = $row['proficiency'];
        }
        if ($mentors === []) {
            return ['needs' => $needs, 'mentors' => []];
        }

        // 3–6. Per-candidate facts, one grouped query each (no per-mentor queries).
        $ids = array_keys($mentors);
        $inIds = implode(',', array_fill(0, count($ids), '?'));
        $grouped = static function (string $sql) use ($pdo, $ids): array {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            $out = [];
            foreach ($stmt->fetchAll() as $row) {
                $out[(int) $row['k']] = $row;
            }
            return $out;
        };
        $teaching = [];
        $stmt = $pdo->prepare(
            "SELECT us.user_id, s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.skill_type = 'teaching' AND s.status = 'active' AND us.user_id IN ($inIds)
             ORDER BY us.user_id, s.name"
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $row) {
            $teaching[(int) $row['user_id']][] = $row['name'];
        }
        $availability = $grouped(
            "SELECT user_id AS k, COUNT(DISTINCT day_of_week) AS days,
                    SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))) AS seconds
             FROM mentor_availability WHERE is_enabled = 1 AND user_id IN ($inIds) GROUP BY user_id"
        );
        $ratings = $grouped(
            "SELECT mentor_id AS k, AVG(overall) AS avg_overall, COUNT(*) AS n
             FROM session_ratings WHERE mentor_id IN ($inIds) GROUP BY mentor_id"
        );
        $completed = $grouped(
            "SELECT mentor_id AS k, COUNT(*) AS n
             FROM mentoring_sessions WHERE status = 'completed' AND mentor_id IN ($inIds) GROUP BY mentor_id"
        );
        $points = $grouped(
            "SELECT user_id AS k, SUM(amount) AS total
             FROM point_transactions WHERE point_type = 'mentor' AND user_id IN ($inIds) GROUP BY user_id"
        );
        $maxCompleted = 0;
        $maxPoints = 0;
        foreach ($ids as $id) {
            $maxCompleted = max($maxCompleted, (int) ($completed[$id]['n'] ?? 0));
            $maxPoints = max($maxPoints, (int) ($points[$id]['total'] ?? 0));
        }

        // 7. Score and explain.
        $neededCount = count($needs);
        foreach ($mentors as $id => &$mentor) {
            $quality = 0.0;
            $matchedSkills = [];
            foreach ($mentor['matched'] as $skillId => $proficiency) {
                $quality += UKN_PROFICIENCY_QUALITY[$proficiency] ?? UKN_PROFICIENCY_QUALITY['Beginner'];
                $matchedSkills[] = ['name' => $needs[$skillId]['name'], 'proficiency' => $proficiency, 'goals' => $needs[$skillId]['goals']];
            }
            usort($matchedSkills, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
            $days = (int) ($availability[$id]['days'] ?? 0);
            $hours = round(((int) ($availability[$id]['seconds'] ?? 0)) / 3600, 1);
            $ratingCount = (int) ($ratings[$id]['n'] ?? 0);
            $ratingAvg = $ratingCount > 0 ? round((float) $ratings[$id]['avg_overall'], 1) : null;
            $sessionsDone = (int) ($completed[$id]['n'] ?? 0);
            $mentorPoints = (int) ($points[$id]['total'] ?? 0);

            $factors = [
                'skill' => 100 * $quality / $neededCount,
                'availability' => 100 * $days / 7,
                'rating' => $ratingCount > 0 ? 100 * ((float) $ratings[$id]['avg_overall'] - 1) / 4 : null,
                'experience' => $maxCompleted > 0 ? 100 * $sessionsDone / $maxCompleted : null,
                'points' => $maxPoints > 0 ? 100 * max(0, $mentorPoints) / $maxPoints : null,
            ];
            $mentor += [
                'score' => uknScoreMentor($factors),
                'factors' => $factors,
                'matchedSkills' => $matchedSkills,
                'teachingSkills' => $teaching[$id] ?? [],
                'availabilityDays' => $days,
                'availabilityHours' => $hours,
                'ratingAvg' => $ratingAvg,
                'ratingCount' => $ratingCount,
                'sessionsCompleted' => $sessionsDone,
                'mentorPoints' => $mentorPoints,
                'neededCount' => $neededCount,
            ];
        }
        unset($mentor);

        // Highest score first; ties by skill match, then name, then id (fully deterministic).
        $ranked = array_values($mentors);
        usort($ranked, static function (array $a, array $b): int {
            return [round($b['score'], 6), round($b['factors']['skill'], 6), $a['name'], $a['id']]
                <=> [round($a['score'], 6), round($a['factors']['skill'], 6), $b['name'], $b['id']];
        });
        return ['needs' => $needs, 'mentors' => $ranked];
    }
}
