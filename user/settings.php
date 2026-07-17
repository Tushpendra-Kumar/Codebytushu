<?php
/**
 * CodeByTushu — User Settings
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
$activeTab = 'settings';

if (isPost()) {
    requireCsrf();
    $sub = post('_sub');

    if ($sub === 'delete_account') {
        $userId = $user['id'];
        
        // Delete avatar if it is a local file
        if (!empty($user['profile_image']) && strpos($user['profile_image'], 'http') !== 0) {
            $avatarPath = __DIR__ . '/../' . ltrim($user['profile_image'], '/');
            if (file_exists($avatarPath) && is_file($avatarPath)) {
                @unlink($avatarPath);
            }
        }
        
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
        Auth::logout();
        header("Location: /");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
    <!-- Main Content -->
    <main>
      <div class="card">
        <div class="card-header"><h2 class="card-title">Account Settings</h2></div>
        <div class="card-body">
          
          <style>
            .settings-section { margin-bottom: 30px; border-bottom: 1px solid var(--border); padding-bottom: 30px; }
            .settings-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
            .settings-section h3 { font-size: 1.2rem; font-weight: 600; margin-bottom: 5px; color: var(--text-primary); }
            .settings-section > p { color: var(--muted); font-size: 0.9rem; margin-bottom: 20px; line-height: 1.4; }
            .setting-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-top: 1px solid rgba(255,255,255,0.05); }
            .setting-item:first-of-type { border-top: none; }
            .setting-info h4 { font-size: 1rem; font-weight: 500; margin-bottom: 4px; }
            .setting-info p { font-size: 0.85rem; color: var(--muted); margin: 0; }
            /* Toggle Switch */
            .switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
            .switch input { opacity: 0; width: 0; height: 0; }
            .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); transition: .4s; border-radius: 24px; }
            [data-theme="light"] .slider { background-color: rgba(0,0,0,0.1); }
            .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .slider { background-color: var(--accent); }
            input:checked + .slider:before { transform: translateX(20px); }
            /* Toast Notification */
            .settings-toast { position: fixed; bottom: 20px; right: 20px; background: #22c55e; color: #fff; padding: 12px 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); opacity: 0; transform: translateY(20px); transition: all 0.3s ease; z-index: 1000; pointer-events: none; }
            .settings-toast.show { opacity: 1; transform: translateY(0); }
          </style>

          <!-- Notifications -->
          <div class="settings-section">
            <h3>Notifications</h3>
            <p>Control what emails and alerts you receive from us.</p>
            
            <div class="setting-item">
              <div class="setting-info">
                <h4>Full Notifications</h4>
                <p>Master toggle for all email notifications.</p>
              </div>
              <label class="switch">
                <input type="checkbox" id="masterNotif" checked onchange="toggleMasterNotif(this)">
                <span class="slider"></span>
              </label>
            </div>

            <div class="setting-item">
              <div class="setting-info">
                <h4>Product Updates</h4>
                <p>Receive emails about new features and major platform updates.</p>
              </div>
              <label class="switch">
                <input type="checkbox" class="child-notif" checked onchange="showSaveToast('Notification preferences saved')">
                <span class="slider"></span>
              </label>
            </div>
            
            <div class="setting-item">
              <div class="setting-info">
                <h4>Promotions & Offers</h4>
                <p>Get notified about special discounts and new courses.</p>
              </div>
              <label class="switch">
                <input type="checkbox" class="child-notif" checked onchange="showSaveToast('Notification preferences saved')">
                <span class="slider"></span>
              </label>
            </div>
            
            <div class="setting-item">
              <div class="setting-info">
                <h4>Order Receipts</h4>
                <p>Email me a receipt whenever I make a purchase.</p>
              </div>
              <label class="switch">
                <input type="checkbox" class="child-notif" checked onchange="showSaveToast('Notification preferences saved')">
                <span class="slider"></span>
              </label>
            </div>
          </div>

          <!-- Danger Zone -->
          <div class="settings-section">
            <h3 style="color: var(--danger);">Danger Zone</h3>
            <p>Irreversible and destructive actions for your account.</p>
            
            <div class="setting-item">
              <div class="setting-info">
                <h4>Delete Account</h4>
                <p>Permanently remove your account and all associated data.</p>
              </div>
              <form id="delete-account-form" method="POST" style="display: none;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="_sub" value="delete_account">
              </form>
              <button type="button" class="btn-save" style="background: transparent; color: var(--danger); border: 1px solid var(--danger); flex-shrink: 0;" onclick="if(confirm('Are you sure you want to permanently delete your account? This action cannot be undone.')) document.getElementById('delete-account-form').submit();">Delete Account</button>
            </div>
          </div>

        </div>
      </div>
      
      <div id="settingsToast" class="settings-toast">Settings saved successfully</div>

    </main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
