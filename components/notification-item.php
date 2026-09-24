<?php
if (!function_exists('ukn_notification_item')) {
    function ukn_notification_item(array $notification): void
    {
        $notification += ['kind' => '', 'unread' => false, 'href' => null, 'id' => null];
        $categoryMap = ['session' => 'session', 'community' => 'community', 'rating' => 'community'];
        $category = $categoryMap[strtolower($notification['kind'])] ?? strtolower($notification['kind']);
        $itemClass = 'ukn-dropdown-panel__item' . (!empty($notification['unread']) ? ' is-unread' : '');
        $itemAttrs = 'data-notification-item data-notification-category="' . htmlspecialchars($category) . '"';
        $body = static function () use ($notification): void {
            ?>
            <span class="ms ukn-text-accent" aria-hidden="true"><?= htmlspecialchars($notification['icon']) ?></span>
            <span class="ukn-dropdown-panel__item-text">
              <span class="ukn-dropdown-panel__item-message"><?= htmlspecialchars($notification['text']) ?></span>
              <span class="ukn-body-sm"><?= htmlspecialchars($notification['time']) ?><?php if ($notification['kind']): ?> &middot; <?= htmlspecialchars($notification['kind']) ?><?php endif; ?></span>
            </span>
            <?php if (!empty($notification['unread'])): ?>
              <span class="ukn-notification-dot" aria-hidden="true"></span>
            <?php endif; ?>
            <?php
        };
        if ($notification['id'] !== null && function_exists('csrfField')):
            // Real notification: opening it marks it read, then goes to its target (Step 41).
            ?>
            <form action="backend/notifications/open.php" method="post" class="m-0" <?= $itemAttrs ?>>
              <?= csrfField() ?>
              <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
              <button type="submit" class="<?= $itemClass ?> w-100 text-start border-0 bg-transparent"><?php $body(); ?></button>
            </form>
            <?php
        else:
            $tag = $notification['href'] ? 'a' : 'div';
            $hrefAttr = $notification['href'] ? ' href="' . htmlspecialchars($notification['href']) . '"' : '';
            ?>
            <<?= $tag ?><?= $hrefAttr ?> class="<?= $itemClass ?>" <?= $itemAttrs ?>><?php $body(); ?></<?= $tag ?>>
            <?php
        endif;
    }
}
