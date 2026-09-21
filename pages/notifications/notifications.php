<?php
require_once __DIR__ . '/../../components/notification-item.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';

// TODO(auth): replace with the real session user id; mirrors index.php's own hardcoded
// demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

$notifications = [];
$notificationsDbError = false;
$kindLabels = ['session' => 'Session', 'community' => 'Community', 'rating' => 'Rating', 'system' => 'System'];

try {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare(
        "SELECT icon, message, type, is_read, link_url, created_at
         FROM notifications WHERE user_id = ? ORDER BY created_at DESC"
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
    error_log('[UKN notifications] ' . $e->getMessage());
    $notificationsDbError = true;
    $notifications = [];
}

$unreadCount = count(array_filter($notifications, static fn (array $n): bool => !empty($n['unread'])));
?>
<div class="ukn-page-header">
  <div>
    <h1>Notifications</h1>
    <p class="ukn-page-header__sub">
      <?= $unreadCount > 0 ? "You have {$unreadCount} unread notification" . ($unreadCount === 1 ? '' : 's') . '.' : "You're all caught up." ?>
    </p>
  </div>
</div>
<?php if ($notificationsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load notifications.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <div class="ukn-tabs-pill" data-notification-filters role="group" aria-label="Filter notifications">
    <button type="button" class="ukn-tab-pill is-active" data-notification-filter="all" aria-pressed="true">All</button>
    <button type="button" class="ukn-tab-pill" data-notification-filter="unread" aria-pressed="false">Unread<span data-notification-tab-count><?= $unreadCount > 0 ? ' (' . $unreadCount . ')' : '' ?></span></button>
    <button type="button" class="ukn-tab-pill" data-notification-filter="session" aria-pressed="false">Sessions</button>
    <button type="button" class="ukn-tab-pill" data-notification-filter="community" aria-pressed="false">Community</button>
  </div>
  <button type="button" class="btn btn-outline-secondary btn-sm" data-action="mark-all-read" <?= $unreadCount === 0 ? 'disabled' : '' ?>>
    Mark All as Read
  </button>
</div>
<div class="card ukn-notification-list">
  <div data-notification-list>
    <?php foreach ($notifications as $notification): ukn_notification_item($notification); endforeach; ?>
  </div>
</div>
<div<?= $notifications ? ' hidden' : '' ?> data-notification-empty>
  <?php ukn_empty_state([
      'icon' => 'notifications_none',
      'title' => "You're all caught up.",
      'message' => 'Nothing matches this filter right now.',
      'dashed' => true,
  ]); ?>
</div>
<?php endif; ?>