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
        // Admin protection
        if ($user['email'] === 'tushpendrakumar@gmail.com') {
            if (isAjax()) jsonError("Admin account cannot be deleted.", 403);
            die("Admin account cannot be deleted.");
        }
        
        $userId = $user['id'];
        
        // Delete avatar if it is a local file
        if (!empty($user['profile_image']) && strpos($user['profile_image'], 'http') !== 0) {
            $avatarPath = __DIR__ . '/../' . ltrim($user['profile_image'], '/');
            if (file_exists($avatarPath) && is_file($avatarPath)) {
                @unlink($avatarPath);
            }
        }
        
        // Delete user (cascade will handle related data if DB is setup with ON DELETE CASCADE)
        // Ensure session and cookies are destroyed
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
        Auth::logout();
        
        if (isAjax()) {
            jsonSuccess();
        }
        
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
            /* Modals */
            .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 2000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); animation: fadeIn .2s; }
            .modal-content { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 30px; width: 90%; max-width: 450px; box-shadow: 0 10px 40px rgba(0,0,0,0.7); }
            .btn-danger { padding: 10px 20px; background: var(--danger); border: none; color: #fff; border-radius: var(--radius); cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; transition: opacity .2s; }
            .btn-danger:hover { opacity: 0.9; }
            .btn-cancel { padding: 10px 20px; background: transparent; border: 1px solid var(--border); color: var(--text); border-radius: var(--radius); cursor: pointer; font-family: 'Poppins', sans-serif; transition: background .2s; }
            .btn-cancel:hover { background: rgba(255,255,255,0.05); }
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

          <?php if ($user['email'] !== 'tushpendrakumar@gmail.com'): ?>
          <!-- Danger Zone -->
          <div class="settings-section">
            <h3 style="color: var(--danger);">Danger Zone</h3>
            <p>Irreversible and destructive actions for your account.</p>
            
            <div class="setting-item">
              <div class="setting-info">
                <h4>Delete Account</h4>
                <p>Permanently remove your account and all associated data.</p>
              </div>
              <button type="button" class="btn-danger" id="triggerDeleteModalBtn">Delete Account</button>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
      
      <div id="settingsToast" class="settings-toast">Settings saved successfully</div>

      <?php if ($user['email'] !== 'tushpendrakumar@gmail.com'): ?>
      <!-- Delete Confirmation Modal -->
      <div id="deleteModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
          <h2 style="color:var(--danger); margin-bottom:15px; font-size:1.5rem;">Delete Your Account?</h2>
          <p style="color:var(--muted); font-size:14px; margin-bottom:25px; line-height:1.6;">
            This action is permanent and cannot be undone. All your account data, profile, orders, downloads, certificates and associated information will be permanently deleted.
          </p>
          <div style="display:flex; gap:15px; justify-content:flex-end;">
            <button id="cancelDeleteBtn" class="btn-cancel">Cancel</button>
            <button id="confirmDeleteBtn" class="btn-danger">Yes, Delete My Account</button>
          </div>
        </div>
      </div>

      <!-- Delete Success Modal -->
      <div id="deleteSuccessModal" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="text-align:center;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:64px; height:64px; color:#22c55e; margin:0 auto 15px auto;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <h2 style="margin-bottom:15px; font-size:1.5rem;">Account Deleted Successfully</h2>
          <p style="color:var(--muted); font-size:14px; margin-bottom:25px;">
            Your account has been permanently deleted. Thank you for being a part of CodeByTushu.
          </p>
          <button onclick="window.location.href='/'" class="btn-save" style="width:100%; justify-content:center;">Go to Home</button>
        </div>
      </div>
      
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          const triggerBtn = document.getElementById('triggerDeleteModalBtn');
          const deleteModal = document.getElementById('deleteModal');
          const cancelBtn = document.getElementById('cancelDeleteBtn');
          const confirmBtn = document.getElementById('confirmDeleteBtn');
          const successModal = document.getElementById('deleteSuccessModal');
          
          if (triggerBtn && deleteModal) {
            triggerBtn.addEventListener('click', () => {
              deleteModal.style.display = 'flex';
            });
          }
          
          if (cancelBtn && deleteModal) {
            cancelBtn.addEventListener('click', () => {
              deleteModal.style.display = 'none';
              if (typeof showSaveToast === 'function') {
                  showSaveToast('Thank you for staying with CodeByTushu ❤️');
              }
            });
          }
          
          if (confirmBtn) {
            confirmBtn.addEventListener('click', async (e) => {
              const btn = e.target;
              btn.innerHTML = 'Deleting...';
              btn.disabled = true;
              
              const formData = new FormData();
              formData.append('_sub', 'delete_account');
              formData.append('csrf_token', '<?= e(csrf_token()) ?>');
              
              try {
                const res = await fetch(window.location.href, {
                  method: 'POST',
                  headers: { 'X-Requested-With': 'XMLHttpRequest' },
                  body: formData
                });
                
                let data = {};
                const text = await res.text();
                try {
                  data = JSON.parse(text);
                } catch(err) {
                  console.error('JSON Parse error on response:', text);
                  // If it redirect to home, we might get HTML back.
                  if (res.redirected || text.includes('<!DOCTYPE html>')) {
                      data = { success: true };
                  } else {
                      data = { success: false, error: 'Invalid response from server' };
                  }
                }
                
                if (data.success) {
                  deleteModal.style.display = 'none';
                  successModal.style.display = 'flex';
                } else {
                  alert(data.error || 'Something went wrong');
                  btn.innerHTML = 'Yes, Delete My Account';
                  btn.disabled = false;
                }
              } catch(error) {
                console.error('Fetch error:', error);
                alert('A network error occurred.');
                btn.innerHTML = 'Yes, Delete My Account';
                btn.disabled = false;
              }
            });
          }
        });
      </script>
      <?php endif; ?>

    </main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
