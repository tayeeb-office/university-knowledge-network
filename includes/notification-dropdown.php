<?php
require_once __DIR__ . '/../components/notification-item.php';
$notifications = $notifications ?? [
    ['icon' => 'event_available', 'text' => 'Rahim Ahmed accepted your Python session request.', 'time' => '2 min ago', 'kind' => 'Session', 'unread' => true],
    ['icon' => 'chat_bubble', 'text' => 'Sara Khan replied to your discussion.', 'time' => '10 min ago', 'kind' => 'Community', 'unread' => true],
    ['icon' => 'person_add', 'text' => 'Hasan Mahmud followed you.', 'time' => '1 hr ago', 'kind' => 'Community', 'unread' => true],
    ['icon' => 'check_circle', 'text' => 'Your JavaScript mentoring session has been completed.', 'time' => 'Yesterday', 'kind' => 'Session', 'unread' => false],
];
$unreadCount = count(array_filter($notifications, static fn (array $n): bool => !empty($n['unread'])));
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