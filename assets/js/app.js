/**
 * Application entry point — loaded once by every page, after Bootstrap's
 * JS bundle and before the assets/js/core/*.js shell scripts.
 *
 * Establishes a single shared namespace so feature scripts (assets/js/core,
 * assets/js/components, assets/js/pages, as they get built) can attach to
 * `window.UKN` instead of creating their own globals.
 */
window.UKN = window.UKN || {};
