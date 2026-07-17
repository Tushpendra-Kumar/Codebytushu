<?php
/**
 * CodeByTushu — User Certificates
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
$activeTab = 'certificates';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
    <!-- Main Content -->
    <main>
      <div class="card">
        <div class="card-header"><h2 class="card-title">My Certificates</h2></div>
        <div class="card-body">
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <h3>No certificates earned</h3>
            <p style="margin-top:8px;">Complete courses to earn verified certificates.</p>
          </div>
        </div>
      </div>
    </main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
