<?php
$activeTab = $activeTab ?? 'profile';
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
    .tab-content { animation: fadeIn 0.3s ease; }
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

  <?php if (!empty($error)):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="profile-grid">
