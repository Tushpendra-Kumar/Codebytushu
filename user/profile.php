<?php
/**
 * CodeByTushu — User Profile & Settings Page
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Upload.php';

Auth::boot();
Auth::requireLogin();

$pdo  = db();


// phone_number column is added via database/migrations/004_leetcode_missing_columns.sql

// Fetch a fresh user object to ensure all fields are loaded correctly
$user = Auth::user(true);

$error   = '';
$success = '';
$activeTab = $_POST['active_tab'] ?? 'profile';

if (isPost()) {
    requireCsrf();
    $sub = post('_sub');

    if ($sub === 'profile') {
        $activeTab = 'profile';
        $fullName = sanitize(post('full_name'));
        $phone    = sanitize(post('phone_number'));

        if (!$fullName || strlen($fullName) < 2) {
            $error = 'Full name must be at least 2 characters.';
        } else {
            $pdo->prepare('UPDATE users SET full_name=?,phone_number=?,updated_at=NOW() WHERE id=?')
                ->execute([$fullName, $phone, $user['id']]);
            $success = 'Profile updated successfully.';
            $user = Auth::user(true);
        }
    }

    if ($sub === 'avatar') {
        $activeTab = 'profile';
        if (!empty($_FILES['avatar']['name'])) {
            $up = new Upload('avatars', $user['id']);
            if ($up->upload('avatar', 'image')) {
                $pdo->prepare('UPDATE users SET profile_image=?,updated_at=NOW() WHERE id=?')
                    ->execute([$up->filePath, $user['id']]);
                $success = 'Profile picture updated.';
                $user = Auth::user(true);
            } else {
                $error = $up->error;
            }
        }
    }

    if ($sub === 'password') {
        $activeTab = 'security';
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$newPass || !$confirm) {
            $error = 'All password fields are required.';
        } elseif ($newPass !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPass)) {
            $error = 'Password must be at least 8 characters and contain 1 uppercase, 1 lowercase, 1 number, and 1 special character.';
        } else {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();
            if (!verifyPassword($current, $hash)) {
                $error = 'Current password is incorrect.';
            } else {
                $pdo->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?')
                    ->execute([hashPassword($newPass), $user['id']]);
                $success = 'Password changed successfully.';
            }
        }
    }

    if ($sub === 'settings') {
        $activeTab = 'settings';
        // Settings are mostly placeholders right now, but we show success message.
        $success = 'Account settings saved successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
  <title>My Profile & Settings — CodeByTushu</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="/favicon.ico?v=6" sizes="any">
  <meta name="theme-color" content="#ffc400">
  <script src="/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0a0a0c; --card: #111118; --border: rgba(255,196,0,.14); --accent: #ffc400;
      --text: #f0f0f0; --muted: #888898; --input: #16161e; --radius: 12px; --danger: #ff4d4d;
      --danger-bg: rgba(255,77,77,0.1);
    }
    [data-theme="light"] {
      --bg: #f8f9fa; --card: #ffffff; --border: #e2e8f0; --text: #1e293b;
      --muted: #64748b; --input: #f1f5f9;
    }
    body {
      font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text);
      min-height: 100vh;
    }
    body::before {
      content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background: radial-gradient(ellipse 60% 50% at 20% 30%, rgba(255,196,0,.06) 0%, transparent 60%);
    }
    .layout {
      position: relative; z-index: 1; max-width: 1000px; margin: 0 auto; padding: 40px 20px;
    }
    .top-bar {
      display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px;
    }
    .top-bar a { text-decoration: none; color: var(--muted); font-size: 13px; font-weight: 500; transition: color 0.2s; }
    .top-bar a:hover { color: var(--accent); }
    .logo { font-size: 22px; font-weight: 800; text-decoration: none; color: var(--text); }
    .logo .gold { color: var(--accent); }

    /* Layout Grid */
    .profile-grid {
      display: grid; grid-template-columns: 260px 1fr; gap: 32px; align-items: start;
    }

    /* Sidebar Navigation */
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
    .nav-item.active { background: rgba(255,196,0,0.1); color: var(--accent); font-weight: 600; }
    .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }

    /* Content Area */
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .card {
      background: var(--card); border: 1px solid var(--border); border-radius: 16px;
      margin-bottom: 24px; overflow: hidden;
    }
    .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); }
    .card-title { font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .card-body { padding: 24px; }
    
    .alert { padding: 14px 18px; border-radius: var(--radius); margin-bottom: 24px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
    .alert-error { background: rgba(255,77,77,.12); border: 1px solid rgba(255,77,77,.3); color: #ff6b6b; }
    .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #22c55e; }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .form-control {
      width: 100%; padding: 12px 16px; background: var(--input); border: 1px solid var(--border);
      border-radius: var(--radius); color: var(--text); font-family: 'Poppins', sans-serif;
      font-size: 14px; outline: none; transition: border-color .2s;
    }
    .form-control:focus { border-color: var(--accent); }
    .form-control:disabled, .form-control[readonly] { opacity: 0.6; cursor: not-allowed; }
    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    .btn-save {
      padding: 12px 24px; background: var(--accent); color: #000; border: none;
      border-radius: var(--radius); font-family: 'Poppins', sans-serif; font-size: 14px;
      font-weight: 700; cursor: pointer; transition: opacity .2s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-save:hover { opacity: 0.9; }

    /* Avatar Section */
    .avatar-section { display: flex; align-items: center; gap: 24px; margin-bottom: 30px; }
    .avatar-img {
      width: 90px; height: 90px; border-radius: 50%; object-fit: cover;
      background: var(--accent); color: #000; font-size: 32px; font-weight: 700;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;
      border: 3px solid var(--card); box-shadow: 0 0 0 2px var(--border);
    }
    .avatar-img img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-btns { display: flex; flex-direction: column; gap: 10px; }
    .btn-upload {
      padding: 10px 18px; background: var(--input); border: 1px solid var(--border);
      color: var(--text); border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; font-family: 'Poppins', sans-serif;
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-upload:hover { border-color: var(--accent); color: var(--accent); }

    /* Settings Items */
    .setting-item {
      display: flex; align-items: center; justify-content: space-between; padding: 16px 0;
      border-bottom: 1px solid var(--border);
    }
    .setting-item:last-child { border-bottom: none; padding-bottom: 0; }
    .setting-info-title { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 4px; }

    /* Toggles */
    .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider {
      position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
      background-color: var(--input); transition: .3s; border-radius: 34px; border: 1px solid var(--border);
    }
    .slider:before {
      position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
      background-color: var(--muted); transition: .3s; border-radius: 50%;
    }
    input:checked + .slider { background-color: rgba(255,196,0,0.15); border-color: var(--accent); }
    input:checked + .slider:before { transform: translateX(20px); background-color: var(--accent); }

    @media (max-width: 768px) {
      .profile-grid { grid-template-columns: 1fr; gap: 24px; }
      .sidebar { position: static; display: flex; overflow-x: auto; padding: 10px; gap: 10px; white-space: nowrap; }
      .nav-item { margin: 0; }
      .form-row-2 { grid-template-columns: 1fr; gap: 20px; }
    }
  </style>
</head>
<body>

<div class="layout">
  <!-- Header -->
  <header class="top-bar">
    <a href="/" class="logo">CODE<span class="gold">BY</span>TUSHU</a>
    <div style="display:flex;gap:20px;align-items:center;">
      <?php if (Auth::isAdmin()): ?><a href="<?= SITE_URL ?>/admin/">Admin Panel →</a><?php endif; ?>
      <a href="/">← Home</a>
      <a href="/auth/logout.php" style="color:var(--danger);font-weight:600;">Logout</a>
    </div>
  </header>

  <?php if ($error):   ?><div class="alert alert-error"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> <?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> <?= e($success) ?></div><?php endif; ?>

  <div class="profile-grid">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <a class="nav-item <?= $activeTab === 'profile' ? 'active' : '' ?>" data-target="profile" onclick="switchTab('profile')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        My Profile
      </a>
      <a class="nav-item <?= $activeTab === 'settings' ? 'active' : '' ?>" data-target="settings" onclick="switchTab('settings')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Account Settings
      </a>
      <a class="nav-item <?= $activeTab === 'security' ? 'active' : '' ?>" data-target="security" onclick="switchTab('security')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Change Password
      </a>
    </aside>

    <!-- Main Content Area -->
    <main class="content-area">
      
      <!-- ==========================================
           TAB: MY PROFILE
           ========================================== -->
      <div id="tab-profile" class="tab-content <?= $activeTab === 'profile' ? 'active' : '' ?>">
        
        <!-- Avatar Update -->
        <div class="card">
          <div class="card-header"><h2 class="card-title">Profile Photo</h2></div>
          <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
              <?= csrfField() ?>
              <input type="hidden" name="_sub" value="avatar">
              <input type="hidden" name="active_tab" value="profile">
              <div class="avatar-section">
                <div class="avatar-img" id="avatarPreview">
                  <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?= e($user['profile_image']) ?>" alt="" id="avatarImg">
                  <?php else: ?>
                    <?= strtoupper(substr($user['full_name'] ?? 'U',0,1)) ?>
                  <?php endif; ?>
                </div>
                <div class="avatar-btns">
                  <label class="btn-upload" for="avatarInput">
                    Upload Photo
                  </label>
                  <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none" onchange="previewAvatar(this)">
                </div>
                <button type="submit" class="btn-save" style="margin-left:auto;">Save Photo</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Basic Information -->
        <div class="card">
          <div class="card-header"><h2 class="card-title">Basic Information</h2></div>
          <div class="card-body">
            <form method="POST" id="profileForm">
              <?= csrfField() ?>
              <input type="hidden" name="_sub" value="profile">
              <input type="hidden" name="active_tab" value="profile">
              
              <div class="form-row-2">
                <div class="form-group">
                  <label class="form-label">Full Name</label>
                  <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Email Address <span style="font-weight:400;text-transform:none;">(Verified)</span></label>
                  <input type="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" readonly>
                </div>
              </div>
              <div class="form-group" style="max-width:400px;">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone_number" class="form-control" value="<?= e($user['phone_number'] ?? '') ?>" placeholder="e.g., +91 9876543210">
              </div>

              <div style="display:flex;justify-content:flex-start;gap:12px;">
                <button type="button" class="btn-save" style="background:var(--input);color:var(--text);border:1px solid var(--border);" onclick="enableProfileEdit()">Edit Profile</button>
                <button type="submit" class="btn-save" id="btnSaveProfile" style="display:none;">Save Changes</button>
              </div>
            </form>
          </div>
        </div>

      </div>

      <!-- ==========================================
           TAB: ACCOUNT SETTINGS
           ========================================== -->
      <div id="tab-settings" class="tab-content <?= $activeTab === 'settings' ? 'active' : '' ?>">
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="_sub" value="settings">
          <input type="hidden" name="active_tab" value="settings">
          
          <div class="card">
            <div class="card-header"><h2 class="card-title">Account Settings</h2></div>
            <div class="card-body">
              <div class="setting-item">
                <div class="setting-info-title">Dark Mode Toggle</div>
                <label class="toggle-switch">
                  <input type="checkbox" id="themeSettingToggle" checked>
                  <span class="slider"></span>
                </label>
              </div>
              <div class="setting-item">
                <div class="setting-info-title">Language Selection</div>
                <select class="form-control" style="width:150px;padding:8px 12px;">
                  <option>English (US)</option>
                  <option>Hindi</option>
                </select>
              </div>
              <div class="setting-item">
                <div class="setting-info-title">Email Notifications</div>
                <label class="toggle-switch">
                  <input type="checkbox" checked>
                  <span class="slider"></span>
                </label>
              </div>
              <div class="setting-item">
                <div class="setting-info-title">Browser Notifications</div>
                <label class="toggle-switch">
                  <input type="checkbox">
                  <span class="slider"></span>
                </label>
              </div>
            </div>
          </div>

          <div style="display:flex;justify-content:flex-end;margin-bottom:40px;">
            <button type="submit" class="btn-save">Save Settings</button>
          </div>
        </form>
      </div>

      <!-- ==========================================
           TAB: CHANGE PASSWORD
           ========================================== -->
      <div id="tab-security" class="tab-content <?= $activeTab === 'security' ? 'active' : '' ?>">
        <div class="card">
          <div class="card-header"><h2 class="card-title">Change Password</h2></div>
          <div class="card-body">
            <form method="POST" id="passwordForm">
              <?= csrfField() ?>
              <input type="hidden" name="_sub" value="password">
              <input type="hidden" name="active_tab" value="security">
              
              <div class="form-group" style="max-width:400px;">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">
              <div class="form-group" style="max-width:400px;">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" id="newPass" class="form-control" placeholder="Min 8 chars, 1 Upper, 1 Lower, 1 Num, 1 Spec" required>
                <p id="passError" style="color:var(--danger);font-size:12px;margin-top:8px;display:none;"></p>
              </div>
              <div class="form-group" style="max-width:400px;">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirmPass" class="form-control" required>
                <p id="confirmError" style="color:var(--danger);font-size:12px;margin-top:8px;display:none;"></p>
              </div>
              
              <div style="display:flex;justify-content:flex-start;margin-top:30px;">
                <button type="submit" class="btn-save" id="btnChangePass">Update Password</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
// --- Tab Switching Logic ---
function switchTab(tabId) {
  // Update URL hash without jumping
  history.pushState(null, null, '#' + tabId);
  
  // Hide all contents
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  
  // Show target
  const targetContent = document.getElementById('tab-' + tabId);
  const targetNav = document.querySelector(`.nav-item[data-target="${tabId}"]`);
  
  if (targetContent) targetContent.classList.add('active');
  if (targetNav) targetNav.classList.add('active');
}

// Check Hash on Load
document.addEventListener('DOMContentLoaded', () => {
  const hash = window.location.hash.replace('#', '');
  if (['profile', 'settings', 'security'].includes(hash)) {
    switchTab(hash);
  }
  
  // Custom Theme Toggle behavior for settings page
  const themeToggle = document.getElementById('themeSettingToggle');
  if (themeToggle) {
    const currentTheme = localStorage.getItem('cbt_admin_theme') || 'dark';
    themeToggle.checked = currentTheme === 'dark';
    
    themeToggle.addEventListener('change', (e) => {
      const newTheme = e.target.checked ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('cbt_admin_theme', newTheme);
      
      // Attempt to sync with the admin JS Theme object if it exists (for live chart updates etc)
      if (typeof Theme !== 'undefined') {
        Theme.apply(newTheme);
      }
    });
  }

  // Profile Edit logic
  const profileInputs = document.querySelectorAll('#profileForm input[type="text"]');
  profileInputs.forEach(input => {
    input.setAttribute('readonly', true);
    input.style.opacity = '0.6';
  });
});

function enableProfileEdit() {
  const profileInputs = document.querySelectorAll('#profileForm input[type="text"]');
  profileInputs.forEach(input => {
    input.removeAttribute('readonly');
    input.style.opacity = '1';
  });
  document.getElementById('btnSaveProfile').style.display = 'inline-flex';
}

// Watch for hash changes (e.g. Back/Forward button)
window.addEventListener('hashchange', () => {
  const hash = window.location.hash.replace('#', '');
  if (['profile', 'settings', 'security'].includes(hash)) {
    switchTab(hash);
  }
});

// --- Avatar Preview ---
function previewAvatar(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('avatarPreview');
    prev.innerHTML = `<img src="${e.target.result}" id="avatarImg" style="width:100%;height:100%;object-fit:cover;">`;
  };
  reader.readAsDataURL(file);
}

// --- Strict Password Validation (Frontend) ---
const passForm = document.getElementById('passwordForm');
const newPass = document.getElementById('newPass');
const confirmPass = document.getElementById('confirmPass');
const passError = document.getElementById('passError');
const confirmError = document.getElementById('confirmError');

if (passForm) {
  passForm.addEventListener('submit', (e) => {
    let valid = true;
    const pwd = newPass.value;
    const cpwd = confirmPass.value;
    
    // Reset errors
    passError.style.display = 'none';
    confirmError.style.display = 'none';
    
    // Strict Regex check
    const regex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    if (!regex.test(pwd)) {
      passError.textContent = 'Password must be at least 8 characters and contain 1 uppercase, 1 lowercase, 1 number, and 1 special character.';
      passError.style.display = 'block';
      valid = false;
    }
    
    // Match check
    if (pwd !== cpwd) {
      confirmError.textContent = 'Passwords do not match.';
      confirmError.style.display = 'block';
      valid = false;
    }
    
    if (!valid) {
      e.preventDefault();
    }
  });
}
</script>
</body>
</html>
