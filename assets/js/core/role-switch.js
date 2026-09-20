(function () {
  'use strict';
  var STORAGE_KEY = 'ukn_active_role';
  var VALID_ROLES = ['learner', 'mentor'];
  var OTHER_ROLE = { learner: 'mentor', mentor: 'learner' };
  function getStoredRole() {
    try {
      var stored = localStorage.getItem(STORAGE_KEY);
      return VALID_ROLES.indexOf(stored) !== -1 ? stored : null;
    } catch (e) {
      return null;
    }
  }
  function storeRole(role) {
    try {
      localStorage.setItem(STORAGE_KEY, role);
    } catch (e) {
    }
  }
  function label(role) {
    return role.charAt(0).toUpperCase() + role.slice(1);
  }
  function applyRole(role) {
    if (VALID_ROLES.indexOf(role) === -1) {
      return;
    }

    document.querySelectorAll('[data-role="learner"], [data-role="mentor"]').forEach(function (el) {
      el.hidden = el.getAttribute('data-role') !== role;
    });
    document.querySelectorAll('[data-role-label]').forEach(function (el) {
      el.textContent = label(role);
    });
    document.querySelectorAll('[data-role-switch-label]').forEach(function (el) {
      el.textContent = 'Switch to ' + label(OTHER_ROLE[role]);
    });
    document.querySelectorAll('[data-role-dashboard-link]').forEach(function (el) {
      var href = el.getAttribute('data-dashboard-href-' + role);
      if (href) {
        el.setAttribute('href', href);
      }
    });
    storeRole(role);
    document.dispatchEvent(new CustomEvent('ukn:rolechange', { detail: { role: role } }));
  }
  function init() {
    if (!document.querySelector('[data-role]')) {
      return;
    }
    applyRole(getStoredRole() || 'learner');

    document.addEventListener('click', function (event) {
      var choice = event.target.closest('[data-role-choice]');
      if (!choice) {
        return;
      }
      applyRole(choice.getAttribute('data-role-choice'));
      var modalEl = choice.closest('.modal');
      if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        var instance = window.bootstrap.Modal.getInstance(modalEl);
        if (instance) {
          instance.hide();
        }
      }
    });
  }
  document.addEventListener('DOMContentLoaded', init);
  window.UKN = window.UKN || {};
  window.UKN.role = { apply: applyRole, current: function () { return getStoredRole() || 'learner'; } };
})();