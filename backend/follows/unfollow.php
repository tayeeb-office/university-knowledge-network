<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 39: the logged-in user unfollows a member (only their own follow row can be removed).
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$targetId = uknPostId('following_id');
if ($targetId === null) {
    uknFailAction('Member not found.', 'home');
}
try {
    getDatabaseConnection()->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?')
        ->execute([(int) getCurrentUser()['id'], $targetId]);
} catch (Throwable $e) {
    error_log('[UKN follows/unfollow] ' . $e->getMessage());
    uknFailAction('Could not unfollow this member. Please try again.', 'home');
}
uknFlashToast('success', 'Unfollowed.');
uknRedirectBack('home');
