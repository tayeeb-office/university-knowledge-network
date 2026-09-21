<?php
require_once __DIR__ . '/../components/notification-item.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/format.php';

// TODO(auth): replace with the real session user id; mirrors index.php's own hardcoded
// demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

$kindLabels = ['session' => 'Session', 'community' => 'Community', 'rating' => 'Rating', 'system' => 'System'];

// index.php already computes $notifications/$notificationCount once (real DB data) before
// including header.php, which includes this file — reuse those instead of querying again.
// The `??` fallback below only runs its own query if this file is ever included without that
// upstream data already in scope, same pattern $currentUser already uses across includes/*.php.
if (!isset($notifications)) {
    $notifications = [];
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            "SELECT icon, message, type, is_read, link_url, created_at
             FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
        );
        $stmt->execute([UKN_DEMO_USER_ID]);
        $notifications = array_map(static function (array $row) use ($kindLabels): array {
            return [
                'icon' => $row['icon'],
                'text' => $row['message'],
                'time' => ukn_time_ago($row['created_at']),
                'kind' => $kindLabels[$row['type']] ?? ucfirst($row['type']),
                'unread' => !$row['is_read'],
                'href' => $row['link_url'],
            ];
        }, $stmt->fetchAll());
    } catch (Throwable $e) {
        error_log('[UKN notification-dropdown] ' . $e->getMessage());
        $notifications = [];
    }
}

if (!isset($notificationCount)) {
    $notificationCount = 0;
    try {
        $pdo = $pdo ?? getDatabaseConnection();
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $countStmt->execute([UKN_DEMO_USER_ID]);
        $notificationCount = (int) $countStmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[UKN notification-dropdown] ' . $e->getMessage());
        $notificationCount = 0;
    }
}
$unreadCount = $notificationCount;
?>
<div class="ukn-dropdown-panel__header">
  <span class="fw-bold">
    Notifications
    <?php if ($unreadCount > 0): ?>
      <span class="ukn-text-accent" data-notification-unread-count>· <?= (int) $unreadCount ?> new</span>
    <?php endif; ?>
  </span>
  <button
    type="button"
    class="border-0 bg-transparent p-0 ukn-body-sm text-decoration-underline"
    data-action="mark-all-read"
    <?= $unreadCount === 0 ? 'disabled' : '' ?>
  >
    Mark all as read
  </button>
</div>
<div class="ukn-dropdown-panel__list">
  <?php foreach ($notifications as $n): ?>
    <?php ukn_notification_item($n); ?>
  <?php endforeach; ?>
</div>
<a href="index.php?page=notifications" class="ukn-dropdown-panel__footer">View all notifications</a>
