/**
 * Mobile offcanvas navigation behavior (includes/mobile-nav.php).
 *
 * Bootstrap's Offcanvas component does not close itself when a link
 * inside it is activated, so this hides #uknMobileNav whenever a real
 * navigation link is clicked — otherwise the menu would stay open over
 * the page the user just navigated to.
 */
(function () {
  'use strict';

  function initMobileNav() {
    var panel = document.getElementById('uknMobileNav');
    if (!panel) {
      return;
    }

    panel.addEventListener('click', function (event) {
      var link = event.target.closest('a.ukn-nav-link');
      if (!link) {
        return;
      }

      if (typeof bootstrap === 'undefined' || !bootstrap.Offcanvas) {
        return;
      }

      var instance = bootstrap.Offcanvas.getInstance(panel);
      if (instance) {
        instance.hide();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', initMobileNav);
})();
