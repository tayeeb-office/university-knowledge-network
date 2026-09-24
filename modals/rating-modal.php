<?php
// Filled per trigger by modal.js (data-rating-* attributes); saved by backend/sessions/rate.php.
$ratingSession = ($ratingSession ?? []) + ['mentor' => '', 'skill' => '', 'date' => ''];
$ratingCategories = [
    ['name' => 'rating_teaching', 'label' => 'Teaching Quality', 'required' => false],
    ['name' => 'rating_communication', 'label' => 'Communication', 'required' => false],
    ['name' => 'rating_helpfulness', 'label' => 'Helpfulness', 'required' => false],
    ['name' => 'rating_overall', 'label' => 'Overall Experience', 'required' => true],
];
?>
<div class="modal fade" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form action="backend/sessions/rate.php" method="post" data-validated-form novalidate>
        <?= function_exists('csrfField') ? csrfField() : '' ?>
        <?= function_exists('uknReturnToField') ? uknReturnToField() : '' ?>
        <input type="hidden" name="session_id" value="" data-rating-session-id-input>
        <div class="modal-header">
          <h2 class="modal-title ukn-h3" id="ratingModalLabel">Rate Your Session</h2>
          <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
            <span class="ms" aria-hidden="true">close</span>
          </button>
        </div>
        <div class="modal-body">
          <p class="ukn-body-sm mb-4" data-rating-summary><?= htmlspecialchars($ratingSession['skill']) ?> with <?= htmlspecialchars($ratingSession['mentor']) ?> · <?= htmlspecialchars($ratingSession['date']) ?></p>
          <?php foreach ($ratingCategories as $category): ?>
            <div class="ukn-form-group">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <label class="form-label mb-0" id="<?= $category['name'] ?>Label">
                  <?= htmlspecialchars($category['label']) ?>
                  <?php if ($category['required']): ?><span class="ukn-text-danger" aria-hidden="true">*</span><?php endif; ?>
                </label>
                <div
                  class="ukn-star-input"
                  role="radiogroup"
                  aria-labelledby="<?= $category['name'] ?>Label"
                  <?php if ($category['required']): ?>data-validate-group="required" data-group-name="<?= $category['name'] ?>"<?php endif; ?>
                >
                  <?php for ($star = 5; $star >= 1; $star--): ?>
                    <input type="radio" name="<?= $category['name'] ?>" id="<?= $category['name'] ?>_<?= $star ?>" value="<?= $star ?>" class="ukn-visually-hidden">
                    <label for="<?= $category['name'] ?>_<?= $star ?>" aria-label="<?= $star ?> star<?= $star > 1 ? 's' : '' ?>">★</label>
                  <?php endfor; ?>
                </div>
              </div>
              <?php if ($category['required']): ?>
                <div class="ukn-field-message is-invalid" data-error-for="<?= $category['name'] ?>" hidden>
                  <span class="ms" aria-hidden="true">error</span>Please choose an overall rating.
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <div class="ukn-form-group mb-0">
            <label for="ratingReview" class="form-label">Written Review</label>
            <textarea class="form-control ukn-textarea-lg" id="ratingReview" name="review" maxlength="2000" placeholder="Share what was helpful about this session..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Submit Rating</button>
        </div>
      </form>
    </div>
  </div>
</div>