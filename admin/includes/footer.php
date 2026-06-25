<?php
/**
 * Admin Panel — Page Footer
 * Closes .main-area, .admin-layout, injects scripts.
 *
 * $extraScripts: Additional <script> tags pages can inject (e.g., Chart.js loader)
 */
$extraScripts ??= '';
?>
    </main><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.admin-layout -->

<!-- Admin JS -->
<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>

<?= $extraScripts ?>

</body>
</html>
