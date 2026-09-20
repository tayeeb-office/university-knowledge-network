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