(function () {
  'use strict';
  function ensureContainer() {
    var container = document.getElementById('uknToastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'uknToastContainer';
      container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      document.body.appendChild(container);
    }
    return container;
  }
  function showToast(message, type) {
    if (typeof bootstrap === 'undefined' || !bootstrap.Toast) {
      return;
    }
    type = (type === 'danger' || type === 'info') ? type : 'success';

    var container = ensureContainer();
    var icon = type === 'danger' ? 'error' : (type === 'info' ? 'info' : 'check_circle');
    var toastEl = document.createElement('div');
    toastEl.className = 'toast ukn-toast-' + type;
    toastEl.setAttribute('role', 'status');
    toastEl.setAttribute('aria-live', 'polite');
    toastEl.setAttribute('aria-atomic', 'true');

    var body = document.createElement('div');
    body.className = 'toast-body';
    var iconEl = document.createElement('span');
    iconEl.className = 'ms';
    iconEl.setAttribute('aria-hidden', 'true');
    iconEl.textContent = icon;
    var textEl = document.createElement('span');
    textEl.textContent = message;
    body.appendChild(iconEl);
    body.appendChild(textEl);
    toastEl.appendChild(body);
    container.appendChild(toastEl);
    var toast = new bootstrap.Toast(toastEl, { delay: 3200 });
    toastEl.addEventListener('hidden.bs.toast', function () {
      toastEl.remove();
    });
    toast.show();
  }
  window.UKN = window.UKN || {};
  window.UKN.showToast = showToast;
})();