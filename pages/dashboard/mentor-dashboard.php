<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/session-card.php';
require_once __DIR__ . '/../../components/learner-card.php';
$mentorStats = [
    ['label' => 'Mentor Points', 'value' => '520', 'icon' => 'military_tech', 'trend' => '+20 this month'],
    ['label' => 'Average Rating', 'value' => '4.8', 'icon' => 'star'],
    ['label' => 'Pending Requests', 'value' => '5', 'icon' => 'inbox'],
    ['label' => 'Completed Sessions', 'value' => '27', 'icon' => 'event_available'],
];
$pendingRequests = [
    [
        'counterparty' => 'Nabila Rahman', 'counterpartyInitials' => 'NR', 'skill' => 'Python',
        'day' => '17', 'month' => 'Sep', 'time' => 'Thu 8:00pm', 'duration' => '60 min',
        'status' => 'pending', 'message' => 'Could we cover pandas groupby before my coursework deadline?',
    ],
    [
        'counterparty' => 'Tanvir Hossain', 'counterpartyInitials' => 'TH', 'skill' => 'Python',
        'day' => '19', 'month' => 'Sep', 'time' => 'Sat 3:00pm', 'duration' => '45 min',
        'status' => 'pending', 'message' => 'Looking for help debugging a Flask project before submission.',
    ],
];
$mentorSessions = [
    [
        'counterparty' => 'Imran Chowdhury', 'counterpartyInitials' => 'IC', 'skill' => 'Python',
        'day' => '18', 'month' => 'Sep', 'time' => 'Fri 6:30pm', 'duration' => '60 min',
        'status' => 'upcoming', 'detailsHref' => ukn_route_href('session-details'),
    ],
];
$recentLearners = [
    [
        'name' => 'Nabila Rahman', 'initials' => 'NR', 'department' => 'Computer Science',
        'skills' => ['Python'], 'points' => 412, 'sessions' => 4, 'profileHref' => ukn_route_href('learner-profile'),
    ],
    [
        'name' => 'Imran Chowdhury', 'initials' => 'IC', 'department' => 'English',
        'skills' => ['Presentation Skills'], 'points' => 318, 'sessions' => 2, 'profileHref' => ukn_route_href('learner-profile'),
    ],
];
$teachingSkills = [
    ['name' => 'Python', 'sessions' => 27],
    ['name' => 'Database Design', 'sessions' => 18],
    ['name' => 'Data Analysis', 'sessions' => 12],
];
$availability = [
    ['day' => 'Wednesday', 'time' => '7:00 PM – 9:00 PM'],
    ['day' => 'Saturday', 'time' => '6:00 PM – 9:00 PM'],
    ['day' => 'Sunday', 'time' => '5:00 PM – 8:00 PM'],
];
$ratingSummary = ['overall' => 4.8, 'total' => 42];
$ratingBreakdown = ['Teaching Quality' => 4.9, 'Communication' => 4.7, 'Helpfulness' => 4.8];
$stars = static function (float $value): string {
    $rounded = (int) round($value);
    return str_repeat('★', max(0, min(5, $rounded))) . str_repeat('☆', 5 - max(0, min(5, $rounded)));
};
$sessionsChartLabels = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
$sessionsChartValues = [5, 7, 6, 9, 11, 12];
?>
<div class="ukn-page-header">
  <div>
    <h1>Mentor Dashboard</h1>
    <p class="ukn-page-header__sub">Manage learner requests, sessions and your mentoring progress.</p>
  </div>
</div>
<div class="row g-3 mb-4">
  <?php foreach ($mentorStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <h2 class="ukn-h4 mb-0">Sessions Completed</h2>
          <span class="ukn-body-sm">Monthly trend</span>
        </div>
        <div class="ukn-chart-wrap">
          <canvas
            data-chart="line"
            data-chart-label="Sessions"
            data-chart-labels="<?= htmlspecialchars(json_encode($sessionsChartLabels)) ?>"
            data-chart-values="<?= htmlspecialchars(json_encode($sessionsChartValues)) ?>"
            role="img"
            aria-label="Sessions completed per month: Apr 5, May 7, Jun 6, Jul 9, Aug 11, Sep 12"
          ></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0">Rating Summary</h2>
          <a href="<?= htmlspecialchars(ukn_route_href('ratings')) ?>" class="ukn-body-sm">View All Ratings</a>
        </div>
        <div class="d-flex align-items-baseline gap-2 mb-3">
          <span class="ukn-display"><?= htmlspecialchars((string) $ratingSummary['overall']) ?></span>
          <span class="ukn-body-sm">/ 5 &middot; <?= (int) $ratingSummary['total'] ?> reviews</span>
        </div>
        <?php foreach ($ratingBreakdown as $label => $value): ?>
          <div class="ukn-rating-item__breakdown">
            <span><?= htmlspecialchars($label) ?></span>
            <span class="ukn-stars" aria-label="<?= htmlspecialchars((string) $value) ?> out of 5"><?= $stars($value) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Pending Learner Requests</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('learner-requests')) ?>" class="ukn-body-sm">View All Requests</a>
  </div>
  <?php foreach ($pendingRequests as $session): ukn_session_card($session); endforeach; ?>
</div>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Upcoming Sessions</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('sessions')) ?>" class="ukn-body-sm">View All Sessions</a>
  </div>
  <?php foreach ($mentorSessions as $session): ukn_session_card($session); endforeach; ?>
</div>
<div class="mb-4">
  <h2 class="ukn-h4 mb-3">Recent Learners</h2>
  <div class="row g-3">
    <?php foreach ($recentLearners as $learner): ?>
      <div class="col-md-6"><?php ukn_learner_card($learner); ?></div>
    <?php endforeach; ?>
  </div>
</div>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0">Teaching Skills</h2>
          <a href="<?= htmlspecialchars(ukn_route_href('teaching-skills')) ?>" class="ukn-body-sm">Manage</a>
        </div>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($teachingSkills as $skill): ?>
            <div class="ukn-row-between">
              <span class="ukn-tag-skill"><?= htmlspecialchars($skill['name']) ?></span>
              <span class="ukn-body-sm"><?= (int) $skill['sessions'] ?> sessions</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0">Availability</h2>
          <a href="<?= htmlspecialchars(ukn_route_href('availability')) ?>" class="ukn-body-sm">Manage</a>
        </div>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($availability as $slot): ?>
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