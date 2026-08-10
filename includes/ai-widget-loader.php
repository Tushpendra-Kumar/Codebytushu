<?php
/**
 * CodeByTushu — AI Widget Lazy Loader
 *
 * Include this file at the bottom of every page (before </body>) to lazily
 * load the React AI chat widget without impacting Core Web Vitals.
 *
 * ⚠️  DO NOT include on Admin Panel pages (/admin/*).
 *
 * Usage:
 *   <?php require_once __DIR__ . '/includes/ai-widget-loader.php'; ?>
 */

// Safety: Never show the widget on Admin Panel pages
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isAdminPage = (strpos($currentUri, '/admin') === 0);

if (!$isAdminPage):
?>
<!-- CodeByTushu AI Widget Mount Point -->
<div id="cbt-ai-widget"></div>
<script>
(function() {
    'use strict';
    // Prevent double-loading if this script is accidentally included twice
    if (window.__cbtWidgetLoaded) return;
    window.__cbtWidgetLoaded = true;

    function loadCbtWidget() {
        // Handle local XAMPP path (localhost/Codebytushu) vs Production (codebytushu.com)
        var basePath = window.location.pathname.startsWith('/Codebytushu') ? '/Codebytushu' : '';
        window.cbtBasePath = basePath;

        // Inject CSS (non-blocking)
        var link = document.createElement('link');
        link.rel  = 'stylesheet';
        link.type = 'text/css';
        link.href = basePath + '/ai-widget/dist/ai-widget.css';
        document.head.appendChild(link);

        // Inject JS
        var script = document.createElement('script');
        script.src = basePath + '/ai-widget/dist/ai-widget.js';
        document.body.appendChild(script);
    }

    // Load immediately when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadCbtWidget);
    } else {
        loadCbtWidget();
    }
})();
</script>
<?php endif; ?>

