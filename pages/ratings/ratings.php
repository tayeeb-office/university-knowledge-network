<?php
/**
 * Ratings — main center content only. Routed via index.php?page=ratings
 * (see index.php's $routes map). The header and left sidebar come from
 * the shell. This route isn't in index.php's $sidebarContextByPage map,
 * so it renders full-width with no app-level right sidebar — matching
 * the approved design, which gives this screen its own internal
 * rating-summary column instead of using the app shell's contextual
 * sidebar for that (same reasoning as pages/sessions/session-details.php).
 *
 * Mentor-oriented, but frontend-only: opening this route in Learner mode
 * does not corrupt role state or break the shell — it just shows the same
 * mentor-facing content regardless of active role (no automatic role
 * switch, per this prompt's explicit instruction).
 *
 * This page only ever DISPLAYS ratings already received — it is not the
 * rating submission form. Submitting a rating still only ever happens
 * through the one shared modals/rating-modal.php, opened from a completed
 * session's "Rate Mentor" action (components/session-card.php /
 * pages/sessions/session-details.php) — nothing here duplicates that.
 *
 * Reviews reuse components/rating-item.php as-is; category breakdown
 * reuses the exact same terminology as modals/rating-modal.php (Teaching
 * Quality / Communication / Helpfulness / Overall Experience) and the
 * same .ukn-rating-item__breakdown / .ukn-display visual language already
 * established on pages/profile/mentor-profile.php's own Rating Summary,
 * for consistency across the two places a mentor's rating appears.
 *
 * Frontend-only mock data throughout — no real rating calculation or
 * persistence, no database. Star distribution counts (30+9+2+1+0) sum to
 * exactly the 42 total reviews shown, so nothing here is internally
 * contradictory.
 */
require_once __DIR__ . '/../../components/rating-item.php';
require_once __DIR__ . '/../../components/empty-state.php';

$overall = 4.8;
$totalReviews = 42;
$completedSessions = 27;
$breakdown = ['Teaching Quality' => 4.9, 'Communication' => 4.7, 'Helpfulness' => 4.8, 'Overall Experience' => 4.8];
$distribution = [5 => 30, 4 => 9, 3 => 2, 2 => 1, 1 => 0];
$stars = static function (float $value): string {
    $rounded = (int) round($value);
    return str_repeat('★', max(0, min(5, $rounded))) . str_repeat('☆', 5 - max(0, min(5, $rounded)));
};

$reviews = [
    ['reviewer' => 'Nabila Rahman', 'initials' => 'NR', 'reviewerHref' => ukn_route_href('learner-profile'), 'overall' => 5.0, 'teaching' => 5, 'communication' => 5, 'helpfulness' => 5, 'skill' => 'Python', 'skillHref' => ukn_route_href('skill-details') . '&id=1', 'date' => 'September 12, 2026', 'dateSort' => '2026-09-12', 'review' => 'Explained Python data analysis clearly and used examples that were easy to follow.'],
    ['reviewer' => 'Ayesha Rahman', 'initials' => 'AR', 'reviewerHref' => ukn_route_href('learner-profile'), 'overall' => 4.8, 'skill' => 'Database Design', 'skillHref' => ukn_route_href('skill-details') . '&id=7', 'date' => 'September 9, 2026', 'dateSort' => '2026-09-09', 'review' => 'The session helped me understand normalization much better. The practical examples were especially useful.'],
    ['reviewer' => 'Imran Chowdhury', 'initials' => 'IC', 'reviewerHref' => ukn_route_href('learner-profile') . '&id=1', 'overall' => 4.7, 'skill' => 'Python', 'skillHref' => ukn_route_href('skill-details') . '&id=1', 'sessionLabel' => 'View Session', 'sessionHref' => ukn_route_href('session-details') . '&id=302&from=sessions', 'date' => 'September 4, 2026', 'dateSort' => '2026-09-04', 'review' => 'Very patient explanation and good communication throughout the session.'],
    ['reviewer' => 'Sara Khan', 'initials' => 'SK', 'reviewerHref' => ukn_route_href('learner-profile') . '&id=2', 'overall' => 5.0, 'skill' => 'Data Analysis', 'skillHref' => ukn_route_href('skill-details') . '&id=5', 'date' => 'August 30, 2026', 'dateSort' => '2026-08-30', 'review' => 'Made pandas groupby finally click with a real coursework example instead of a toy dataset.'],
    ['reviewer' => 'Tanvir Hossain', 'initials' => 'TH', 'reviewerHref' => ukn_route_href('learner-profile'), 'overall' => 4.5, 'skill' => 'Python', 'skillHref' => ukn_route_href('skill-details') . '&id=1', 'date' => 'August 22, 2026', 'dateSort' => '2026-08-22', 'review' => 'Good session overall, though we ran a bit over time on the debugging part.'],
    ['reviewer' => 'Mahi Noor', 'initials' => 'MN', 'reviewerHref' => ukn_route_href('learner-profile'), 'overall' => 4.0, 'skill' => 'Python', 'skillHref' => ukn_route_href('skill-details') . '&id=1', 'date' => 'August 15, 2026', 'dateSort' => '2026-08-15', 'review' => "Helpful but assumed I already knew some pandas basics I hadn't covered yet."],
];

$skillFilters = ['All Skills', 'Python', 'Database Design', 'Data Analysis'];
?>
<div class="ukn-page-header">
  <div>
    <h1>Ratings</h1>
    <p class="ukn-page-header__sub">View feedback from learners you've mentored.</p>
  </div>
</div>

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

    <div hidden data-ratings-empty>
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
