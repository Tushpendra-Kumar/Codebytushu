<?php
/**
 * ════════════════════════════════════════════════════════════════
 * CodeByTushu Admin Panel — Page Template
 * ════════════════════════════════════════════════════════════════
 *
 * COPY THIS FILE when creating any new admin page.
 * Replace all [PLACEHOLDER] values with real content.
 *
 * ── Required Variables ───────────────────────────────────────────
 *  $adminSection  string  Section name shown in header title
 *  $adminTitle    string  <title> tag content
 *  $breadcrumbs   array   [['label'=>'','url'=>''], ...]
 *
 * ── Optional Variables ───────────────────────────────────────────
 *  $extraHead     string  Extra <link>/<meta> tags in <head>
 *  $extraScripts  string  Extra <script> tags before </body>
 *
 * ── Example Usage ────────────────────────────────────────────────
 *   See /admin/index.php for a complete working example.
 * ════════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

// 1. Page metadata (set BEFORE auth_check.php)
$adminSection = 'Section Name';
$adminTitle   = 'Page Title — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Section Name', 'url' => '/admin/section.php'],
    ['label' => 'Current Page'],  // No URL = active/current
];

// 2. Optional: extra head assets
// $extraHead = '<link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/extra.css">';

// 3. Auth guard (boots session, verifies admin role, sets $user)
require_once __DIR__ . '/includes/auth_check.php';

// 4. Page-specific data queries
$pdo = db();
// ... your queries here ...

// 5. Optional: extra scripts
// $extraScripts = '<script src="/admin/assets/extra.js"></script>';
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<body>
<div class="admin-layout">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-area">

    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>

    <main class="page-content">

      <!-- Page header -->
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">[Page Title]</h1>
          <p class="page-subtitle">[Optional subtitle or description]</p>
        </div>
        <div class="page-header-actions">
          <button class="btn btn-primary" onclick="Modal.open('createModal')">
            + Add New
          </button>
        </div>
      </div>

      <!-- ── Your content here ─────────────────────────────── -->

      <!-- Example: Card with table -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">[Content Title]</div>
          <div class="card-actions">
            <!-- filter selects, buttons -->
          </div>
        </div>
        <div class="table-toolbar">
          <div class="search-input-wrap">
            <span class="si">🔍</span>
            <input type="text" placeholder="Search…">
          </div>
        </div>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Column 1</th>
                <th>Column 2</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Value</td>
                <td><span class="badge badge-success">Active</span></td>
                <td style="text-align:right;">
                  <div style="display:flex;gap:6px;justify-content:flex-end;">
                    <button class="btn btn-ghost btn-sm btn-icon" title="Edit">✏️</button>
                    <button class="btn btn-danger btn-sm btn-icon" title="Delete">🗑️</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          <span style="font-size:12px;color:var(--text-muted);">Showing 1–10 of 100</span>
          <div class="pagination">
            <a href="?page=1" class="page-btn">←</a>
            <a href="?page=1" class="page-btn active">1</a>
            <a href="?page=2" class="page-btn">2</a>
            <a href="?page=2" class="page-btn">→</a>
          </div>
        </div>
      </div>

      <!-- ── End content ─────────────────────────────────────── -->

    </main><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.admin-layout -->

<!-- Admin JS (always last) -->
<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<?= $extraScripts ?? '' ?>

<!-- Page-specific inline scripts -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Your page-specific JavaScript here
});
</script>

</body>
</html>
