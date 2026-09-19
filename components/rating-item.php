<?php
/**
 * Rating item — reusable review component.
 * Used by: Ratings (a mentor's review list), Mentor Profile.
 *
 * Usage:
 *   require_once __DIR__ . '/../components/rating-item.php';
 *   foreach ($reviews as $review) { ukn_rating_item($review); }
 *
 * $review shape:
 *   [
 *     'reviewer'  => 'Sara Khan', 'initials' => 'SK',
 *     'reviewerHref' => null,               // optional — links the reviewer's name
 *     'overall'   => 5,                     // 1-5
 *     'teaching'  => 5, 'communication' => 4, 'helpfulness' => 5,  // optional breakdown, 1-5 each
 *     'review'    => 'Explained pandas merges so clearly...',
 *     'date'      => 'Sep 10',
 *     'dateSort'  => '2026-09-10',          // optional ISO date — powers
 *                                          // data-review-date, only needed
 *                                          // where assets/js/pages/ratings.js's
 *                                          // "Newest" sort applies (the Ratings page)
 *     'skill'     => 'Python',              // optional related skill/session context
 *     'skillHref' => null,                  // optional — links the skill to Skill Details
 *     'sessionLabel' => null,               // optional, e.g. 'Session S-1039'
 *     'sessionHref'  => null,               // optional — links to Session Details
 *   ]
 *
 * .ukn-stars (assets/css/utilities.css) is the one star-rendering style
 * used everywhere in the app — reused here rather than a one-off.
 * data-review-rating/-skill/-date on the wrapper are only read by
 * assets/js/pages/ratings.js's filter/sort — harmless on any other page
 * that renders this component (Mentor Profile) and reuses it unchanged.
 */
if (!function_exists('ukn_rating_item')) {
    function ukn_rating_item(array $review): void
    {
        $review += [
            'initials' => '?', 'reviewerHref' => null, 'overall' => 5, 'teaching' => null,
            'communication' => null, 'helpfulness' => null, 'review' => '', 'date' => '', 'dateSort' => '',
            'skill' => null, 'skillHref' => null, 'sessionLabel' => null, 'sessionHref' => null,
        ];
        $stars = static fn (int $n): string => str_repeat('★', max(0, min(5, $n))) . str_repeat('☆', 5 - max(0, min(5, $n)));
        $breakdown = array_filter([
            'Teaching Quality' => $review['teaching'],
            'Communication' => $review['communication'],
            'Helpfulness' => $review['helpfulness'],
        ], static fn ($v) => $v !== null);
        ?>
        <div
          class="card mb-3"
          data-review-rating="<?= (int) round($review['overall']) ?>"
          data-review-skill="<?= htmlspecialchars(strtolower((string) $review['skill'])) ?>"
          data-review-date="<?= htmlspecialchars($review['dateSort']) ?>"
        >
          <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="ukn-avatar" aria-hidden="true"><?= htmlspecialchars($review['initials']) ?></span>
              <div class="flex-fill ukn-min-w-0">
                <div class="fw-bold ukn-truncate">
                  <?php if ($review['reviewerHref']): ?>
                    <a href="<?= htmlspecialchars($review['reviewerHref']) ?>"><?= htmlspecialchars($review['reviewer']) ?></a>
                  <?php else: ?>
                    <?= htmlspecialchars($review['reviewer']) ?>
                  <?php endif; ?>
                </div>
                <div class="ukn-body-sm">
                  <?php if ($review['skill']): ?>
                    <?php if ($review['skillHref']): ?>
                      <a href="<?= htmlspecialchars($review['skillHref']) ?>"><?= htmlspecialchars($review['skill']) ?></a>
                    <?php else: ?>
                      <?= htmlspecialchars($review['skill']) ?>
                    <?php endif; ?>
                     ·
                  <?php endif; ?><?= htmlspecialchars($review['date']) ?>
                </div>
              </div>
              <span class="ukn-stars flex-shrink-0" aria-label="<?= htmlspecialchars(rtrim(rtrim(sprintf('%.1f', $review['overall']), '0'), '.')) ?> out of 5 stars"><?= $stars((int) round($review['overall'])) ?></span>
            </div>

            <?php if ($review['review']): ?>
              <p class="ukn-body mb-0"><?= htmlspecialchars($review['review']) ?></p>
            <?php endif; ?>

            <?php if ($review['sessionLabel']): ?>
              <div class="ukn-body-sm mt-2">
                <?php if ($review['sessionHref']): ?>
                  <a href="<?= htmlspecialchars($review['sessionHref']) ?>"><?= htmlspecialchars($review['sessionLabel']) ?></a>
                <?php else: ?>
                  <?= htmlspecialchars($review['sessionLabel']) ?>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($breakdown): ?>
              <div class="mt-3 pt-3 ukn-border-top">
                <?php foreach ($breakdown as $label => $value): ?>
                  <div class="ukn-rating-item__breakdown">
                    <span><?= htmlspecialchars($label) ?></span>
                    <span class="ukn-stars" aria-label="<?= (int) $value ?> out of 5"><?= $stars((int) $value) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php
    }
}
