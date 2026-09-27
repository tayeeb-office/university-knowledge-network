<?php
require_once __DIR__ . '/../../components/learner-recommendation-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/learner-recommendations.php';

// Sidebar "Recommendations": the recommendations other members wrote for the signed-in user's
// learner profile (learner_recommendations), newest first. Always the session user — no id is
// read from the request — and the same list in Learner and Mentor mode (route is login-guarded).
// Read-only: authors delete their own recommendation from the learner profile.
$received = [];
$receivedDbError = false;
try {
    $received = uknLearnerRecommendations(getDatabaseConnection(), UKN_CURRENT_USER_ID);
} catch (Throwable $e) {
    error_log('[UKN recommendations] ' . $e->getMessage());
    $receivedDbError = true;
}
$receivedCount = count($received);
?>
<div class="ukn-page-header">
  <div>
    <h1>Recommendations</h1>
    <p class="ukn-page-header__sub" data-recommendation-summary>
      <?= $receivedCount === 0 ? 'Recommendations other members write for you will appear here.'
          : 'Members have written ' . $receivedCount . ' recommendation' . ($receivedCount === 1 ? '' : 's') . ' for you.' ?>
    </p>
  </div>
</div>
<?php if ($receivedDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load recommendations.',
      'message' => 'Something went wrong while loading your recommendations. Please try again shortly.',
  ]); ?>
<?php elseif ($received === []): ?>
  <?php ukn_empty_state([
      'icon' => 'recommend',
      'title' => 'No recommendations yet.',
      'message' => 'When another member writes a recommendation on your learner profile, it will appear here.',
  ]); ?>
<?php else: ?>
  <?php foreach ($received as $rec): ukn_learner_recommendation_card($rec); endforeach; ?>
<?php endif; ?>
