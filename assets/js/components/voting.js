(function () {
  'use strict';
  function applyVoteState(group, state) {
    var scoreEl = group.querySelector('[data-vote-score]');
    if (!scoreEl) {
      return;
    }
    var base = parseInt(scoreEl.getAttribute('data-vote-base'), 10) || 0;
    scoreEl.textContent = String(base + state);
    group.setAttribute('data-vote-state', String(state));
    var upBtn = group.querySelector('[data-vote-up]');
    if (upBtn) {
      upBtn.classList.toggle('is-active', state === 1);
      upBtn.setAttribute('aria-pressed', state === 1 ? 'true' : 'false');
    }
    var downBtn = group.querySelector('[data-vote-down]');
    if (downBtn) {
      downBtn.classList.toggle('is-active', state === -1);
      downBtn.setAttribute('aria-pressed', state === -1 ? 'true' : 'false');
    }
  }
  document.addEventListener('click', function (event) {
    var upBtn = event.target.closest('[data-vote-up]');
    var downBtn = event.target.closest('[data-vote-down]');
    if (!upBtn && !downBtn) {
      return;
    }

    var group = (upBtn || downBtn).closest('.ukn-vote-rail');
    if (!group) {
      return;
    }
    var current = parseInt(group.getAttribute('data-vote-state'), 10) || 0;
    var clicked = upBtn ? 1 : -1;
    applyVoteState(group, current === clicked ? 0 : clicked);
  });
})();