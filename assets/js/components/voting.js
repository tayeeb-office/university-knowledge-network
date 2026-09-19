/**
 * POST upvote/downvote toggle — drives the .ukn-vote-rail control in
 * components/post-card.php, on every page that renders a post card
 * (Home, Post Details, My Posts, Saved Posts, profiles, Skill Details),
 * via one delegated listener.
 *
 * Voting is a POST-only feature. Comments have no voting at all — see
 * assets/js/components/comments.js — so this deliberately matches only
 * .ukn-vote-rail and can never bind to anything inside a comment.
 *
 * Frontend-only mock state: clicking just adjusts the displayed number
 * (relative to the server-rendered data-vote-base) and toggles which
 * button looks active. Nothing is persisted or sent anywhere — there is
 * no real voting.
 */
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
