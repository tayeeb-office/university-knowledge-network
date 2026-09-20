(function () {
  'use strict';
  var STORAGE_KEY = 'ukn-theme';
  var root = document.documentElement;
  function getStoredTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY);
    } catch (e) {
      return null;
    }
  }
  function storeTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (e) {
    }
  }
  function preferredTheme() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light';
  }

  function currentTheme() {
    return root.getAttribute('data-bs-theme') || getStoredTheme() || preferredTheme();
  }
  function updateToggleControls(theme) {
    document.querySelectorAll('[data-theme-label]').forEach(function (el) {
      el.textContent = 'Theme: ' + (theme === 'dark' ? 'Dark' : 'Light');
    });
    document.querySelectorAll('#uknThemeToggle').forEach(function (btn) {
      btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    });
  }
  function applyTheme(theme) {
    root.setAttribute('data-bs-theme', theme);
    storeTheme(theme);
    updateToggleControls(theme);
    document.dispatchEvent(new CustomEvent('ukn:themechange', { detail: { theme: theme } }));
  }
  function toggleTheme() {
    applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
  }
  function init() {
    applyTheme(currentTheme());

    document.addEventListener('click', function (event) {
      if (event.target.closest('#uknThemeToggle')) {
        toggleTheme();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', init);
  window.UKN = window.UKN || {};
  window.UKN.theme = { toggle: toggleTheme, set: applyTheme, current: currentTheme };
})();