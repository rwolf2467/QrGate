/* avocloud theme controller — light/dark toggle, persisted in localStorage.
   The early FOUC guard (applies stored theme before paint) is emitted inline
   by partials/head.php. This file wires up the toggle button(s). */
(function () {
  'use strict';

  var STORAGE_KEY = 'avo-theme';
  // keep in sync with avocloud.css role tokens (--avo-bg)
  var BG = { dark: '#0B0B0B', light: '#F2EFE6' };

  function current() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
  }

  function stored() {
    try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
  }

  function systemDark() {
    return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
  }

  function syncMeta() {
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', current() === 'dark' ? BG.dark : BG.light);
  }

  // Apply a theme to the DOM. persist=false means "follow system" — don't
  // write a choice, so the page keeps tracking the OS preference.
  function apply(theme, persist) {
    var root = document.documentElement;
    if (theme === 'light') {
      root.classList.remove('dark');
    } else {
      root.classList.add('dark');
    }
    if (persist !== false) {
      try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
    }
    syncButtons();
    syncMeta();
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

  // While the user hasn't made an explicit choice, track live OS changes.
  if (window.matchMedia) {
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    var onSysChange = function (e) {
      if (!stored()) apply(e.matches ? 'dark' : 'light', false);
    };
    if (mq.addEventListener) mq.addEventListener('change', onSysChange);
    else if (mq.addListener) mq.addListener(onSysChange);
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-avo-theme-toggle]').forEach(function (btn) {
      btn.addEventListener('click', toggle);
    });
    syncButtons();
    syncMeta();
  });
})();
