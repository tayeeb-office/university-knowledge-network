<?php
/**
 * Notification item — one reusable row, used by BOTH the header's
 * notification dropdown (includes/notification-dropdown.php) and the
 * future full Notifications page — extracted here so the two never
 * drift into two different-looking notification rows.
 *
 * Usage:
 *   require_once __DIR__ . '/../components/notification-item.php';
 *   foreach ($notifications as $n) { ukn_notification_item($n); }
 *
 * $notification shape:
 *   [
 *     'icon'   => 'event_available',   // Material Symbol; pick per type —
 *                                       // Session Request/Accepted/Completed,
 *                                       // Comment, Reply, Follow, Rating
 *     'text'   => 'Rahim Ahmed accepted your Python session request.',
 *     'time'   => '2 min ago',
 *     'kind'   => 'Session',           // short category label
 *     'unread' => true,
 *     'href'   => null,                // optional deep link
 *   ]
 *
 * Read/unread is communicated three ways at once (tinted background,
 * bold message text, accent dot) — never color alone. No real
 * notification fetching; this only renders whatever array it's given.
 *
 * data-notification-category below is derived from 'kind' (never a new
 * required field) so pages/notifications/notifications.php's Sessions/
 * Community filter has something stable to match against without relying
 * on 'kind''s own display text/wording — harmless, inert data in the
 * header dropdown, which doesn't filter.
 */
if (!function_exists('ukn_notification_item')) {
    function ukn_notification_item(array $notification): void
    {
        $notification += ['kind' => '', 'unread' => false, 'href' => null];
        $tag = $notification['href'] ? 'a' : 'div';
        $hrefAttr = $notification['href'] ? ' href="' . htmlspecialchars($notification['href']) . '"' : '';

        $categoryMap = ['session' => 'session', 'community' => 'community', 'rating' => 'community'];
        $category = $categoryMap[strtolower($notification['kind'])] ?? strtolower($notification['kind']);
        ?>
        <<?= $tag ?><?= $hrefAttr ?> class="ukn-dropdown-panel__item<?= !empty($notification['unread']) ? ' is-unread' : '' ?>" data-notification-item data-notification-category="<?= htmlspecialchars($category) ?>">
          <span class="ms ukn-text-accent" aria-hidden="true"><?= htmlspecialchars($notification['icon']) ?></span>
          <span class="ukn-dropdown-panel__item-text">
            <span class="ukn-dropdown-panel__item-message"><?= htmlspecialchars($notification['text']) ?></span>
            <span class="ukn-body-sm"><?= htmlspecialchars($notification['time']) ?><?php if ($notification['kind']): ?> &middot; <?= htmlspecialchars($notification['kind']) ?><?php endif; ?></span>
          </span>
          <?php if (!empty($notification['unread'])): ?>
            <span class="ukn-notification-dot" aria-hidden="true"></span>
          <?php endif; ?>
        </<?= $tag ?>>
        <?php
    }
}
