<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/rating-item.php';
$mentors = [
    2 => [
        'name' => 'Rahim Ahmed', 'initials' => 'RA', 'department' => 'Computer Science', 'year' => '4th Year',
        'title' => 'Python & Data Analysis Mentor',
        'bio' => 'I help students learn Python, database design and practical data analysis through project-based sessions.',
        'rating' => 4.9, 'points' => 520, 'sessions' => 127, 'learnersHelped' => 84,
        'availabilityStatus' => 'Available This Week',
        'skills' => [
            ['name' => 'Python', 'level' => 'Advanced', 'sessions' => 52],
            ['name' => 'Database Design', 'level' => 'Advanced', 'sessions' => 41],
            ['name' => 'Data Analysis', 'level' => 'Intermediate', 'sessions' => 34],
        ],
        'skillOptions' => ['Python', 'Database Design', 'Data Analysis'],
        'availability' => [
            ['day' => 'Wednesday', 'time' => '7:00 PM – 9:00 PM'],
            ['day' => 'Saturday', 'time' => '6:00 PM – 9:00 PM'],
            ['day' => 'Sunday', 'time' => '5:00 PM – 8:00 PM'],
        ],
        'ratingBreakdown' => ['Teaching Quality' => 4.9, 'Communication' => 4.8, 'Helpfulness' => 4.9],
        'totalReviews' => 42,
        'reviews' => [
            ['reviewer' => 'Nabila Rahman', 'initials' => 'NR', 'overall' => 5, 'skill' => 'Python', 'date' => 'Sep 10', 'review' => 'Rahim explained normalization clearly and used examples that were easy to follow.'],
            ['reviewer' => 'Tanvir Hossain', 'initials' => 'TH', 'overall' => 5, 'skill' => 'Python', 'date' => 'Sep 3', 'review' => 'Patient with beginner questions and always ties concepts back to an actual assignment.'],
            ['reviewer' => 'Sara Khan', 'initials' => 'SK', 'overall' => 4, 'skill' => 'Data Analysis', 'date' => 'Aug 22', 'review' => 'Solid session on pandas groupby — would have liked a bit more time on edge cases.'],
        ],
    ],
    3 => [
        'name' => 'Hasan Mahmud', 'initials' => 'HM', 'department' => 'Electrical Engineering', 'year' => '4th Year',
        'title' => 'Arduino & Embedded Systems Mentor',
        'bio' => 'I mentor students building their first Arduino projects, focusing on wiring, debouncing and practical debugging over pure theory.',
        'rating' => 4.7, 'points' => 365, 'sessions' => 52, 'learnersHelped' => 38,
        'availabilityStatus' => 'Available Next Week',
        'skills' => [
            ['name' => 'Arduino', 'level' => 'Advanced', 'sessions' => 33],
            ['name' => 'Embedded Systems', 'level' => 'Intermediate', 'sessions' => 19],
        ],
        'skillOptions' => ['Arduino', 'Embedded Systems'],
        'availability' => [
            ['day' => 'Tuesday', 'time' => '6:00 PM – 8:00 PM'],
            ['day' => 'Thursday', 'time' => '6:00 PM – 8:00 PM'],
        ],
        'ratingBreakdown' => ['Teaching Quality' => 4.7, 'Communication' => 4.6, 'Helpfulness' => 4.8],
        'totalReviews' => 21,
        'reviews' => [
            ['reviewer' => 'Farhana Islam', 'initials' => 'FI', 'overall' => 5, 'skill' => 'Arduino', 'date' => 'Sep 5', 'review' => 'Fixed my debounce circuit in ten minutes with a wiring diagram I actually understood.'],
            ['reviewer' => 'Mahi Noor', 'initials' => 'MN', 'overall' => 4, 'skill' => 'Embedded Systems', 'date' => 'Aug 18', 'review' => 'Good practical session, a little fast-paced for a first-timer.'],
        ],
    ],
];
$requestedId = isset($_GET['id']) && is_string($_GET['id']) && isset($mentors[(int) $_GET['id']]) ? (int) $_GET['id'] : 2;
$mentor = $mentors[$requestedId];
$stars = static function (float $value): string {
    $rounded = (int) round($value);
    return str_repeat('★', max(0, min(5, $rounded))) . str_repeat('☆', 5 - max(0, min(5, $rounded)));
};
$requestMentor = [
    'name' => $mentor['name'], 'initials' => $mentor['initials'], 'department' => $mentor['department'],
    'skill' => $mentor['skills'][0]['name'] ?? '', 'rating' => $mentor['rating'], 'skillOptions' => $mentor['skillOptions'],
];
?>
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
          <span class="ukn-status ukn-status-success"><?= htmlspecialchars($mentor['availabilityStatus']) ?></span>
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