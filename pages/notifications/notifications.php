<?php
/**
 * Notifications — main center content only. Routed via
 * index.php?page=notifications (see index.php's $routes map). The header,
 * left sidebar and footer come from the shell; this route is deliberately
 * absent from index.php's $sidebarContextByPage map, so it renders full
 * width with no app-level right sidebar (same reasoning as
 * pages/sessions/session-details.php and pages/ratings/ratings.php).
 *
 * Every row reuses components/notification-item.php's ukn_notification_item()
 * — the exact same function includes/notification-dropdown.php's header
 * bell panel already uses — so this page and that dropdown can never drift
 * into two different-looking notification rows. "Mark all as read" reuses
 * the same data-action="mark-all-read" trigger convention and the same
 * document-wide window.UKN.markAllNotificationsRead() helper
 * (assets/js/core/dropdown.js) the dropdown's button already calls —
 * nothing here reimplements that logic a second time.
 *
 * Filtering (All / Unread / Sessions / Community) is the one genuinely new
 * behavior this page needs beyond the dropdown, so it lives in its own
 * file: assets/js/components/notifications.js.
 *
 * Frontend-only mock data throughout — no real notification delivery, no
 * read/unread persistence beyond the current page load, no database.
 *
 * The first 4 rows below intentionally repeat, word-for-word, the same
 * default mock notifications includes/header.php seeds $notifications
 * with (and therefore what includes/notification-dropdown.php, the bell
 * badge, and the sidebar's Notifications nav-item count all already show)
 * — index.php includes header.php before this page file ever runs, so
 * this file can't simply share that same PHP variable; repeating the
 * same 4 items here (3 unread, 1 read, same order) is what keeps this
 * page's unread count in sync with the header's instead of inventing a
 * second, contradictory mock unread total. The remaining rows are
 * additional already-read history, including mentor-context items (a
 * session request received, a rating received) a dual-role mentor would
 * also plausibly see here.
 */
require_once __DIR__ . '/../../components/notification-item.php';
require_once __DIR__ . '/../../components/empty-state.php';

$notifications = [
    ['icon' => 'event_available', 'text' => 'Rahim Ahmed accepted your Python session request.', 'time' => '2 min ago', 'kind' => 'Session', 'unread' => true, 'href' => ukn_route_href('session-details') . '&id=201&from=sessions'],
    ['icon' => 'chat_bubble', 'text' => 'Sara Khan replied to your discussion.', 'time' => '10 min ago', 'kind' => 'Community', 'unread' => true, 'href' => ukn_route_href('post-details')],
    ['icon' => 'person_add', 'text' => 'Hasan Mahmud followed you.', 'time' => '1 hr ago', 'kind' => 'Community', 'unread' => true, 'href' => ukn_route_href('mentor-profile') . '&id=3'],
    ['icon' => 'check_circle', 'text' => 'Your JavaScript mentoring session has been completed.', 'time' => 'Yesterday', 'kind' => 'Session', 'unread' => false, 'href' => ukn_route_href('session-details') . '&id=203&from=sessions'],
    ['icon' => 'mail', 'text' => 'Mahi Noor requested a Python mentoring session with you.', 'time' => '2 days ago', 'kind' => 'Session', 'unread' => false, 'href' => ukn_route_href('session-details') . '&id=101&from=requests'],
    ['icon' => 'star', 'text' => 'Sara Khan rated your mentoring session 5 stars.', 'time' => '2 days ago', 'kind' => 'Rating', 'unread' => false, 'href' => ukn_route_href('ratings')],
    ['icon' => 'reply', 'text' => 'Hasan Mahmud replied to your comment on "Need Help Understanding Database Normalization."', 'time' => '3 days ago', 'kind' => 'Community', 'unread' => false, 'href' => ukn_route_href('post-details')],
    ['icon' => 'schedule', 'text' => 'Your Python session with Rahim Ahmed starts in 30 minutes.', 'time' => '4 days ago', 'kind' => 'Session', 'unread' => false, 'href' => ukn_route_href('session-details') . '&id=201&from=sessions'],
];
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

<div hidden data-notification-empty>
  <?php ukn_empty_state([
      'icon' => 'notifications_none',
      'title' => "You're all caught up.",
      'message' => 'Nothing matches this filter right now.',
      'dashed' => true,
  ]); ?>
</div>
