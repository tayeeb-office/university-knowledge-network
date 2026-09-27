<?php
// Learner recommendations: members' written recommendations FOR a learner, shown on the
// learner's profile. Not mentor matching (see backend/helpers/recommendations.php).
require_once __DIR__ . '/../config/database.php';

if (!defined('UKN_RECOMMENDATION_MAX')) {
    define('UKN_RECOMMENDATION_MAX', 1000);   // learner_recommendations.content is VARCHAR(1000)
}

if (!function_exists('uknLearnerRecommendations')) {
    /**
     * Recommendations written for $learnerId, newest first, with each recommender's public
     * fields (name, initials, avatar_path, department, year) in the same query. Recommenders
     * whose account is no longer active are left out.
     */
    function uknLearnerRecommendations(PDO $pdo, int $learnerId): array
    {
        $stmt = $pdo->prepare(
            "SELECT lr.id, lr.recommender_id, lr.content, lr.created_at,
                    u.full_name AS name, u.initials, u.avatar_path, u.year_of_study AS year,
                    d.name AS department
             FROM learner_recommendations lr
             JOIN users u ON u.id = lr.recommender_id AND u.status = 'active'
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE lr.learner_id = ?
             ORDER BY lr.created_at DESC, lr.id DESC"
        );
        $stmt->execute([$learnerId]);
        return $stmt->fetchAll();
    }
}
