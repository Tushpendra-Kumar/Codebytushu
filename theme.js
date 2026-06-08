/**
 * theme.js — Global dark/light mode that persists across ALL pages
 * Include this script in every page's <head> to prevent flash.
 */
(function () {
    const saved = localStorage.getItem('cbt-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
})();
