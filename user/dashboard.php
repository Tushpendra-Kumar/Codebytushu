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
        $phone = sanitize(post('phone_number'));
        $fullName = sanitize(post('full_name'));
        
        $pdo->prepare('UPDATE users SET full_name=?, phone_number=?, updated_at=NOW() WHERE id=?')
            ->execute([$fullName, $phone, $user['id']]);
        $success = 'Profile updated successfully.';
        $user = Auth::user(true);
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

            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="_sub" value="profile">
              <div class="form-group">
                <label class="form-label">Email (Managed by Google)</label>
                <input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly>
              </div>
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number (Optional)</label>
                <div style="display:flex; gap:10px;">
                  <input type="tel" id="phoneNumber" name="phone_number" class="form-control" value="<?= e($user['phone_number'] ?? '') ?>" placeholder="+91 9876543210" autocomplete="tel">
                  <button type="button" id="btnSendOtp" class="btn-save" style="background:#222; color:#fff; border:1px solid #444; flex-shrink:0;">Verify</button>
                </div>
                <div id="recaptcha-container" style="margin-top:10px;"></div>
                
                <!-- OTP Section -->
                <div id="otpSection" style="display:none; margin-top:15px; background:rgba(255,255,255,0.03); padding:16px; border-radius:12px; border:1px solid var(--border);">
                   <label class="form-label" style="color:var(--accent);">Enter 6-digit OTP</label>
                   <div style="display:flex; gap:10px;">
                      <input type="text" id="otpCode" class="form-control" placeholder="123456" maxlength="6" style="letter-spacing:4px; font-weight:bold;">
                      <button type="button" id="btnVerifyOtp" class="btn-save" style="flex-shrink:0;">Confirm</button>
                   </div>
                   <p id="otpMessage" style="font-size:13px; margin-top:10px; color:var(--danger); display:none;"></p>
                   <p id="otpSuccess" style="font-size:13px; margin-top:10px; color:#22c55e; display:none; font-weight:600;"><i class="fa-solid fa-circle-check"></i> Phone Verified Successfully!</p>
                </div>
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
          <div class="card-header"><h2 class="card-title">Settings</h2></div>
          <div class="card-body">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              <h3>Account Preferences</h3>
              <p style="margin-top:8px;">Manage your notification and display settings here in the future.</p>
            </div>
          </div>
        </div>
      </div>

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
</script>

<!-- Firebase SDK -->
<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
  import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";

  // Firebase Configuration
  const firebaseConfig = {
    apiKey: "AIzaSyDHUiszVq3UUmXcBMt8G5ZGhMLTFV9SuaU",
    authDomain: "codebytushu-839a5.firebaseapp.com",
    projectId: "codebytushu-839a5",
    storageBucket: "codebytushu-839a5.firebasestorage.app",
    messagingSenderId: "396256984712",
    appId: "1:396256984712:web:ae36995ac91a195ebd293d",
    measurementId: "G-KJRB6YG0DV"
  };

  // Initialize Firebase
  const app  = initializeApp(firebaseConfig);
  const auth = getAuth(app);
  auth.useDeviceLanguage();

  let confirmationResult  = null;
  let recaptchaVerifier   = null;

  // Helper — create a fresh invisible RecaptchaVerifier on the container div
  function initRecaptcha() {
    if (recaptchaVerifier) {
      try { recaptchaVerifier.clear(); } catch(e) {}
    }
    recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
      size: 'invisible',
      callback: () => {}
    });
  }

  // Run after DOM is ready
  document.addEventListener('DOMContentLoaded', () => {
    const btnSendOtp      = document.getElementById('btnSendOtp');
    const btnVerifyOtp    = document.getElementById('btnVerifyOtp');
    const otpSection      = document.getElementById('otpSection');
    const phoneNumberInput= document.getElementById('phoneNumber');
    const otpCodeInput    = document.getElementById('otpCode');
    const otpMessage      = document.getElementById('otpMessage');
    const otpSuccess      = document.getElementById('otpSuccess');

    // Show "Change" if phone already saved
    if (phoneNumberInput && phoneNumberInput.value.trim() !== '') {
      btnSendOtp.textContent = 'Change';
    }

    // Pre-init reCAPTCHA
    initRecaptcha();

    // ── 1. SEND OTP ───────────────────────────────────────────
    btnSendOtp.addEventListener('click', async () => {
      const phoneNumber = phoneNumberInput.value.trim();

      if (!phoneNumber.startsWith('+')) {
        otpMessage.textContent = 'Country code required — e.g. +91 9876543210';
        otpMessage.style.display = 'block';
        return;
      }

      otpMessage.style.display = 'none';
      btnSendOtp.textContent   = 'Sending…';
      btnSendOtp.disabled      = true;

      try {
        confirmationResult = await signInWithPhoneNumber(auth, phoneNumber, recaptchaVerifier);
        otpSection.style.display      = 'block';
        btnSendOtp.textContent        = 'OTP Sent ✓';
        btnSendOtp.style.background   = '#22c55e';
        btnSendOtp.style.borderColor  = '#22c55e';
        btnSendOtp.style.color        = '#fff';
        otpCodeInput.focus();
      } catch (error) {
        console.error('Send OTP error:', error);
        otpMessage.textContent = 'Failed to send OTP: ' + (error.message || error.code);
        otpMessage.style.display = 'block';
        btnSendOtp.textContent   = 'Verify';
        btnSendOtp.disabled      = false;
        // Re-init reCAPTCHA for retry
        initRecaptcha();
      }
    });

    // ── 2. VERIFY OTP ─────────────────────────────────────────
    btnVerifyOtp.addEventListener('click', async () => {
      const code = otpCodeInput.value.trim();
      if (code.length < 6) {
        otpMessage.textContent = 'Enter the full 6-digit OTP.';
        otpMessage.style.display = 'block';
        return;
      }

      btnVerifyOtp.textContent = 'Verifying…';
      btnVerifyOtp.disabled    = true;
      otpMessage.style.display = 'none';

      try {
        await confirmationResult.confirm(code);

        // Save phone number via AJAX
        const res  = await fetch('/api/auth/save_phone.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'phone_number=' + encodeURIComponent(phoneNumberInput.value.trim())
        });
        const data = await res.json();

        if (data.success) {
          otpSuccess.style.display = 'block';
          otpMessage.style.display = 'none';
          otpCodeInput.disabled    = true;
          btnVerifyOtp.style.display = 'none';
          btnSendOtp.textContent   = 'Verified ✓';
          btnSendOtp.disabled      = true;
          btnSendOtp.style.background  = '#22c55e';
          btnSendOtp.style.color       = '#fff';
        } else {
          throw new Error(data.message || 'DB save failed');
        }

      } catch (error) {
        console.error('Verify OTP error:', error);
        otpMessage.textContent   = error.code === 'auth/invalid-verification-code'
          ? 'Invalid OTP. Please try again.'
          : (error.message || 'Verification failed.');
        otpMessage.style.display = 'block';
        btnVerifyOtp.textContent = 'Confirm';
        btnVerifyOtp.disabled    = false;
      }
    });
  }); // end DOMContentLoaded
</script>
</body>
</html>

