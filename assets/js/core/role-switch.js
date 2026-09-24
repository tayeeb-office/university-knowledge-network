(function () {
  'use strict';
  // The active role is server state (PHP session, set via backend/auth/switch-role.php).
  // The page is already rendered for it, so this module only reports it; the role-switch
  // modal submits a normal POST form.
  var LEGACY_STORAGE_KEY = 'ukn_active_role';
  function init() {
    // Earlier builds kept the role in localStorage; it is no longer read, so drop it.
    try {
      localStorage.removeItem(LEGACY_STORAGE_KEY);
    } catch (e) {
    }
  }
  function current() {
    return document.body ? document.body.getAttribute('data-active-role') : null;
  }
  document.addEventListener('DOMContentLoaded', init);
  window.UKN = window.UKN || {};
  window.UKN.role = { current: current };
})();
