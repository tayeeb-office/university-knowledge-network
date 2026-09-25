(function () {
  'use strict';
  // Post card Share menu (components/post-card.php). Social links are plain <a> links built on
  // the server; this file only handles Copy link and the native share sheet. Delegated, so it
  // works for every card on the page, including Post Details.
  function toast(message, type) {
    if (window.UKN && window.UKN.showToast) {
      window.UKN.showToast(message, type);
    }
  }

  // Fallback for pages not served from a secure context (no Clipboard API).
  function copyWithSelection(text) {
    var field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.top = '-1000px';
    field.style.opacity = '0';
    document.body.appendChild(field);
    field.select();
    var copied = false;
    try {
      copied = document.execCommand('copy');
    } catch (e) {
      copied = false;
    }
    field.remove();
    return copied;
  }

  function reportCopy(copied) {
    if (copied) {
      toast('Link copied.', 'success');
    } else {
      toast('Could not copy the link. Please copy it from the address bar.', 'danger');
    }
  }

  function copyLink(url) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).then(function () {
        reportCopy(true);
      }, function () {
        reportCopy(copyWithSelection(url));
      });
      return;
    }
    reportCopy(copyWithSelection(url));
  }

  // "More options…" only where the browser has a native share sheet.
  document.addEventListener('show.bs.dropdown', function (event) {
    var dropdown = event.target.closest('.dropdown');
    var nativeItem = dropdown ? dropdown.querySelector('[data-share-native-item]') : null;
    if (nativeItem) {
      nativeItem.hidden = typeof navigator.share !== 'function';
    }
  });

  document.addEventListener('click', function (event) {
    var copyButton = event.target.closest('[data-share-copy]');
    var nativeButton = event.target.closest('[data-share-native]');
    if (!copyButton && !nativeButton) {
      return;
    }
    var menu = (copyButton || nativeButton).closest('[data-share-menu]');
    if (!menu) {
      return;
    }
    var url = menu.getAttribute('data-share-url') || '';
    if (copyButton) {
      copyLink(url);
      return;
    }
    if (typeof navigator.share !== 'function') {
      return;
    }
    // Called straight from the click so the browser treats it as a user gesture.
    navigator.share({ title: menu.getAttribute('data-share-title') || '', url: url }).catch(function (error) {
      if (!error || error.name !== 'AbortError') {
        toast('Sharing is not available right now. Use Copy link instead.', 'danger');
      }
    });
  });
})();
