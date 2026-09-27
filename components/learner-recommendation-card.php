<?php
require_once __DIR__ . '/../backend/helpers/avatars.php';
if (!function_exists('ukn_learner_recommendation_card')) {
    /**
     * One received learner recommendation (a row from uknLearnerRecommendations()): recommender
     * photo/initials, linked name, department · year, date and text. $delete adds the author's
     * Delete action (['learnerId' => …, 'learnerName' => …]); the caller passes it only when the
     * viewer wrote this recommendation.
     */
    function ukn_learner_recommendation_card(array $rec, ?array $delete = null): void
    {
        $meta = implode(' · ', array_filter([(string) ($rec['department'] ?? ''), (string) ($rec['year'] ?? '')], 'strlen'));
        ?>
        <div class="card mb-3" data-recommendation-by="<?= (int) $rec['recommender_id'] ?>">
          <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <?= uknAvatarHtml($rec['avatar_path'], (string) $rec['initials'], 'ukn-avatar flex-shrink-0') ?>
              <div class="flex-fill ukn-min-w-0">
                <div class="fw-bold ukn-truncate">
                  <a href="<?= htmlspecialchars(ukn_route_href('learner-profile') . '&id=' . (int) $rec['recommender_id']) ?>"><?= htmlspecialchars($rec['name']) ?></a>
                </div>
                <?php if ($meta !== ''): ?><div class="ukn-body-sm ukn-truncate"><?= htmlspecialchars($meta) ?></div><?php endif; ?>
              </div>
              <time class="ukn-body-sm flex-shrink-0" datetime="<?= htmlspecialchars(date('Y-m-d', strtotime($rec['created_at']))) ?>"><?= htmlspecialchars(date('M j, Y', strtotime($rec['created_at']))) ?></time>
            </div>
            <p class="ukn-body ukn-prose mb-0"><?= nl2br(htmlspecialchars($rec['content'])) ?></p>
            <?php if ($delete !== null): ?>
            <div class="d-flex justify-content-end mt-2">
              <button
                type="button" class="btn-ghost ukn-text-danger"
                data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                data-delete-title="Delete your recommendation?"
                data-delete-message="Your recommendation for <?= htmlspecialchars($delete['learnerName']) ?> will be removed from their profile."
                data-delete-confirm-label="Delete"
                data-delete-form="deleteRecommendation-<?= (int) $rec['id'] ?>"
              >Delete</button>
            </div>
            <form id="deleteRecommendation-<?= (int) $rec['id'] ?>" action="backend/learner-recommendations/delete.php" method="post" hidden>
              <?= csrfField() ?>
              <input type="hidden" name="return_to" value="<?= htmlspecialchars(uknBaseUrl() . '/' . ukn_route_href('learner-profile') . '&id=' . (int) $delete['learnerId'] . '&tab=recommendations') ?>">
              <input type="hidden" name="recommendation_id" value="<?= (int) $rec['id'] ?>">
            </form>
            <?php endif; ?>
          </div>
        </div>
        <?php
    }
}
