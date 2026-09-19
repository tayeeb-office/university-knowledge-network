/**
 * Theme control — Light / Dark.
 *
 * Driven entirely by Bootstrap 5.3's color-mode attribute
 * (data-bs-theme on <html>), which every foundation CSS file already keys
 * off (assets/css/theme.css defines the light/dark token overrides, and
 * every component reads --ukn-* custom properties rather than hardcoded
 * colors) — so flipping this one attribute is enough to update the
 * header brand, dropdown surfaces, text, borders and icons together. This
 * is the single place theme state is read, applied and persisted; nothing
 * else in the app should duplicate this logic.
 *
 * The "University" brand word stays the fixed accent orange in both
 * modes (assets/css/layout.css), independent of this toggle.
 */
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
      /* private browsing / storage disabled — theme just won't persist */
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
