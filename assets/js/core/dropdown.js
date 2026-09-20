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
