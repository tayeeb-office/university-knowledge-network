/**
 * Global Header Search (includes/header.php's <form class="ukn-header__search">)
 * — the single search box shown on every page, reflowed to a full-width
 * second row on mobile purely by assets/css/responsive.css (same one
 * <form>/<input>, no separate mobile search markup or system).
 *
 * The form is a plain native GET form (action="index.php", a hidden
 * page=search field, and name="q") — submitting it already navigates to
 * index.php?page=search&q=... with zero JS required for the normal case.
 * This file only adds the one thing plain HTML can't: rejecting an empty/
 * whitespace-only query instead of navigating to a pointless search, and
 * trimming stray leading/trailing whitespace so "  Python  " searches the
 * same as "Python". No autocomplete, no suggestions — docs/ui's Search
 * Results screen doesn't show either.
 */
(function () {
  'use strict';

  document.querySelectorAll('.ukn-header__search').forEach(function (form) {
    var input = form.querySelector('input[name="q"]');
    if (!input) {
      return;
    }

    form.addEventListener('submit', function (event) {
      var value = input.value.trim();
      if (!value) {
        event.preventDefault();
        input.classList.add('is-invalid');
        input.focus();
        return;
      }
      input.classList.remove('is-invalid');
      input.value = value;
    });

    input.addEventListener('input', function () {
      input.classList.remove('is-invalid');
    });
  });
})();
