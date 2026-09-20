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