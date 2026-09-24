<?php
require_once __DIR__ . '/../helpers/actions.php';

// Step 41: mark all of the logged-in user's notifications as read.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('notifications');

try {
    getDatabaseConnection()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')
        ->execute([(int) getCurrentUser()['id']]);
} catch (Throwable $e) {
    error_log('[UKN notifications/mark-all-read] ' . $e->getMessage());
    uknFailAction('Could not update your notifications. Please try again.', 'notifications');
}
uknFlashToast('success', 'All notifications marked as read.');
uknRedirectBack('notifications');
