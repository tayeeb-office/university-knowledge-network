(function () {
  'use strict';
  var feed = document.querySelector('[data-feed]');
  if (!feed) {
    return;
  }
  var list = feed.querySelector('[data-feed-list]');
  var moreWrap = feed.querySelector('[data-feed-more]');
  var loadMoreBtn = feed.querySelector('[data-load-more]');
  var endNote = feed.querySelector('[data-feed-end]');
  var tabs = Array.prototype.slice.call(feed.querySelectorAll('[data-feed-tab]'));
  function directPostCards(container) {
    return container ? Array.prototype.slice.call(container.children).filter(function (el) {
      return el.classList.contains('ukn-post-card');
    }) : [];
  }

  var originalListOrder = directPostCards(list);
  var originalMoreOrder = directPostCards(moreWrap);
  function votesOf(card) {
    return parseInt(card.getAttribute('data-votes'), 10) || 0;
  }
  function applyOrder(container, cards) {
    cards.forEach(function (card) {
      container.appendChild(card);
    });
  }

  function revealRemaining() {
    if (moreWrap && moreWrap.hidden) {
      moreWrap.hidden = false;
    }
    if (loadMoreBtn) {
      loadMoreBtn.hidden = true;
    }
    if (endNote) {
      endNote.hidden = false;
    }
  }
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', revealRemaining);
  }
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      if (tab.classList.contains('active')) {
        return;
      }

      tabs.forEach(function (t) {
        t.classList.remove('active');
        t.setAttribute('aria-pressed', 'false');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-pressed', 'true');
      var mode = tab.getAttribute('data-feed-tab');
      if (mode === 'following') {
        revealRemaining();
      }
      [list, moreWrap].forEach(function (container) {
        if (!container) {
          return;
        }
        directPostCards(container).forEach(function (card) {
          card.hidden = mode === 'following' && card.getAttribute('data-following') !== 'true';
        });
      });
      if (mode === 'popular') {
        applyOrder(list, originalListOrder.slice().sort(function (a, b) { return votesOf(b) - votesOf(a); }));
        if (moreWrap) {
          applyOrder(moreWrap, originalMoreOrder.slice().sort(function (a, b) { return votesOf(b) - votesOf(a); }));
        }
      } else {
        applyOrder(list, originalListOrder);
        if (moreWrap) {
          applyOrder(moreWrap, originalMoreOrder);
        }
      }
    });
  });
})();