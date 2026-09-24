<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Steps 39 + 40: the logged-in user follows another active member. The follower is always the
// session user; following yourself is refused (the schema forbids it too). A first follow
// notifies the member if they have "follow activity" notifications switched on.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$targetId = uknPostId('following_id');
$user = getCurrentUser();
$userId = (int) $user['id'];
if ($targetId === null || $targetId === $userId) {
    uknFailAction($targetId === $userId ? "You can't follow yourself." : 'Member not found.', 'home');
}
try {
    $pdo = getDatabaseConnection();
    $target = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND status = 'active'");
    $target->execute([$targetId]);
    $targetName = $target->fetchColumn();
    if ($targetName === false) {
        uknFailAction('Member not found.', 'home');
    }
    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)');
    $insert->execute([$userId, $targetId]);
    if ($insert->rowCount() === 1) {
        $profileRoute = $user['role'] === 'mentor' ? 'mentor-profile' : 'learner-profile';
        uknNotify($pdo, $targetId, $userId, 'person_add', "{$user['full_name']} started following you.",
            "index.php?page={$profileRoute}&id={$userId}", 'notify_follow_activity', true);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN follows/follow] ' . $e->getMessage());
    uknFailAction('Could not follow this member. Please try again.', 'home');
}
uknFlashToast('success', "You're following {$targetName}.");
uknRedirectBack('home');
