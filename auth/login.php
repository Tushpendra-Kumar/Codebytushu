<?php
/**
 * CodeByTushu — Login Page (Google OAuth Only)
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();
Auth::redirectIfLoggedIn('/');

$error = $_GET['error'] ?? '';
$next  = get('next', '/');

// Save the redirect intent in session
if ($next !== '/') {
    $_SESSION['redirect_after_login'] = $next;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — CodeByTushu</title>
  <meta name="description" content="Sign in to your CodeByTushu account using Google to access premium modules.">
  <meta name="robots" content="noindex,nofollow">

  <!-- Favicon -->
  <link rel="icon"             href="<?= SITE_URL ?>/favicon.ico?v=6"                 sizes="any">
  <link rel="icon"             href="<?= SITE_URL ?>/favicon-32x32.png?v=6"           type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= SITE_URL ?>/apple-touch-icon.png?v=6"        sizes="180x180">
  <meta name="theme-color"     content="#ffc400">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:       #000000;
      --card:     #111111;
      --card-hover:#161616;
      --border:   rgba(255,196,0,.25);
      --accent:   #ffc400;
      --text:     #ffffff;
      --muted:    #aaaaaa;
      --danger:   #ff4d4d;
      --success:  #22c55e;
      --radius:   16px;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
    }

    /* Animated background */
    body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0;
      background:
        radial-gradient(ellipse 60% 50% at 20% 30%, rgba(255,196,0,.10) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 80% 70%, rgba(255,196,0,.08) 0%, transparent 60%);
      pointer-events: none;
    }

    /* Top Navigation (Home Button) */
    .top-nav {
      position: relative; z-index: 1; width: 100%; max-width: 900px;
      display: flex; justify-content: center; margin-bottom: 40px;
    }
    .btn-home {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 20px; background: var(--card); border: 1px solid var(--border);
      border-radius: 30px; color: var(--text); font-size: 14px; font-weight: 500;
      text-decoration: none; transition: all 0.2s;
    }
    .btn-home:hover { background: rgba(255,196,0,0.1); color: var(--accent); }
    .btn-home svg { width: 16px; height: 16px; }

    .auth-wrapper { position: relative; z-index: 1; width: 100%; max-width: 900px; display: flex; flex-direction: column; align-items: center; }

    /* Card */
    .auth-card {
      background: var(--card); border: 1px solid var(--border); border-radius: 24px;
      padding: 48px 40px; box-shadow: 0 24px 64px rgba(0,0,0,.6), 0 0 0 1px rgba(255,196,0,.10);
      text-align: center; width: 100%; max-width: 480px; margin-bottom: 40px;
    }

    /* Logo Avatar */
    .logo-avatar {
      width: 80px; height: 80px; border-radius: 50%; border: 2px solid var(--accent);
      margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
      background: rgba(255,196,0,0.1);
    }
    .logo-avatar span {
      font-family: 'Ubuntu', sans-serif; font-size: 28px; font-weight: 700; color: #fff;
    }
    .logo-avatar span .gold { color: var(--accent); }

    /* Micro Badges */
    .micro-badges { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-bottom: 24px; }
    .micro-badge {
      display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
      background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
      border-radius: 20px; font-size: 11px; font-weight: 600; color: var(--muted); letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .micro-badge svg { width: 12px; height: 12px; color: var(--accent); }

    .auth-card h1 { font-size: 28px; font-weight: 700; margin-bottom: 12px; line-height: 1.2; }
    .auth-card .subtitle { font-size: 14px; color: var(--muted); margin-bottom: 36px; line-height: 1.5; }

    /* Alert */
    .alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .alert-error   { background: rgba(255,77,77,.12); border: 1px solid rgba(255,77,77,.3); color: #ff6b6b; }
    .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #22c55e; }

    /* Google button */
    .btn-google {
      display: flex; align-items: center; justify-content: center; gap: 14px;
      width: 100%; padding: 16px; background: var(--text); border: 1px solid var(--text);
      border-radius: 12px; color: #000; font-family: 'Poppins', sans-serif;
      font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none;
      transition: all .2s;
    }
    .btn-google:hover { opacity: .95; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,255,255,.15); }
    .btn-google:active { transform: translateY(0); }
    .btn-google img { width: 24px; height: 24px; }

    .explore-link {
      display: inline-flex; align-items: center; gap: 6px; margin-top: 24px;
      color: var(--muted); font-size: 14px; text-decoration: none; font-weight: 500; transition: color 0.2s;
    }
    .explore-link:hover { color: var(--accent); }
    .explore-link svg { width: 16px; height: 16px; }

    /* Divider */
    .section-divider {
      display: flex; align-items: center; justify-content: center; gap: 16px;
      width: 100%; margin: 20px 0 40px; color: var(--muted); font-size: 12px;
      font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    }
    .section-divider::before, .section-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.1); }
    .section-divider svg { width: 18px; height: 18px; color: var(--accent); }

    /* Benefits Grid */
    .benefits-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; width: 100%; }

    .benefit-card {
      background: var(--card); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px;
      padding: 32px; transition: transform 0.2s, box-shadow 0.2s;
    }
    .benefit-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.4); }
    
    .benefit-card.with-login { border-color: rgba(34,197,94,0.3); background: linear-gradient(180deg, rgba(34,197,94,0.05) 0%, var(--card) 100%); }
    .benefit-card.without-login { border-color: rgba(255,77,77,0.3); background: linear-gradient(180deg, rgba(255,77,77,0.05) 0%, var(--card) 100%); }

    .card-badge {
      display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
      border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 1px; margin-bottom: 20px;
    }
    .card-badge.success { background: rgba(34,197,94,0.15); color: var(--success); }
    .card-badge.danger { background: rgba(255,77,77,0.15); color: var(--danger); }
    .card-badge svg { width: 14px; height: 14px; }

    .benefit-card h2 { font-size: 20px; font-weight: 700; margin-bottom: 24px; color: var(--text); }

    .benefit-list { list-style: none; }
    .benefit-list li {
      display: flex; align-items: flex-start; gap: 12px; font-size: 14px;
      color: var(--muted); margin-bottom: 16px; line-height: 1.5;
    }
    .benefit-list li:last-child { margin-bottom: 0; }
    .benefit-list.success li svg { color: var(--success); flex-shrink: 0; width: 18px; height: 18px; margin-top: 2px; }
    .benefit-list.danger li svg { color: var(--danger); flex-shrink: 0; width: 18px; height: 18px; margin-top: 2px; }

    @media (max-width: 768px) {
      .benefits-grid { grid-template-columns: 1fr; }
      .auth-card { padding: 40px 24px; }
    }
  </style>
</head>
<body>

<!-- Top Nav -->
<div class="top-nav">
  <a href="<?= SITE_URL ?>/" class="btn-home">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Home
  </a>
</div>

<div class="auth-wrapper">
  
  <!-- Main Auth Card -->
  <div class="auth-card">
    <div class="logo-avatar">
      <span>C<span class="gold">T</span></span>
    </div>

    <div class="micro-badges">
      <div class="micro-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Secure
      </div>
      <div class="micro-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        Fast Access
      </div>
      <div class="micro-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        No Spam
      </div>
    </div>

    <h1>Welcome to CodeByTushu</h1>
    <p class="subtitle">Login to unlock premium modules and save your progress permanently.</p>

    <!-- Alerts -->
    <?php if ($msg = getFlash('error')): ?>
      <div class="alert alert-error">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>
    <?php if ($msg = getFlash('success')): ?>
      <div class="alert alert-success">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3"/></svg>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>
    <?php if ($error === 'google_not_configured'): ?>
      <div class="alert alert-error">Google OAuth is not configured.</div>
    <?php endif; ?>

    <!-- Google Login Button -->
    <a href="<?= SITE_URL ?>/api/auth/google.php" class="btn-google">
      <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google Logo">
      Continue with Google
    </a>

    <div style="margin-top: 24px; position: relative;">
        <span style="background: var(--card); padding: 0 10px; color: var(--muted); font-size: 12px; font-weight: 600; position: relative; z-index: 1;">OR</span>
        <div style="position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: rgba(255,255,255,0.1); z-index: 0;"></div>
    </div>

    <a href="<?= SITE_URL ?>/" class="explore-link">
      Continue exploring freely
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
  </div>

  <!-- Divider -->
  <div class="section-divider">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    What You'll Get
  </div>

  <!-- Benefits Section -->
  <div class="benefits-grid">
    
    <!-- With Login -->
    <div class="benefit-card with-login">
      <div class="card-badge success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        WITH LOGIN
      </div>
      <h2>Logged-in Benefits</h2>
      <ul class="benefit-list success">
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/></svg>
          Save your LeetCode solutions and progress permanently.
        </li>
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/></svg>
          Direct access to premium Courses and Video Editing assets.
        </li>
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/></svg>
          Interact, comment on blogs, and explore the complete platform.
        </li>
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/></svg>
          Single Sign-On (SSO): Login once, access everything without passwords.
        </li>
      </ul>
    </div>

    <!-- Without Login -->
    <div class="benefit-card without-login">
      <div class="card-badge danger">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        WITHOUT LOGIN
      </div>
      <h2>Guest Limitations</h2>
      <ul class="benefit-list danger">
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          Cannot save your progress or bookmark anything.
        </li>
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          Restricted access. Only public landing pages can be viewed.
        </li>
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          Cannot access LeetCode solutions, Courses, or premium assets.
        </li>
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          Cannot interact or comment on any platform discussions.
        </li>
      </ul>
    </div>

  </div>

</div>

</body>
</html>
