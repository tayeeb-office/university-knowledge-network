/**
 * Dropdown content behavior.
 *
 * Bootstrap's own Dropdown component (bootstrap.bundle.min.js, which
 * includes Popper) already handles: opening/closing on trigger click,
 * closing a previously-open dropdown when a different one is opened,
 * closing on an outside click, closing on Escape, aria-expanded, and
 * flip/overflow-aware positioning. None of that is reimplemented here —
 * doing so would duplicate logic that already works correctly for
 * includes/notification-dropdown.php and includes/profile-dropdown.php.
 *
 * The one interaction Bootstrap has no opinion on is the notification
 * "Mark all as read" mock action. There is no backend — this only updates
 * the DOM (removes the unread styling, the per-item dots, and the bell/
 * header unread badges).
 *
 * window.UKN.markAllNotificationsRead() is exposed document-wide (not
 * scoped to one dropdown panel) because both the header's notification
 * dropdown (includes/notification-dropdown.php) AND the full Notifications
 * page (pages/notifications/notifications.php, via
 * assets/js/components/notifications.js) share the exact same
 * data-action="mark-all-read" trigger convention and need to mark every
 * [data-notification-item] unread across the whole document — not just
 * whichever one panel/list the click happened in — since both surfaces
 * mirror the same underlying mock notification state.
 */
(function () {
  'use strict';

  function markAllNotificationsRead() {
    document.querySelectorAll('[data-notification-item].is-unread').forEach(function (item) {
      item.classList.remove('is-unread');
      var dot = item.querySelector('.ukn-notification-dot');
      if (dot) {
        dot.remove();
      }
    });

    document.querySelectorAll('[data-notification-badge]').forEach(function (badge) {
      badge.remove();
    });

    document.querySelectorAll('[data-notification-unread-count]').forEach(function (el) {
      el.remove();
    });

    document.querySelectorAll('[data-action="mark-all-read"]').forEach(function (btn) {
      btn.setAttribute('disabled', 'disabled');
    });
  }

  window.UKN = window.UKN || {};
  window.UKN.markAllNotificationsRead = markAllNotificationsRead;

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-action="mark-all-read"]');
    if (!trigger) {
      return;
    }
    markAllNotificationsRead();
  });
})();
