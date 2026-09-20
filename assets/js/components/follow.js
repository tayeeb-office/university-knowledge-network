(function () {
  'use strict';
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-follow-toggle]');
    if (!btn) {
      return;
    }

    var next = btn.getAttribute('data-following') !== 'true';
    btn.setAttribute('data-following', next ? 'true' : 'false');
    btn.setAttribute('aria-pressed', next ? 'true' : 'false');
    btn.classList.toggle('btn-primary', !next);
    btn.classList.toggle('btn-outline-secondary', next);

    var label = btn.querySelector('[data-follow-label]');
    if (label) {
      label.textContent = next ? 'Following' : 'Follow';
    }
  });
})();