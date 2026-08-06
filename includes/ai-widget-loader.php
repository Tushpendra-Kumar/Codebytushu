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
        // Inject CSS (non-blocking)
        var link = document.createElement('link');
        link.rel  = 'stylesheet';
        link.type = 'text/css';
        link.href = '/ai-widget/dist/ai-widget.css';
        document.head.appendChild(link);

        // Inject JS
        var script = document.createElement('script');
        script.src = '/ai-widget/dist/ai-widget.js';
        document.body.appendChild(script);
    }

    // Load 1.5s after page fully loads — zero impact on LCP / FID / CLS
    if (document.readyState === 'complete') {
        setTimeout(loadCbtWidget, 1500);
    } else {
        window.addEventListener('load', function() {
            setTimeout(loadCbtWidget, 1500);
        });
    }
})();
</script>
<?php endif; ?>
