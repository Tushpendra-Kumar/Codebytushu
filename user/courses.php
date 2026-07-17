<?php
/**
 * CodeByTushu — User Courses
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
$activeTab = 'courses';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
    <!-- Main Content -->
    <main>
      <div class="card">
        <div class="card-header"><h2 class="card-title">My Courses</h2></div>
        <div class="card-body">
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <h3>No courses found</h3>
            <p style="margin-top:8px;">You haven't enrolled in any premium courses yet.</p>
            <a href="/courses/" class="btn-save" style="margin-top:20px;text-decoration:none;">Explore Courses</a>
          </div>
        </div>
      </div>
    </main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
