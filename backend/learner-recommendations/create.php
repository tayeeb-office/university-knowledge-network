<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/learner-recommendations.php';
require_once __DIR__ . '/../helpers/community.php';

// The logged-in user writes a recommendation for another active learner (learner or learner +
// mentor account). The recommender is always the session user, whatever mode they are in;
// recommending yourself is refused (the schema forbids it too). A member may recommend the same
// learner again: every successful submission is a new row.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$learnerId = uknPostId('learner_id');
$user = getCurrentUser();
$userId = (int) $user['id'];
if ($learnerId === null) {
    uknFailAction('Learner not found.', 'home');
}
if ($learnerId === $userId) {
    uknFailAction("You can't recommend yourself.", 'home');
}
if (array_key_exists('content', $_POST) && uknPostString('content') === null) {
    uknFailAction('Invalid recommendation.', 'home');
}
$content = sanitizeInput(uknPostString('content') ?? '');
if (!validateLength($content, 1, UKN_RECOMMENDATION_MAX)) {
    uknFailAction('Write a recommendation (up to ' . UKN_RECOMMENDATION_MAX . ' characters).', 'home');
}
try {
    $pdo = getDatabaseConnection();
    $target = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND status = 'active' AND role IN ('learner', 'dual')");
    $target->execute([$learnerId]);
    $learnerName = $target->fetchColumn();
    if ($learnerName === false) {
        uknFailAction('Learner not found.', 'home');
    }
    // The recommendation and the learner's notification commit together: one notification per
    // new recommendation. No Settings preference covers recommendations.
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO learner_recommendations (recommender_id, learner_id, content) VALUES (?, ?, ?)')
        ->execute([$userId, $learnerId, $content]);
    uknNotify($pdo, $learnerId, $userId, 'recommend', "{$user['full_name']} wrote you a recommendation.",
        'index.php?page=learner-profile&id=' . $learnerId . '&tab=recommendations', null);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN learner-recommendations/create] ' . $e->getMessage());
    uknFailAction('Your recommendation could not be saved. Please try again.', 'home');
}
uknFlashToast('success', "Your recommendation for {$learnerName} was added.");
uknRedirectBack('home');
