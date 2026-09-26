<?php
// Phase G (Step 43): leaderboard rankings computed from the point_transactions ledger.
// users.learning_points / mentor_points are only incrementally maintained since Phase E and
// their seed values were hand-set (they do not match the ledger), so rankings, standings and
// period views all aggregate the ledger itself.
require_once __DIR__ . '/../config/database.php';

if (!function_exists('uknLeaderboardPeriods')) {
    function uknLeaderboardPeriods(): array
    {
        return ['week' => 'This Week', 'month' => 'This Month', 'all' => 'All Time'];
    }
}
if (!function_exists('uknLeaderboardPeriodFromRequest')) {
    /** Whitelisted ?period= value; anything else (missing, unknown, array) is 'all'. */
    function uknLeaderboardPeriodFromRequest(): string
    {
        $period = $_GET['period'] ?? 'all';
        return is_string($period) && isset(uknLeaderboardPeriods()[$period]) ? $period : 'all';
    }
}
if (!function_exists('uknLeaderboardBaseSql')) {
    /**
     * FROM/WHERE shared by rankings and standings. Periods use calendar boundaries computed by
     * the database (same clock as created_at): week = since Monday 00:00, month = since the 1st.
     * The fragments are fixed strings picked from a whitelist; nothing user-supplied is inlined.
     * $displayJoins adds LEFT JOINs for display-only columns without changing the filters.
     */
    function uknLeaderboardBaseSql(string $pointType, string $period, string $displayJoins = ''): string
    {
        $roleFilter = [
            'learning'  => "AND u.role IN ('learner', 'dual')",
            'mentor'    => "AND u.role = 'dual'",
            'community' => '',
        ][$pointType];
        $periodFilter = [
            'week'  => 'AND pt.created_at >= CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY',
            'month' => "AND pt.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
            'all'   => '',
        ][$period];
        return "FROM point_transactions pt
                JOIN users u ON u.id = pt.user_id
                {$displayJoins}
                WHERE pt.point_type = '{$pointType}' {$periodFilter}
                  AND u.status = 'active' AND u.is_admin = 0 {$roleFilter}";
    }
}
if (!function_exists('uknLeaderboardRows')) {
    /**
     * Top members for one point type and period, one aggregate query. Only positive net totals
     * are ranked. Ties are broken by name, then id, so the order is deterministic.
     * 'activity' = distinct sessions that earned points (learning/mentor) or ledger entries (community).
     */
    function uknLeaderboardRows(PDO $pdo, string $pointType, string $period, int $limit = 20): array
    {
        if (!in_array($pointType, ['learning', 'mentor', 'community'], true) || !isset(uknLeaderboardPeriods()[$period])) {
            return [];
        }
        $activity = $pointType === 'community'
            ? 'COUNT(*)'
            : "COUNT(DISTINCT CASE WHEN pt.category = 'session' AND pt.amount > 0 THEN pt.related_session_id END)";
        // Mentor rating from the reusable mentor_rating_summary VIEW (per-mentor AVG/COUNT over session_ratings).
        $ratingSelect = $pointType === 'mentor' ? ', r.avg_rating AS rating' : '';
        $ratingJoin = $pointType === 'mentor'
            ? 'LEFT JOIN mentor_rating_summary r ON r.mentor_id = u.id'
            : '';
        $ratingGroup = $pointType === 'mentor' ? ', r.avg_rating' : '';
        $base = uknLeaderboardBaseSql($pointType, $period, "LEFT JOIN departments d ON d.id = u.department_id {$ratingJoin}");
        $stmt = $pdo->prepare(
            "SELECT u.id, u.full_name AS name, u.initials, u.avatar_path, u.role, d.name AS category,
                    SUM(pt.amount) AS points, {$activity} AS activity{$ratingSelect}
             {$base}
             GROUP BY u.id, u.full_name, u.initials, u.avatar_path, u.role, d.name{$ratingGroup}
             HAVING points > 0
             ORDER BY points DESC, u.full_name ASC, u.id ASC
             LIMIT " . max(1, min(100, $limit))
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
if (!function_exists('uknLeaderboardStanding')) {
    /**
     * The given member's points and rank for one point type and period, using the same
     * filters and tie-break as uknLeaderboardRows(). rank is null when the member has no
     * positive total (or is not eligible for this board). One query.
     */
    function uknLeaderboardStanding(PDO $pdo, int $userId, string $pointType, string $period): array
    {
        $standing = ['points' => 0, 'rank' => null];
        if ($userId <= 0 || !in_array($pointType, ['learning', 'mentor', 'community'], true) || !isset(uknLeaderboardPeriods()[$period])) {
            return $standing;
        }
        $base = uknLeaderboardBaseSql($pointType, $period);
        $stmt = $pdo->prepare(
            "SELECT me.total AS points,
                    (SELECT COUNT(*) FROM (SELECT u.id, u.full_name, SUM(pt.amount) AS total {$base}
                                           GROUP BY u.id, u.full_name HAVING total > 0) t
                     WHERE t.total > me.total
                        OR (t.total = me.total AND (t.full_name < me.full_name
                            OR (t.full_name = me.full_name AND t.id < me.id)))) + 1 AS `rank`
             FROM (SELECT u.id, u.full_name, SUM(pt.amount) AS total {$base} AND u.id = ?
                   GROUP BY u.id, u.full_name) me"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row !== false) {
            $standing['points'] = (int) $row['points'];
            $standing['rank'] = $standing['points'] > 0 ? (int) $row['rank'] : null;
        }
        return $standing;
    }
}
