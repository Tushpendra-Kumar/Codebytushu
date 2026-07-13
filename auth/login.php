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
      --border:   rgba(255,196,0,.25);
      --accent:   #ffc400;
      --text:     #ffffff;
      --muted:    #aaaaaa;
      --danger:   #ff4d4d;
      --radius:   12px;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    /* Animated background */
    body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0;
      background:
        radial-gradient(ellipse 60% 50% at 20% 30%, rgba(255,196,0,.10) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 80% 70%, rgba(255,196,0,.08) 0%, transparent 60%);
    }

    .auth-wrapper { position: relative; z-index: 1; width: 100%; max-width: 440px; }

    /* Logo */
    .auth-brand {
      text-align: center; margin-bottom: 32px;
      font-family: 'Ubuntu', sans-serif;
    }
    .auth-brand a { text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;}
    .auth-brand .logo-text {
      font-size: 32px; font-weight: 700; letter-spacing: -0.5px;
    }
    .auth-brand .logo-text .white { color: #fff; }
    .auth-brand .logo-text .gold  { color: var(--accent); }
    .auth-brand .sub {
      font-size: 13px; color: var(--muted); letter-spacing: 2px;
      text-transform: uppercase; margin-top: 4px;
      display: block;
    }

    /* Card */
    .auth-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 48px 40px;
      box-shadow: 0 24px 64px rgba(0,0,0,.6), 0 0 0 1px rgba(255,196,0,.10);
      text-align: center;
    }
    .auth-card h1 {
      font-size: 24px; font-weight: 700; margin-bottom: 8px;
    }
    .auth-card .subtitle {
      font-size: 15px; color: var(--muted); margin-bottom: 36px;
    }

    /* Alert */
    .alert {
      padding: 14px 16px; border-radius: var(--radius); margin-bottom: 24px;
      font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .alert-error   { background: rgba(255,77,77,.12); border: 1px solid rgba(255,77,77,.3); color: #ff6b6b; }
    .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #22c55e; }

    /* Google button */
    .btn-google {
      display: flex; align-items: center; justify-content: center; gap: 14px;
      width: 100%; padding: 16px;
      background: var(--text);
      border: 1px solid var(--text);
      border-radius: var(--radius);
      color: #000;
      font-family: 'Poppins', sans-serif;
      font-size: 16px; font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: opacity .2s, transform .15s, box-shadow .2s;
    }
    .btn-google:hover {
      opacity: .95; 
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(255,255,255,.15);
    }
    .btn-google:active { transform: translateY(0); }
    .btn-google img { width: 24px; height: 24px; }

    /* Back link */
    .auth-footer { text-align: center; margin-top: 28px; }
    .auth-footer a {
      color: var(--muted); font-size: 14px; text-decoration: none;
      transition: color .2s;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .auth-footer a:hover { color: var(--accent); }

  </style>
</head>
<body>

<div class="auth-wrapper">
  <!-- Brand -->
  <div class="auth-brand">
    <a href="<?= SITE_URL ?>/">
      <div class="logo-text"><span class="white">CodeBy</span><span class="gold">Tushu</span></div>
    </a>
    <span class="sub">Premium Modules</span>
  </div>

  <!-- Card -->
  <div class="auth-card">
    <h1>Welcome Back</h1>
    <p class="subtitle">Sign in to access your modules</p>

    <!-- Global Alerts -->
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
      <div class="alert alert-error">
        Google OAuth is not configured. Please add keys to .env
      </div>
    <?php endif; ?>

    <!-- Google Login Button -->
    <a href="<?= SITE_URL ?>/api/auth/google.php" class="btn-google">
      <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google Logo">
      Continue with Google
    </a>
  </div>

  <div class="auth-footer">
    <a href="<?= SITE_URL ?>/">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to Home
    </a>
  </div>
</div>

</body>
</html>
