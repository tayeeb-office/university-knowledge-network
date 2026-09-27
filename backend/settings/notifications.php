<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/community.php';

// Settings → Notifications: saves the logged-in user's notification preferences (user_settings).
// Only the preferences the form showed (shown[], one of UKN_NOTIFICATION_SETTINGS each) are
// written, so the other mode's options keep their values; a shown box that is not ticked is off.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('settings');

$shown = $_POST['shown'] ?? null;
$columns = is_array($shown)
    ? array_values(array_intersect(UKN_NOTIFICATION_SETTINGS, array_filter($shown, 'is_string')))
    : [];
if ($columns === []) {
    uknFailAction('Nothing to save.', 'settings');
}
$values = array_map(static fn (string $column): int => uknPostString($column) === '1' ? 1 : 0, $columns);
try {
    getDatabaseConnection()->prepare(
        'INSERT INTO user_settings (user_id, ' . implode(', ', $columns) . ') VALUES (?' . str_repeat(', ?', count($columns)) . ')
         ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(static fn (string $c): string => "{$c} = VALUES({$c})", $columns))
    )->execute(array_merge([(int) getCurrentUser()['id']], $values));
} catch (Throwable $e) {
    error_log('[UKN settings/notifications] ' . $e->getMessage());
    uknFailAction('Your notification settings could not be saved. Please try again.', 'settings');
}
uknFlashToast('success', 'Notification settings saved.');
uknRedirectBack('settings');
