<?php
/**
 * CodeByTushu — User Dashboard
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
$activeTab = $_GET['tab'] ?? 'profile';

if (isPost()) {
    requireCsrf();
    $sub = post('_sub');

    if ($sub === 'profile') {
        $activeTab = 'profile';
        $fullName = sanitize(post('full_name'));
        
        // Handle profile image upload
        $profileImage = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            
            if (in_array($file['type'], $allowedTypes)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $ext = ($file['type'] === 'image/png') ? 'png' : (($file['type'] === 'image/webp') ? 'webp' : 'jpg');
                }
                
                $uploadDir = __DIR__ . '/../uploads/images/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    // Delete old local avatar if exists
                    if (!empty($user['profile_image']) && strpos($user['profile_image'], '/uploads/images/avatars/') === 0) {
                        $oldPath = __DIR__ . '/..' . $user['profile_image'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $profileImage = '/uploads/images/avatars/' . $filename;
                } else {
                    $error = 'Failed to save uploaded image.';
                }
            } else {
                $error = 'Invalid file type. Only JPG, PNG, and WebP are allowed.';
            }
        }
        if (!$error) {
            $pdo->prepare('UPDATE users SET full_name=?, profile_image=?, updated_at=NOW() WHERE id=?')
                ->execute([$fullName, $profileImage, $user['id']]);
            $success = 'Profile updated successfully.';
            $user = Auth::user(true); // refresh user data
        }
    } elseif ($sub === 'delete_account') {
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
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>User Dashboard — CodeByTushu</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="/favicon.ico?v=6" sizes="any">
  <meta name="theme-color" content="#ffc400">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #000000; --card: #111111; --border: rgba(255,196,0,.25); --accent: #ffc400;
      --text: #ffffff; --muted: #aaaaaa; --input: #161616; --radius: 12px; --danger: #ff4d4d;
      --danger-bg: rgba(255,77,77,0.1);
    }
    body {
      font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text);
      min-height: 100vh; overflow-x: hidden;
    }
    body::before {
      content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background: radial-gradient(ellipse 60% 50% at 20% 30%, rgba(255,196,0,.08) 0%, transparent 60%);
    }
    .layout {
      position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 40px 20px;
    }
    .top-bar {
      display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px;
      padding-bottom: 20px; border-bottom: 1px solid var(--border);
    }
    .top-bar a { text-decoration: none; color: var(--muted); font-size: 14px; font-weight: 500; transition: color 0.2s; }
    .top-bar a:hover { color: var(--accent); }
    .logo { font-size: 24px; font-weight: 800; text-decoration: none; color: var(--text); }
    .logo .gold { color: var(--accent); }

    .profile-grid { display: grid; grid-template-columns: 260px 1fr; gap: 32px; align-items: start; }

    /* Sidebar */
    .sidebar {
      background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 16px;
      position: sticky; top: 40px;
    }
    .nav-item {
      display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin-bottom: 8px;
      border-radius: 10px; color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 500;
      cursor: pointer; transition: all 0.2s ease;
    }
    .nav-item:last-child { margin-bottom: 0; }
    .nav-item:hover { background: var(--input); color: var(--text); }
    .nav-item.active { background: rgba(255,196,0,0.1); color: var(--accent); font-weight: 600; border: 1px solid rgba(255,196,0,.2); }
    .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }

    /* Content Area */
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; margin-bottom: 24px; overflow: hidden; }
    .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); }
    .card-title { font-size: 18px; font-weight: 600; }
    .card-body { padding: 24px; }
    
    .alert { padding: 14px 18px; border-radius: var(--radius); margin-bottom: 24px; font-size: 14px; font-weight: 500; }
    .alert-error { background: rgba(255,77,77,.12); border: 1px solid rgba(255,77,77,.3); color: #ff6b6b; }
    .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #22c55e; }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 8px; }
    .form-control {
      width: 100%; padding: 12px 16px; background: var(--input); border: 1px solid var(--border);
      border-radius: var(--radius); color: var(--text); font-family: 'Poppins', sans-serif;
      font-size: 14px; outline: none; transition: all .2s;
    }
    .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(255,196,0,.15); }
    .form-control[readonly] { opacity: 0.7; cursor: not-allowed; }

    .btn-save {
      padding: 12px 24px; background: var(--accent); color: #000; border: none;
      border-radius: var(--radius); font-family: 'Poppins', sans-serif; font-size: 14px;
      font-weight: 700; cursor: pointer; transition: opacity .2s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-save:hover { opacity: 0.9; }

    /* Avatar */
    .avatar-section { display: flex; align-items: center; gap: 24px; margin-bottom: 30px; }
    .avatar-img {
      width: 80px; height: 80px; border-radius: 50%; object-fit: cover;
      background: var(--accent); color: #000; font-size: 28px; font-weight: 700;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
      border: 2px solid var(--border);
    }

    /* Placeholder content for future modules */
    .empty-state {
      text-align: center; padding: 40px 20px; color: var(--muted);
    }
    .empty-state svg { width: 48px; height: 48px; color: var(--border); margin-bottom: 16px; }

    @media (max-width: 768px) {
      .profile-grid { grid-template-columns: 1fr; gap: 24px; }
      .sidebar { position: static; display: flex; overflow-x: auto; padding: 10px; gap: 10px; white-space: nowrap; }
      .nav-item { margin: 0; }
    }
  </style>
</head>
<body>

<div class="layout">
  <!-- Header -->
  <header class="top-bar">
    <a href="/" class="logo">CODE<span class="gold">BY</span>TUSHU</a>
    <div style="display:flex;gap:24px;align-items:center;">
      <?php if (Auth::isAdmin()): ?><a href="<?= SITE_URL ?>/admin/" style="color:var(--accent);">Admin Panel</a><?php endif; ?>
      <a href="/">Home</a>
      <a href="#" onclick="document.getElementById('logout-form').submit(); return false;" style="color:var(--danger);font-weight:600;">Logout</a>
      <form id="logout-form" action="/api/auth/logout.php" method="POST" style="display: none;">
          <?= csrfField() ?>
      </form>
    </div>
  </header>

  <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="profile-grid">
    <!-- Sidebar -->
    <aside class="sidebar">
      <a class="nav-item <?= $activeTab === 'profile' ? 'active' : '' ?>" data-target="profile" onclick="switchTab('profile')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Profile
      </a>
      <a class="nav-item <?= $activeTab === 'courses' ? 'active' : '' ?>" data-target="courses" onclick="switchTab('courses')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        My Courses
      </a>
      <a class="nav-item <?= $activeTab === 'orders' ? 'active' : '' ?>" data-target="orders" onclick="switchTab('orders')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        My Orders
      </a>
      <a class="nav-item <?= $activeTab === 'downloads' ? 'active' : '' ?>" data-target="downloads" onclick="switchTab('downloads')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Downloads
      </a>
      <a class="nav-item <?= $activeTab === 'certificates' ? 'active' : '' ?>" data-target="certificates" onclick="switchTab('certificates')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Certificates
      </a>
      <a class="nav-item <?= $activeTab === 'settings' ? 'active' : '' ?>" data-target="settings" onclick="switchTab('settings')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Settings
      </a>
    </aside>

    <!-- Main Content -->
    <main>
      <!-- TAB: PROFILE -->
      <div id="tab-profile" class="tab-content <?= $activeTab === 'profile' ? 'active' : '' ?>">
        <div class="card">
          <div class="card-header"><h2 class="card-title">Google Account</h2></div>
          <div class="card-body">
            <div class="avatar-section">
              <?php if (!empty($user['profile_image'])): ?>
                <img src="<?= e($user['profile_image']) ?>" class="avatar-img" alt="Profile">
              <?php else: ?>
                <div class="avatar-img"><?= strtoupper(substr($user['full_name'] ?? 'U',0,1)) ?></div>
              <?php endif; ?>
              <div>
                <h3 style="font-size:18px;font-weight:600;"><?= e($user['full_name']) ?></h3>
                <p style="color:var(--muted);font-size:14px;"><?= e($user['email']) ?></p>
              </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
              <?= csrfField() ?>
              <input type="hidden" name="_sub" value="profile">
              
              <div class="form-group">
                <label class="form-label">Profile Photo</label>
                <div style="display: flex; gap: 15px; align-items: center;">
                  <?php if (!empty($user['profile_image'])): ?>
                    <img id="avatarPreview" src="<?= e($user['profile_image']) ?>" style="width:60px; height:60px; border-radius:50%; object-fit:cover; border:2px solid var(--border);" alt="Profile">
                  <?php else: ?>
                    <div id="avatarPreview" style="width:60px; height:60px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:bold;">
                      <?= strtoupper(substr($user['full_name'] ?? 'U',0,1)) ?>
                    </div>
                  <?php endif; ?>
                  
                  <input type="file" id="profileImageInput" name="profile_image" accept="image/jpeg, image/png, image/webp" style="display:none;" onchange="previewAvatar(this)">
                  <button type="button" class="btn-save" style="background:#222; color:#fff; border:1px solid #444;" onclick="document.getElementById('profileImageInput').click();">
                    Change Photo
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Email (Managed by Google)</label>
                <input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly>
              </div>
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>">
              </div>

              <button type="submit" class="btn-save">Save Profile</button>
            </form>
          </div>
        </div>
      </div>

      <!-- TAB: COURSES -->
      <div id="tab-courses" class="tab-content <?= $activeTab === 'courses' ? 'active' : '' ?>">
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
      </div>

      <!-- TAB: ORDERS -->
      <div id="tab-orders" class="tab-content <?= $activeTab === 'orders' ? 'active' : '' ?>">
        <div class="card">
          <div class="card-header"><h2 class="card-title">Order History</h2></div>
          <div class="card-body">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
              <h3>No orders yet</h3>
              <p style="margin-top:8px;">Your store orders and donation receipts will appear here.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: DOWNLOADS -->
      <div id="tab-downloads" class="tab-content <?= $activeTab === 'downloads' ? 'active' : '' ?>">
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
      </div>

      <!-- TAB: CERTIFICATES -->
      <div id="tab-certificates" class="tab-content <?= $activeTab === 'certificates' ? 'active' : '' ?>">
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
      </div>

      <!-- TAB: SETTINGS -->
      <div id="tab-settings" class="tab-content <?= $activeTab === 'settings' ? 'active' : '' ?>">
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
                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account? This action cannot be undone.');">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="_sub" value="delete_account">
                  <button type="submit" class="btn-save" style="background: transparent; color: var(--danger); border: 1px solid var(--danger);">Delete Account</button>
                </form>
              </div>
            </div>

          </div>
        </div>
      </div>
      
      <div id="settingsToast" class="settings-toast">Settings saved successfully</div>

    </main>
  </div>
</div>

<script>
function switchTab(tabId) {
  history.pushState(null, null, '?tab=' + tabId);
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  const targetContent = document.getElementById('tab-' + tabId);
  const targetNav = document.querySelector(`.nav-item[data-target="${tabId}"]`);
  if (targetContent) targetContent.classList.add('active');
  if (targetNav) targetNav.classList.add('active');
}

document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab) {
    switchTab(tab);
  }
  
});

function previewAvatar(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var preview = document.getElementById('avatarPreview');
      if (preview.tagName === 'IMG') {
        preview.src = e.target.result;
      } else {
        var newImg = document.createElement('img');
        newImg.id = 'avatarPreview';
        newImg.src = e.target.result;
        newImg.style.cssText = preview.style.cssText;
        preview.parentNode.replaceChild(newImg, preview);
      }
      
      // Update top header avatar instantly as well
      document.querySelectorAll('.avatar-img').forEach(function(el) {
        if(el.tagName === 'IMG') el.src = e.target.result;
      });
    }
    reader.readAsDataURL(input.files[0]);
  }
}

function toggleMasterNotif(masterCheckbox) {
  const isChecked = masterCheckbox.checked;
  const childCheckboxes = document.querySelectorAll('.child-notif');
  childCheckboxes.forEach(cb => {
    cb.checked = isChecked;
    cb.disabled = !isChecked;
    if(!isChecked) {
       cb.nextElementSibling.style.opacity = '0.5';
       cb.nextElementSibling.style.cursor = 'not-allowed';
    } else {
       cb.nextElementSibling.style.opacity = '1';
       cb.nextElementSibling.style.cursor = 'pointer';
    }
  });
  showSaveToast(isChecked ? 'All notifications enabled' : 'All notifications disabled');
}

let toastTimeout;
function showSaveToast(message) {
  const toast = document.getElementById('settingsToast');
  if (!toast) return;
  
  if (message) toast.textContent = message;
  
  toast.classList.add('show');
  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    toast.classList.remove('show');
  }, 3000);
}
</script>
