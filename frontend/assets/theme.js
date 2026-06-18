/* avocloud theme controller — light/dark toggle, persisted in localStorage.
   The early FOUC guard (applies stored theme before paint) is emitted inline
   by partials/head.php. This file wires up the toggle button(s). */
(function () {
  'use strict';

  var STORAGE_KEY = 'avo-theme';

  function current() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
  }

  function apply(theme) {
    var root = document.documentElement;
    if (theme === 'light') {
      root.classList.remove('dark');
    } else {
      root.classList.add('dark');
    }
    try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
    syncButtons();
  }

  function toggle() {
    apply(current() === 'dark' ? 'light' : 'dark');
  }

  function syncButtons() {
    var isDark = current() === 'dark';
    document.querySelectorAll('[data-avo-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-pressed', String(isDark));
      btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      btn.setAttribute('title', isDark ? 'Light mode' : 'Dark mode');
    });
  }

  // expose for inline onclick or programmatic use
  window.avoTheme = { toggle: toggle, apply: apply, current: current };

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-avo-theme-toggle]').forEach(function (btn) {
      btn.addEventListener('click', toggle);
    });
    syncButtons();
  });
})();
