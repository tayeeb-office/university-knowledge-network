<?php
require_once __DIR__ . '/../../components/rating-item.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';


$overall = 0.0;
$totalReviews = 0;
$completedSessions = 0;
$breakdown = [];
$distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$reviews = [];
$skillFilters = ['All Skills'];
$ratingsDbError = false;

$stars = static function (float $value): string {
    $rounded = (int) round($value);
    return str_repeat('★', max(0, min(5, $rounded))) . str_repeat('☆', 5 - max(0, min(5, $rounded)));
};

try {
    $pdo = getDatabaseConnection();

    $summaryStmt = $pdo->prepare(
        "SELECT AVG(overall) AS overall, COUNT(*) AS total, AVG(teaching) AS teaching,
                AVG(communication) AS communication, AVG(helpfulness) AS helpfulness
         FROM session_ratings WHERE mentor_id = ?"
    );
    $summaryStmt->execute([UKN_CURRENT_USER_ID]);
    $summary = $summaryStmt->fetch();
    $totalReviews = (int) $summary['total'];
    $overall = $summary['overall'] !== null ? round((float) $summary['overall'], 1) : 0.0;
    if ($totalReviews > 0) {
        $breakdown = [
            'Teaching Quality' => round((float) $summary['teaching'], 1),
            'Communication' => round((float) $summary['communication'], 1),
            'Helpfulness' => round((float) $summary['helpfulness'], 1),
            'Overall Experience' => $overall,
        ];
    }

    $userStmt = $pdo->prepare("SELECT sessions_as_mentor FROM users WHERE id = ?");
    $userStmt->execute([UKN_CURRENT_USER_ID]);
    $completedSessions = (int) $userStmt->fetchColumn();

    $distStmt = $pdo->prepare(
        "SELECT ROUND(overall) AS star, COUNT(*) AS n FROM session_ratings
         WHERE mentor_id = ? GROUP BY star"
    );
    $distStmt->execute([UKN_CURRENT_USER_ID]);
    foreach ($distStmt->fetchAll() as $row) {
        $star = (int) $row['star'];
        if (isset($distribution[$star])) {
            $distribution[$star] = (int) $row['n'];
        }
    }

    $reviewsStmt = $pdo->prepare(
        "SELECT sr.session_id, sr.overall, sr.teaching, sr.communication, sr.helpfulness, sr.review,
                sr.created_at, ur.id AS reviewer_id, ur.full_name AS reviewer, ur.initials,
                sk.id AS skill_id, sk.name AS skill
         FROM session_ratings sr
         JOIN users ur ON ur.id = sr.reviewer_id
         LEFT JOIN skills sk ON sk.id = sr.skill_id
         WHERE sr.mentor_id = ?
         ORDER BY sr.created_at DESC"
    );
    $reviewsStmt->execute([UKN_CURRENT_USER_ID]);
    $reviews = array_map(static function (array $row): array {
        return [
            'reviewer' => $row['reviewer'],
            'initials' => $row['initials'],
            'reviewerHref' => ukn_route_href('learner-profile') . '&id=' . $row['reviewer_id'],
            'overall' => (float) $row['overall'],
            'teaching' => $row['teaching'] !== null ? (int) $row['teaching'] : null,
            'communication' => $row['communication'] !== null ? (int) $row['communication'] : null,
            'helpfulness' => $row['helpfulness'] !== null ? (int) $row['helpfulness'] : null,
            'skill' => $row['skill'],
            'skillHref' => $row['skill_id'] ? ukn_route_href('skill-details') . '&id=' . $row['skill_id'] : null,
            'sessionLabel' => 'View Session',
            'sessionHref' => ukn_route_href('session-details') . '&id=' . $row['session_id'] . '&from=sessions',
            'date' => date('F j, Y', strtotime($row['created_at'])),
            'dateSort' => date('Y-m-d', strtotime($row['created_at'])),
            'review' => (string) $row['review'],
        ];
    }, $reviewsStmt->fetchAll());

    $skillsStmt = $pdo->prepare(
        "SELECT DISTINCT sk.name FROM session_ratings sr JOIN skills sk ON sk.id = sr.skill_id
         WHERE sr.mentor_id = ? ORDER BY sk.name"
    );
    $skillsStmt->execute([UKN_CURRENT_USER_ID]);
    foreach ($skillsStmt->fetchAll(PDO::FETCH_COLUMN) as $skillName) {
        $skillFilters[] = $skillName;
    }
} catch (Throwable $e) {
    error_log('[UKN ratings] ' . $e->getMessage());
    $ratingsDbError = true;
}
?>
<div class="ukn-page-header">
  <div>
    <h1>Ratings</h1>
    <p class="ukn-page-header__sub">View feedback from learners you've mentored.</p>
  </div>
</div>
<?php if ($ratingsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load ratings.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body text-center">
        <div class="ukn-display"><?= htmlspecialchars((string) $overall) ?></div>
        <div class="ukn-stars" aria-label="<?= htmlspecialchars((string) $overall) ?> out of 5"><?= $stars($overall) ?></div>
        <p class="ukn-body-sm mt-2 mb-0"><?= $totalReviews ?> reviews &middot; <?= $completedSessions ?> completed sessions</p>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-body">
        <div class="ukn-eyebrow mb-3">Breakdown</div>
        <?php foreach ($breakdown as $label => $value): ?>
          <div class="ukn-rating-item__breakdown">
            <span><?= htmlspecialchars($label) ?></span>
            <span class="ukn-stars" aria-label="<?= htmlspecialchars((string) $value) ?> out of 5"><?= $stars($value) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="ukn-eyebrow mb-3">Star Distribution</div>
        <?php foreach ($distribution as $starCount => $count): $pct = $totalReviews ? round($count / $totalReviews * 100) : 0; ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="ukn-body-sm flex-shrink-0 ukn-star-distribution__label"><?= $starCount ?> Star<?= $starCount === 1 ? '' : 's' ?></span>
            <div class="progress flex-fill" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= $starCount ?>-star reviews: <?= $count ?> of <?= $totalReviews ?>">
              <div class="progress-bar" style="width: <?= $pct ?>%"></div>
            </div>
            <span class="ukn-body-sm flex-shrink-0 ukn-star-distribution__count"><?= $count ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="ukn-tabs-pill mb-3" data-rating-filters role="group" aria-label="Filter reviews by star rating">
      <button type="button" class="ukn-tab-pill is-active" data-rating-filter="0">All Ratings</button>
      <button type="button" class="ukn-tab-pill" data-rating-filter="5">5 Stars</button>
      <button type="button" class="ukn-tab-pill" data-rating-filter="4">4 Stars</button>
      <button type="button" class="ukn-tab-pill" data-rating-filter="3">3 Stars</button>
      <button type="button" class="ukn-tab-pill" data-rating-filter="2">2 Stars</button>
      <button type="button" class="ukn-tab-pill" data-rating-filter="1">1 Star</button>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <select class="form-select form-select-sm w-auto" id="ratingsSkillFilter" aria-label="Filter reviews by skill">
        <?php foreach ($skillFilters as $skill): ?>
          <option value="<?= $skill === 'All Skills' ? '' : htmlspecialchars(strtolower($skill)) ?>"><?= htmlspecialchars($skill) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select form-select-sm w-auto" id="ratingsSort" aria-label="Sort reviews">
        <option value="newest">Newest</option>
        <option value="highest">Highest Rated</option>
        <option value="lowest">Lowest Rated</option>
      </select>
      <span class="ukn-body-sm ms-auto" data-ratings-count><?= count($reviews) ?> Reviews</span>
    </div>
    <div data-ratings-list>
      <?php foreach ($reviews as $review): ukn_rating_item($review); endforeach; ?>
    </div>
    <div<?= $reviews ? ' hidden' : '' ?> data-ratings-empty>
      <?php ukn_empty_state([
          'icon' => 'star',
          'title' => 'No ratings match this filter.',
          'message' => 'Try a different star rating or skill filter.',
          'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-ratings-clear-filters'],
          'dashed' => true,
      ]); ?>
    </div>
  </div>
</div>
<?php endif; ?>