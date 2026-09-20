<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/point-transaction.php';
require_once __DIR__ . '/../../components/empty-state.php';
$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';
$heading = $isMentor ? 'Mentor Points' : 'Points';
$subheading = $isMentor
    ? 'Track the points you earn through mentoring and teaching activity.'
    : 'Track the points you earn through learning and community activity.';
$pointStats = [
    'learning' => ['label' => 'Learning Points', 'value' => '412', 'icon' => 'military_tech', 'trend' => '+64 this month'],
    'mentor'   => ['label' => 'Mentor Points', 'value' => '520', 'icon' => 'military_tech', 'trend' => '+48 this month'],
    'total'    => ['label' => 'Total Points', 'value' => '932', 'icon' => 'military_tech', 'trend' => 'Rank 4 of 1,284'],
    'growth'   => ['label' => 'Point Growth', 'value' => '+13%', 'icon' => 'trending_up', 'trend' => 'vs. August'],
];
$statOrder = $isMentor ? ['mentor', 'learning', 'total', 'growth'] : ['learning', 'mentor', 'total', 'growth'];
$howYouEarn = $isMentor
    ? ['Complete mentoring sessions', 'Receive positive ratings from learners', 'Help learners reach their learning goals']
    : ['Complete learning sessions', 'Complete learning goals', 'Participate helpfully in the community'];
$filterOptions = $isMentor
    ? ['all' => 'All', 'session' => 'Sessions', 'rating' => 'Ratings']
    : ['all' => 'All', 'session' => 'Sessions', 'goal' => 'Goals', 'community' => 'Community'];
$learnerTransactions = [
    ['amount' => 10, 'type' => 'Learning Points', 'reason' => 'Completed Python Session', 'session' => 'Rahim Ahmed', 'date' => 'Sep 12, 2026', 'icon' => 'event_available', 'category' => 'session'],
    ['amount' => 5, 'type' => 'Learning Points', 'reason' => 'Completed Learning Goal', 'session' => 'MySQL', 'date' => 'Sep 10, 2026', 'icon' => 'flag', 'category' => 'goal'],
    ['amount' => 12, 'type' => 'Learning Points', 'reason' => 'Community Post Reached 100 Upvotes', 'session' => 'Discussion #418', 'date' => 'Sep 7, 2026', 'icon' => 'arrow_upward', 'category' => 'community'],
    ['amount' => 3, 'type' => 'Learning Points', 'reason' => 'Helpful Community Contribution', 'session' => null, 'date' => 'Sep 8, 2026', 'icon' => 'forum', 'category' => 'community'],
    ['amount' => 10, 'type' => 'Learning Points', 'reason' => 'Completed Data Analysis Session', 'session' => null, 'date' => 'Sep 5, 2026', 'icon' => 'event_available', 'category' => 'session'],
    ['amount' => -10, 'type' => 'Learning Points', 'reason' => 'Late Cancellation Penalty', 'session' => 'Hasan Mahmud', 'date' => 'Aug 28, 2026', 'icon' => 'cancel', 'category' => 'session'],
];
$mentorTransactions = [
    ['amount' => 20, 'type' => 'Mentor Points', 'reason' => 'Completed Python Mentoring Session', 'session' => 'Imran Chowdhury', 'date' => 'Sep 12, 2026', 'icon' => 'event_available', 'category' => 'session'],
    ['amount' => 5, 'type' => 'Mentor Points', 'reason' => 'Received a 5-Star Rating', 'session' => 'Python', 'date' => 'Sep 10, 2026', 'icon' => 'star', 'category' => 'rating'],
    ['amount' => 20, 'type' => 'Mentor Points', 'reason' => 'Completed Database Design Session', 'session' => 'Ayesha Rahman', 'date' => 'Sep 7, 2026', 'icon' => 'event_available', 'category' => 'session'],
    ['amount' => 25, 'type' => 'Mentor Points', 'reason' => 'Completed Data Analysis Mentoring Session', 'session' => 'Tanvir Hossain', 'date' => 'Sep 3, 2026', 'icon' => 'event_available', 'category' => 'session'],
    ['amount' => -10, 'type' => 'Mentor Points', 'reason' => 'Late Cancellation Penalty', 'session' => 'Sara Khan', 'date' => 'Aug 28, 2026', 'icon' => 'cancel', 'category' => 'session'],
];
$transactions = $isMentor ? $mentorTransactions : $learnerTransactions;
?>
<div class="ukn-page-header">
  <div>
    <h1><?= htmlspecialchars($heading) ?></h1>
    <p class="ukn-page-header__sub"><?= htmlspecialchars($subheading) ?></p>
  </div>
</div>
<div class="row g-3 mb-4">
  <?php foreach ($statOrder as $key): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($pointStats[$key]); ?></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3 mb-lg-0">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <h2 class="ukn-h4 mb-0">Transaction History</h2>
          <select class="form-select form-select-sm w-auto" id="pointsTransactionFilter" aria-label="Filter transactions">
            <?php foreach ($filterOptions as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div data-points-list>
          <?php foreach ($transactions as $transaction): ?>
            <div data-points-item data-points-category="<?= htmlspecialchars($transaction['category']) ?>">
              <?php ukn_point_transaction($transaction); ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div hidden data-points-empty>
          <?php ukn_empty_state([
              'icon' => 'military_tech',
              'title' => $isMentor ? 'No mentor point activity yet.' : 'No point activity yet.',
              'message' => $isMentor ? 'Completed mentoring sessions will appear here.' : 'Complete learning activities to start earning points.',
          ]); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h2 class="ukn-h4">How You Earn Points</h2>
        <ul class="ukn-body-sm mb-0 ps-3">
          <?php foreach ($howYouEarn as $source): ?>
            <li class="mb-2"><?= htmlspecialchars($source) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>