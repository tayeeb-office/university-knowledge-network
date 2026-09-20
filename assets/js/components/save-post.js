(function () {
  'use strict';
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-save-post]');
    if (!btn) {
      return;
    }
    var next = btn.getAttribute('data-saved') !== 'true';
    btn.setAttribute('data-saved', next ? 'true' : 'false');
    btn.setAttribute('aria-pressed', next ? 'true' : 'false');
    btn.classList.toggle('is-active', next);

    var icon = btn.querySelector('[data-save-icon]');
    if (icon) {
      icon.textContent = next ? 'bookmark' : 'bookmark_border';
    }

    var label = btn.querySelector('[data-save-label]');
    if (label) {
      label.textContent = next ? 'Saved' : 'Save';
    }
  });
})();