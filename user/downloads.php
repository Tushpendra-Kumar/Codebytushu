<?php
/**
 * CodeByTushu — User Downloads
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();
Auth::requireLogin();

$pdo  = db();
$user = Auth::user(true);

$error   = '';
$success = '';
$activeTab = 'downloads';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
    <!-- Main Content -->
    <main>
      <div class="card">
        <div class="card-header"><h2 class="card-title">My Downloads</h2></div>
        <div class="card-body">
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <h3>No downloads</h3>
            <p style="margin-top:8px;">Purchased source codes and digital assets will be available here.</p>
          </div>
        </div>
      </div>
    </main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
