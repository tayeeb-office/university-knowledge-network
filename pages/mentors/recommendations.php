<?php
require_once __DIR__ . '/../../components/mentor-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/helpers/recommendations.php';
require_once __DIR__ . '/../../backend/helpers/community.php';

// Ranked for the session user only (route is learner-guarded in index.php); the scoring is
// documented in backend/helpers/recommendations.php.
$recommendation = ['needs' => [], 'mentors' => []];
$recommendationsDbError = false;
$followStates = [];
try {
    $recommendation = uknRecommendMentors(UKN_CURRENT_USER_ID);
    foreach ($recommendation['mentors'] as $mentor) {
        $followStates[(int) $mentor['id']] = uknFollowStateFor((int) $mentor['id']);
    }
} catch (Throwable $e) {
    error_log('[UKN recommendations] ' . $e->getMessage());
    $recommendationsDbError = true;
}

$plural = static fn (int $n, string $one, string $many): string => $n . ' ' . ($n === 1 ? $one : $many);
$recommendedMentors = [];
foreach ($recommendation['mentors'] as $rank => $mentor) {
    $matchedNames = array_column($mentor['matchedSkills'], 'name');
    $reasons = [
        'Matches ' . count($matchedNames) . ' of the ' . $plural($mentor['neededCount'], 'skill', 'skills')
            . " you're learning or working toward",
    ];
    foreach ($mentor['matchedSkills'] as $skill) {
        $reasons[] = $skill['name'] . ' — ' . ($skill['proficiency'] ?? 'level not set')
            . ($skill['goals'] ? ' (goal: ' . implode(', ', $skill['goals']) . ')' : '');
    }
    $reasons[] = $mentor['availabilityDays'] > 0
        ? 'Available ' . $plural($mentor['availabilityDays'], 'day', 'days') . '/week (' . $mentor['availabilityHours'] . ' hours)'
        : 'No weekly availability set yet';
    if ($mentor['ratingCount'] > 0) {
        $reasons[] = 'Rated ' . $mentor['ratingAvg'] . '/5 from ' . $plural($mentor['ratingCount'], 'review', 'reviews');
    }
    if ($mentor['sessionsCompleted'] > 0) {
        $reasons[] = $plural($mentor['sessionsCompleted'], 'completed mentoring session', 'completed mentoring sessions');
    }
    $recommendedMentors[] = [
        'id' => $mentor['id'],
        'name' => $mentor['name'],
        'initials' => $mentor['initials'],
        'avatar_path' => $mentor['avatar_path'] ?? null,
        'department' => $mentor['department'],
        'primarySkill' => $matchedNames[0],
        'otherSkills' => array_values(array_diff($mentor['teachingSkills'], [$matchedNames[0]])),
        'skillOptions' => $mentor['teachingSkills'],
        'rating' => $mentor['ratingAvg'],
        'sessions' => $mentor['sessionsCompleted'],
        'points' => $mentor['mentorPoints'],
        'availability' => $mentor['availabilityDays'] > 0 ? 'Available ' . $plural($mentor['availabilityDays'], 'day', 'days') . '/week' : '',
        'profileHref' => ukn_route_href('mentor-profile') . '&id=' . $mentor['id'],
        'match' => (int) round($mentor['score']),
        'score' => $mentor['score'],
        'matchLabel' => $rank === 0 ? 'Best Match' : null,
        'reasons' => $reasons,
        'following' => $followStates[(int) $mentor['id']] ?? null,
    ];
}
$topMentor = $recommendedMentors ? array_shift($recommendedMentors) : null;
?>
<div class="ukn-page-header">
  <div>
    <h1>Recommended Mentors</h1>
    <p class="ukn-page-header__sub">Mentors ranked by how well they match the skills you're learning.</p>
  </div>
</div>
<?php if ($recommendationsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load recommendations.',
      'message' => 'Something went wrong while loading recommended mentors. Please try again shortly.',
  ]); ?>
<?php elseif ($recommendation['needs'] === []): ?>
  <?php ukn_empty_state([
      'icon' => 'menu_book',
      'title' => 'Add a learning skill to get recommendations.',
      'message' => 'Recommendations are based on the skills you are learning and your learning goals.',
      'action' => ['label' => 'Explore Skills', 'href' => ukn_route_href('skills')],
  ]); ?>
<?php elseif ($topMentor === null): ?>
  <?php ukn_empty_state([
      'icon' => 'person_search',
      'title' => 'No mentor teaches your skills yet.',
      'message' => 'None of the available mentors currently teaches ' . implode(', ', array_column($recommendation['needs'], 'name')) . '.',
      'action' => ['label' => 'Browse All Mentors', 'href' => ukn_route_href('find-mentors')],
  ]); ?>
<?php else: ?>
<div class="row g-3">
  <div class="col-12"><?php ukn_mentor_card($topMentor, ['variant' => 'recommendation']); ?></div>
  <?php foreach ($recommendedMentors as $mentor): ?>
    <div class="col-md-6"><?php ukn_mentor_card($mentor, ['variant' => 'recommendation']); ?></div>
  <?php endforeach; ?>
</div>
<p class="ukn-body-sm mt-2">
  Match score: skill match 40 · availability 20 · rating 20 · experience 10 · mentor points 10.
  Factors without data yet (e.g. no ratings) are left out and the rest re-weighted.
</p>
<?php endif; ?>
