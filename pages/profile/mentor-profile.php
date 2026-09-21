<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/rating-item.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';

$requestedId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;
$mentor = false;
$mentorDbError = false;
$dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

try {
    $pdo = getDatabaseConnection();

    $selectBase = "SELECT u.id, u.full_name AS name, u.initials, u.year_of_study AS year, u.headline AS title,
            u.bio, u.avg_rating AS rating, u.mentor_points AS points, u.sessions_as_mentor AS sessions,
            u.learners_helped AS learnersHelped, u.total_reviews AS totalReviews, d.name AS department
        FROM users u
        LEFT JOIN departments d ON d.id = u.department_id
        WHERE u.role IN ('mentor', 'dual') AND u.status = 'active' ";

    $stmt = $pdo->prepare($selectBase . "AND u.id = ?");
    $stmt->execute([$requestedId]);
    $mentor = $stmt->fetch();

    if ($mentor === false) {
        // No matching/active mentor for the requested id: fall back to the lowest-id
        // active mentor, mirroring the page's previous "always show something" mock behaviour.
        $stmt = $pdo->prepare($selectBase . "ORDER BY u.id ASC LIMIT 1");
        $stmt->execute();
        $mentor = $stmt->fetch();
    }

    if ($mentor !== false) {
        $mentorId = (int) $mentor['id'];
        $mentor['year'] = (string) ($mentor['year'] ?? '');
        $mentor['title'] = (string) ($mentor['title'] ?? '');
        $mentor['bio'] = (string) ($mentor['bio'] ?? '');
        $mentor['department'] = (string) ($mentor['department'] ?? '');
        $mentor['points'] = (int) $mentor['points'];
        $mentor['sessions'] = (int) $mentor['sessions'];
        $mentor['learnersHelped'] = (int) $mentor['learnersHelped'];
        $mentor['totalReviews'] = (int) $mentor['totalReviews'];

        $skillsStmt = $pdo->prepare(
            "SELECT s.name, us.proficiency AS level, us.sessions_count AS sessions
             FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id = ? AND us.skill_type = 'teaching'
             ORDER BY us.sessions_count DESC, us.proficiency DESC"
        );
        $skillsStmt->execute([$mentorId]);
        $mentor['skills'] = array_map(static function (array $row): array {
            $row['sessions'] = (int) $row['sessions'];
            return $row;
        }, $skillsStmt->fetchAll());
        $mentor['skillOptions'] = array_column($mentor['skills'], 'name');

        $availabilityStmt = $pdo->prepare(
            "SELECT day_of_week, start_time, end_time FROM mentor_availability
             WHERE user_id = ? AND is_enabled = 1
             ORDER BY day_of_week, start_time"
        );
        $availabilityStmt->execute([$mentorId]);
        $availabilityRows = $availabilityStmt->fetchAll();
        $mentor['availability'] = array_map(static function (array $row) use ($dayNames): array {
            return [
                'day' => $dayNames[(int) $row['day_of_week']] ?? '',
                'time' => date('g:i A', strtotime($row['start_time'])) . ' – ' . date('g:i A', strtotime($row['end_time'])),
            ];
        }, $availabilityRows);
        $mentor['availabilityStatus'] = ukn_availability_bucket(array_map(
            static fn (array $row) => (int) $row['day_of_week'],
            $availabilityRows
        ));

        $breakdownStmt = $pdo->prepare(
            "SELECT AVG(teaching) AS teaching, AVG(communication) AS communication, AVG(helpfulness) AS helpfulness
             FROM session_ratings WHERE mentor_id = ?"
        );
        $breakdownStmt->execute([$mentorId]);
        $breakdownRow = $breakdownStmt->fetch();
        $mentor['ratingBreakdown'] = [];
        if ($breakdownRow && $breakdownRow['teaching'] !== null) {
            $mentor['ratingBreakdown'] = [
                'Teaching Quality' => round((float) $breakdownRow['teaching'], 1),
                'Communication' => round((float) $breakdownRow['communication'], 1),
                'Helpfulness' => round((float) $breakdownRow['helpfulness'], 1),
            ];
        }

        $reviewsStmt = $pdo->prepare(
            "SELECT ur.full_name AS reviewer, ur.initials, sr.overall, sk.name AS skill,
                    sr.created_at, sr.review
             FROM session_ratings sr
             JOIN users ur ON ur.id = sr.reviewer_id
             LEFT JOIN skills sk ON sk.id = sr.skill_id
             WHERE sr.mentor_id = ?
             ORDER BY sr.created_at DESC
             LIMIT 10"
        );
        $reviewsStmt->execute([$mentorId]);
        $mentor['reviews'] = array_map(static function (array $row): array {
            $row['date'] = date('M j', strtotime($row['created_at']));
            return $row;
        }, $reviewsStmt->fetchAll());
    }
} catch (Throwable $e) {
    error_log('[UKN mentor-profile] ' . $e->getMessage());
    $mentorDbError = true;
}

$stars = static function (float $value): string {
    $rounded = (int) round($value);
    return str_repeat('★', max(0, min(5, $rounded))) . str_repeat('☆', 5 - max(0, min(5, $rounded)));
};
$requestMentor = $mentor === false ? [] : [
    'name' => $mentor['name'], 'initials' => $mentor['initials'], 'department' => $mentor['department'],
    'skill' => $mentor['skills'][0]['name'] ?? '', 'rating' => $mentor['rating'], 'skillOptions' => $mentor['skillOptions'],
];
?>
<?php if ($mentorDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load this profile.',
      'message' => 'Something went wrong while loading this mentor profile. Please try again shortly.',
  ]); ?>
<?php elseif ($mentor === false): ?>
  <?php ukn_empty_state([
      'icon' => 'person_off',
      'title' => 'Mentor not found.',
      'message' => 'This profile may no longer be available.',
  ]); ?>
<?php else: ?>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start gap-3 flex-wrap">
      <span class="ukn-avatar ukn-avatar-xl flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($mentor['initials']) ?></span>
      <div class="flex-fill ukn-min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h1 class="ukn-h3 mb-0"><?= htmlspecialchars($mentor['name']) ?></h1>
          <span class="ukn-role-chip">Mentor</span>
        </div>
        <div class="ukn-body-sm mt-1"><?= htmlspecialchars($mentor['department']) ?> &middot; <?= htmlspecialchars($mentor['title']) ?></div>
        <p class="ukn-body-sm mt-2 mb-0"><?= htmlspecialchars($mentor['bio']) ?></p>
        <div class="d-flex align-items-center gap-3 flex-wrap mt-2 ukn-body-sm">
          <span><span class="ukn-stars" aria-hidden="true">★</span> <?= htmlspecialchars((string) $mentor['rating']) ?></span>
          <span><?= (int) $mentor['points'] ?> Mentor Points</span>
          <?php if ($mentor['availabilityStatus']): ?>
            <span class="ukn-status ukn-status-success"><?= htmlspecialchars($mentor['availabilityStatus']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="d-flex flex-column gap-2 flex-shrink-0">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sessionRequestModal">Request Session</button>
      </div>
    </div>
  </div>
</div>
<div class="row g-3 mb-4">
  <?php
  $mentorStats = [
      ['label' => 'Mentor Points', 'value' => (string) $mentor['points'], 'icon' => 'military_tech'],
      ['label' => 'Average Rating', 'value' => (string) $mentor['rating'], 'icon' => 'star'],
      ['label' => 'Completed Sessions', 'value' => (string) $mentor['sessions'], 'icon' => 'event_available'],
      ['label' => 'Learners Helped', 'value' => (string) $mentor['learnersHelped'], 'icon' => 'group'],
  ];
  foreach ($mentorStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0">Teaching Skills</h2>
          <span class="ukn-body-sm">Top skill: <?= htmlspecialchars($mentor['skills'][0]['name'] ?? '') ?></span>
        </div>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($mentor['skills'] as $skill): ?>
            <div class="ukn-row-between">
              <span class="ukn-tag-skill"><?= htmlspecialchars($skill['name']) ?></span>
              <span class="ukn-body-sm"><?= htmlspecialchars($skill['level']) ?> &middot; <?= (int) $skill['sessions'] ?> sessions</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <h2 class="ukn-h4 mb-3">Availability</h2>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($mentor['availability'] as $slot): ?>
            <div class="ukn-row-between">
              <span class="fw-bold"><?= htmlspecialchars($slot['day']) ?></span>
              <span class="ukn-body-sm"><?= htmlspecialchars($slot['time']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="ukn-h4 mb-0">Rating Summary</h2>
      <a href="<?= htmlspecialchars(ukn_route_href('ratings')) ?>" class="ukn-body-sm">View All Ratings</a>
    </div>
    <div class="d-flex align-items-baseline gap-2 mb-3">
      <span class="ukn-display"><?= htmlspecialchars((string) $mentor['rating']) ?></span>
      <span class="ukn-body-sm">/ 5 &middot; <?= (int) $mentor['totalReviews'] ?> reviews</span>
    </div>
    <?php foreach ($mentor['ratingBreakdown'] as $label => $value): ?>
      <div class="ukn-rating-item__breakdown">
        <span><?= htmlspecialchars($label) ?></span>
        <span class="ukn-stars" aria-label="<?= htmlspecialchars((string) $value) ?> out of 5"><?= $stars($value) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Recent Reviews</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('ratings')) ?>" class="ukn-body-sm">View All Ratings</a>
  </div>
  <?php foreach ($mentor['reviews'] as $review): ukn_rating_item($review); endforeach; ?>
</div>
<?php endif; ?>