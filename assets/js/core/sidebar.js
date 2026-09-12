/**
 * Left sidebar behavior.
 *
 * At tablet widths (768px-991px, see assets/css/responsive.css) the
 * sidebar collapses to an icon-only rail. Each nav link already carries a
 * title/tooltip attribute (includes/left-sidebar.php), so this just wires
 * up Bootstrap's Tooltip component to make the label reachable on hover
 * and keyboard focus when the text is hidden.
 */
(function () {
  'use strict';

  function initSidebarTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
      return;
    }

    var sidebar = document.querySelector('.ukn-sidebar-left');
    if (!sidebar) {
      return;
    }

    sidebar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      if (!bootstrap.Tooltip.getInstance(el)) {
        new bootstrap.Tooltip(el);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', initSidebarTooltips);
})();
