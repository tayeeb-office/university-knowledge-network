<?php
require_once __DIR__ . '/../../components/mentor-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';

// NOTE: there is no recommendation/scoring engine or "match %" column anywhere in the
// schema, and this task intentionally does not invent one (see
// DATABASE_READ_INTEGRATION_PLAN.md §2.4). This shows the top-rated available mentors
// as a simple, defensible stand-in; 'match'/'matchLabel' are left null, which the
// mentor card component already renders as "no match badge".
$recommendedMentors = [];
$recommendationsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $mentorStmt = $pdo->query(
        "SELECT DISTINCT u.id, u.full_name AS name, u.initials, u.avg_rating AS rating,
                u.mentor_points AS points, u.sessions_as_mentor AS sessions, d.name AS department
         FROM users u
         JOIN user_skills us ON us.user_id = u.id AND us.skill_type = 'teaching'
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.role IN ('mentor', 'dual') AND u.status = 'active'
         ORDER BY u.avg_rating DESC, u.sessions_as_mentor DESC
         LIMIT 4"
    );
    $recommendedMentors = $mentorStmt->fetchAll();

    if ($recommendedMentors) {
        $mentorIds = array_column($recommendedMentors, 'id');
        $placeholders = implode(',', array_fill(0, count($mentorIds), '?'));

        $skillsStmt = $pdo->prepare(
            "SELECT us.user_id, s.name
             FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id IN ($placeholders) AND us.skill_type = 'teaching'
             ORDER BY us.user_id, us.sessions_count DESC, us.proficiency DESC"
        );
        $skillsStmt->execute($mentorIds);
        $skillsByMentor = [];
        foreach ($skillsStmt->fetchAll() as $row) {
            $skillsByMentor[$row['user_id']][] = $row['name'];
        }

        $availabilityStmt = $pdo->prepare(
            "SELECT user_id, day_of_week FROM mentor_availability
             WHERE user_id IN ($placeholders) AND is_enabled = 1"
        );
        $availabilityStmt->execute($mentorIds);
        $daysByMentor = [];
        foreach ($availabilityStmt->fetchAll() as $row) {
            $daysByMentor[$row['user_id']][] = (int) $row['day_of_week'];
        }

        foreach ($recommendedMentors as &$mentor) {
            $skillNames = $skillsByMentor[$mentor['id']] ?? [];
            $mentor['primarySkill'] = $skillNames[0] ?? '';
            $mentor['otherSkills'] = array_slice($skillNames, 1);
            $mentor['availability'] = ukn_availability_bucket($daysByMentor[$mentor['id']] ?? []);
            $mentor['profileHref'] = ukn_route_href('mentor-profile') . '&id=' . $mentor['id'];
            $mentor['match'] = null;
            $mentor['matchLabel'] = null;
        }
        unset($mentor);
    }
} catch (Throwable $e) {
    error_log('[UKN recommendations] ' . $e->getMessage());
    $recommendationsDbError = true;
}

$topMentor = $recommendedMentors ? array_shift($recommendedMentors) : null;
?>
<div class="ukn-page-header">
  <div>
    <h1>Recommended Mentors</h1>
    <p class="ukn-page-header__sub">Top-rated available mentors on the network.</p>
  </div>
</div>
<?php if ($recommendationsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load recommendations.',
      'message' => 'Something went wrong while loading recommended mentors. Please try again shortly.',
  ]); ?>
<?php elseif ($topMentor === null): ?>
  <?php ukn_empty_state([
      'icon' => 'person_search',
      'title' => 'No mentors available yet.',
      'message' => 'Check back once mentors have added their teaching skills.',
  ]); ?>
<?php else: ?>
<div class="row g-3">
  <div class="col-12"><?php ukn_mentor_card($topMentor, ['variant' => 'recommendation']); ?></div>
  <?php foreach ($recommendedMentors as $mentor): ?>
    <div class="col-md-6"><?php ukn_mentor_card($mentor, ['variant' => 'recommendation']); ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>