window.UKN = window.UKN || {};

(function () {
  'use strict';
  // Follow / Following buttons are rendered by the server from the follows table. A page the
  // browser restores from its back/forward cache (even with Cache-Control: no-store) shows the
  // state from when it was left, so a follow or unfollow made elsewhere would look undone.
  // Reload such a page so its buttons match the database again.
  window.addEventListener('pageshow', function (event) {
    if (event.persisted && document.querySelector('[data-follow-label]')) {
      window.location.reload();
    }
  });
})();
