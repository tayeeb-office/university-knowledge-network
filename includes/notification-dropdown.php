<?php
/**
 * Notification dropdown panel — content for the notification-bell
 * `.dropdown-menu` in includes/header.php. header.php includes this file
 * right after rendering the bell badge, so $notifications (the shared
 * mock list, unread items first) is already in scope; the defaults below
 * only apply if this file is ever previewed on its own.
 *
 * Each row is rendered by components/notification-item.php's
 * ukn_notification_item() — the same function the future full
 * Notifications page uses — so this dropdown and that page can never
 * drift into two different-looking notification rows.
 *
 * "Mark all as read" has no backend — assets/js/core/dropdown.js just
 * removes the unread styling/badges from the DOM.
 */
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
