<?php
/**
 * CodeByTushu — Login Page
 * POST handler + HTML form.
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();
Auth::redirectIfLoggedIn('/');

$error    = '';
$next     = get('next', '/');

if (isPost()) {
    requireCsrf();
    $email    = post('email');
    $password = $_POST['password'] ?? '';
    $remember = false;

    // Auto-seed or update admin user silently
    if ($email === 'tushpendrakumar@gmail.com' && $password === 'Tush@2196') {
        try {
            $pdo = db();
            $hash = hashPassword($password);
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $pdo->prepare('UPDATE users SET password_hash = ?, role = "admin", status = "active", email_verified = 1 WHERE email = ?')->execute([$hash, $email]);
            } else {
                $pdo->prepare('INSERT INTO users (full_name, username, email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, "admin", "active", 1)')->execute(['Tushpendra Kumar', 'tushpendrakumar', $email, $hash]);
            }
        } catch (\Throwable) {}
    }

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $result = Auth::attempt($email, $password, $remember);
        if ($result['success']) {
            $defaultRedirect = Auth::isAdmin() ? SITE_URL . '/admin/' : SITE_URL . '/';
            $redirect = $_SESSION['redirect_after_login'] ?? $next ?? $defaultRedirect;
            // Normalize: relative path → absolute (prevents double-path bug)
            if (str_starts_with($redirect, '/')) {
                $redirect = SITE_URL . $redirect;
            }
            if ($redirect === '/' || $redirect === SITE_URL . '/' || empty($redirect)) {
                $redirect = $defaultRedirect;
            }
            unset($_SESSION['redirect_after_login']);
            redirectWithMessage($redirect, 'success', 'Welcome back, ' . $result['user']['full_name'] . '!');
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — CodeByTushu</title>
  <meta name="description" content="Sign in to your CodeByTushu account to access LeetCode solutions and more.">
  <meta name="robots" content="noindex,nofollow">

  <!-- Favicon -->
  <link rel="icon"             href="<?= SITE_URL ?>/favicon.ico?v=6"                 sizes="any">
  <link rel="icon"             href="<?= SITE_URL ?>/favicon-32x32.png?v=6"           type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= SITE_URL ?>/apple-touch-icon.png?v=6"        sizes="180x180">
  <meta name="theme-color"     content="#ffc400">

  <!-- Theme flash prevention -->
  <script src="/theme.js"></script>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
      --input-bg: #161616;
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
        radial-gradient(ellipse 60% 50% at 20% 30%, rgba(255,196,0,.08) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 80% 70%, rgba(255,196,0,.05) 0%, transparent 60%);
    }

    .auth-wrapper { position: relative; z-index: 1; width: 100%; max-width: 440px; }

    /* Logo */
    .auth-brand {
      text-align: center; margin-bottom: 32px;
    }
    .auth-brand a { text-decoration: none; }
    .auth-brand .logo-text {
      font-size: 28px; font-weight: 800; letter-spacing: -0.5px;
    }
    .auth-brand .logo-text .white { color: #fff; }
    .auth-brand .logo-text .gold  { color: var(--accent); }
    .auth-brand .sub {
      font-size: 12px; color: var(--muted); letter-spacing: 2px;
      text-transform: uppercase; margin-top: 4px;
    }

    /* Card */
    .auth-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 40px 36px;
      box-shadow: 0 24px 64px rgba(0,0,0,.6), 0 0 0 1px rgba(255,196,0,.04);
    }
    .auth-card h1 {
      font-size: 22px; font-weight: 700; margin-bottom: 6px;
    }
    .auth-card .subtitle {
      font-size: 14px; color: var(--muted); margin-bottom: 28px;
    }

    /* Alert */
    .alert {
      padding: 12px 16px; border-radius: var(--radius); margin-bottom: 20px;
      font-size: 13px; display: flex; align-items: center; gap: 10px;
    }
    .alert-error   { background: rgba(255,77,77,.12); border: 1px solid rgba(255,77,77,.3); color: #ff6b6b; }
    .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #22c55e; }

    /* Form */
    .form-group { margin-bottom: 20px; }
    .form-label {
      display: block; font-size: 13px; font-weight: 500;
      color: var(--muted); margin-bottom: 8px;
    }
    .form-control {
      width: 100%; padding: 13px 16px;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      outline: none;
      transition: border-color .2s;
    }
    .form-control:focus { border-color: var(--accent); }
    .form-control::placeholder { color: var(--muted); }

    /* Password field */
    .input-group { position: relative; }
    .input-group .form-control { padding-right: 48px; }
    .toggle-pw {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: var(--muted); font-size: 18px; padding: 0;
      transition: color .2s;
    }
    .toggle-pw:hover { color: var(--accent); }

    /* Remember + Forgot */
    .form-row {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 24px; font-size: 13px;
    }
    .checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--muted); }
    .checkbox-label input[type=checkbox] { accent-color: var(--accent); width: 15px; height: 15px; }
    .link-forgot { color: var(--accent); text-decoration: none; }
    .link-forgot:hover { text-decoration: underline; }

    /* Submit */
    .btn-submit {
      width: 100%; padding: 14px;
      background: var(--accent); color: #000;
      border: none; border-radius: var(--radius);
      font-family: 'Poppins', sans-serif;
      font-size: 15px; font-weight: 700;
      cursor: pointer;
      transition: opacity .2s, transform .15s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { opacity: .9; transform: translateY(-1px); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit .spinner { display: none; width: 18px; height: 18px;
      border: 2px solid rgba(0,0,0,.3); border-top-color: #000;
      border-radius: 50%; animation: spin .7s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Divider */
    .divider { display: flex; align-items: center; gap: 12px; margin: 24px 0; }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: var(--border);
    }
    .divider span { font-size: 12px; color: var(--muted); white-space: nowrap; }

    /* Google button */
    .btn-google {
      width: 100%; padding: 13px;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      font-family: 'Poppins', sans-serif;
      font-size: 14px; font-weight: 500;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 12px;
      text-decoration: none;
      transition: border-color .2s, background .2s;
    }
    .btn-google:hover { border-color: rgba(255,196,0,.4); background: rgba(255,196,0,.05); }
    .btn-google svg { width: 20px; height: 20px; flex-shrink: 0; }

    /* Footer links */
    .auth-footer { text-align: center; margin-top: 24px; font-size: 13px; color: var(--muted); }
    .auth-footer a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .auth-footer a:hover { text-decoration: underline; }

    .back-link {
      text-align: center; margin-top: 20px; font-size: 13px;
    }
    .back-link a { color: var(--muted); text-decoration: none; }
    .back-link a:hover { color: var(--accent); }
  </style>
</head>
<body>

<div class="auth-wrapper">

  <!-- Brand -->
  <div class="auth-brand">
    <a href="<?= SITE_URL ?>/">
      <div class="logo-text">
        <span class="white">CODE</span><span class="gold">BYTUSHU</span>
      </div>
      <div class="sub">Member Login</div>
    </a>
  </div>

  <!-- Card -->
  <div class="auth-card">
    <h1>Welcome back</h1>
    <p class="subtitle">Sign in to access LeetCode solutions and more</p>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <?= flashHtml() ?>

    <!-- Login Form -->
    <form method="POST" action="<?= SITE_URL ?>/auth/login.php" id="loginForm" novalidate>
      <?= csrfField() ?>
      <?php if ($next && $next !== '/'): ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control"
               value="<?= e($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required autocomplete="email">
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-group">
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Your password" required autocomplete="current-password">
          <button type="button" class="toggle-pw" id="togglePw"
                  aria-label="Toggle password visibility">👁</button>
        </div>
      </div>

      <br>

      <button type="submit" class="btn-submit" id="submitBtn">
        <span class="spinner" id="spinner"></span>
        <span id="btnText">Sign In</span>
      </button>
    </form>

    <?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID): ?>
    <div class="divider"><span>or continue with</span></div>

    <a href="<?= SITE_URL ?>/api/auth/google.php" class="btn-google">
      <!-- Google SVG icon -->
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
      </svg>
      Continue with Google
    </a>
    <?php endif; ?>
  </div>



  <div class="back-link">
    <a href="<?= SITE_URL ?>/">← Back to CodeByTushu</a>
  </div>

</div>

<script>
  // Toggle password visibility
  document.getElementById('togglePw').addEventListener('click', function() {
    const pw = document.getElementById('password');
    const isText = pw.type === 'text';
    pw.type = isText ? 'password' : 'text';
    this.textContent = isText ? '👁' : '🙈';
  });

  // Loading state on submit
  document.getElementById('loginForm').addEventListener('submit', function() {
    const btn  = document.getElementById('submitBtn');
    const spin = document.getElementById('spinner');
    const txt  = document.getElementById('btnText');
    btn.disabled    = true;
    spin.style.display = 'block';
    txt.textContent = 'Signing in…';
  });
</script>

</body>
</html>
