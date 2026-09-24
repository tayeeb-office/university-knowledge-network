<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Step 41: open one of your notifications — marks it read, then goes to its in-app target
// (or the notifications page). Another user's notification id behaves like a missing one.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('notifications');

$notificationId = uknPostId('notification_id');
if ($notificationId === null) {
    uknFailAction('Notification not found.', 'notifications');
}
try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    $stmt = $pdo->prepare('SELECT link_url FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$notificationId, $userId]);
    $link = $stmt->fetchColumn();
    if ($link === false) {
        uknFailAction('Notification not found.', 'notifications');
    }
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
} catch (Throwable $e) {
    error_log('[UKN notifications/open] ' . $e->getMessage());
    uknFailAction('Could not open this notification. Please try again.', 'notifications');
}
$target = uknSafeAppLink($link);
if ($target === null) {
    uknRedirectToRoute('notifications');
}
header('Location: ' . uknBaseUrl() . '/' . $target, true, 302);
exit;
