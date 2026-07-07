/**
 * theme.js — Global dark/light mode that persists across ALL pages
 * Include this script in every page's <head> to prevent flash.
 *
 * SHARED UTILITY: Used by index.html, all Leetcode pages, video-editing.
 * Located at root (/theme.js) so all sub-pages can load it via absolute path.
 *
 * PHP MIGRATION NOTE:
 * When migrating to PHP, this script stays at /theme.js and is included
 * via the shared PHP head file (e.g., includes/head.php) like so:
 *   <script src="/theme.js"></script>
 * No changes to the script content itself are needed.
 */
(function () {
    const saved = localStorage.getItem('cbt-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
})();
