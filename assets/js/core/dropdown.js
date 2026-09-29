(function () {
  'use strict';
  function markAllNotificationsRead() {
    // data-notification-item sits on each item's <form>; is-unread is on the <button> inside it.
    document.querySelectorAll('[data-notification-item].is-unread, [data-notification-item] .is-unread').forEach(function (item) {
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

  // On submit, not click: disabling the submit button during its own click cancels the form
  // submission, so the request never reached backend/notifications/mark-all-read.php.
  document.addEventListener('submit', function (event) {
    if (!event.target.querySelector('[data-action="mark-all-read"]')) {
      return;
    }
    markAllNotificationsRead();
  });
})();
