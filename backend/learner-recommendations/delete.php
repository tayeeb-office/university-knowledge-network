<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/learner-recommendations.php';

// The author deletes their own recommendation. Only the recommendation id is read; ownership
// is the session user, checked in the DELETE itself, so another member's recommendation (or
// an unknown id) is "not found" whatever else is posted, in either Learner or Mentor mode.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$recommendationId = uknPostId('recommendation_id');
if ($recommendationId === null) {
    uknFailAction('Recommendation not found.', 'home');
}
try {
    $delete = getDatabaseConnection()->prepare('DELETE FROM learner_recommendations WHERE id = ? AND recommender_id = ?');
    $delete->execute([$recommendationId, (int) getCurrentUser()['id']]);
    if ($delete->rowCount() !== 1) {
        uknFailAction('Recommendation not found.', 'home');
    }
} catch (Throwable $e) {
    error_log('[UKN learner-recommendations/delete] ' . $e->getMessage());
    uknFailAction('The recommendation could not be deleted. Please try again.', 'home');
}
uknFlashToast('success', 'Recommendation deleted.');
uknRedirectBack('home');
