<?php
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