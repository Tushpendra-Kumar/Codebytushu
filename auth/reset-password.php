<?php
/**
 * CodeByTushu — Reset Password Page
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();

$token = get('token');
$error = '';
$done  = false;

if (!$token) redirect('/auth/forgot-password.php');

$email = Auth::validateResetToken($token);
if (!$email) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
}

if (isPost() && !$error) {
    requireCsrf();
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $rawToken = post('token') ?: $token;
        if (Auth::resetPassword($rawToken, $password)) {
            $done = true;
        } else {
            $error = 'Reset failed. The link may have expired. Please request a new one.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — CodeByTushu</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="/favicon.ico?v=6" sizes="any">
  <meta name="theme-color" content="#ffc400">
  <script src="/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --bg: #0a0a0c; --card: #111118; --border: rgba(255,196,0,.14);
            --accent: #ffc400; --text: #f0f0f0; --muted: #888898;
            --danger: #ff4d4d; --input-bg: #16161e; --radius: 12px; }
    body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text);
           min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    body::before { content: ''; position: fixed; inset: 0; z-index: 0;
      background: radial-gradient(ellipse 60% 50% at 50% 30%, rgba(255,196,0,.08) 0%, transparent 60%); }
    .auth-wrapper { position: relative; z-index: 1; width: 100%; max-width: 420px; }
    .auth-brand { text-align: center; margin-bottom: 32px; }
    .auth-brand a { text-decoration: none; }
    .logo-text { font-size: 28px; font-weight: 800; }
    .logo-text .white { color: #fff; } .logo-text .gold { color: var(--accent); }
    .brand-sub { font-size: 12px; color: var(--muted); letter-spacing: 2px; text-transform: uppercase; margin-top: 4px; }
    .auth-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px;
                 padding: 40px 36px; box-shadow: 0 24px 64px rgba(0,0,0,.6); }
    .auth-card h1 { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
    .auth-card .subtitle { font-size: 14px; color: var(--muted); margin-bottom: 28px; }
    .alert { padding: 12px 16px; border-radius: var(--radius); margin-bottom: 20px;
             font-size: 13px; display: flex; align-items: flex-start; gap: 10px; }
    .alert-error { background: rgba(255,77,77,.12); border: 1px solid rgba(255,77,77,.3); color: #ff6b6b; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 500; color: var(--muted); margin-bottom: 8px; }
    .input-group { position: relative; }
    .form-control { width: 100%; padding: 13px 16px; background: var(--input-bg);
                    border: 1px solid var(--border); border-radius: var(--radius); color: var(--text);
                    font-family: 'Poppins', sans-serif; font-size: 14px; outline: none;
                    transition: border-color .2s; }
    .input-group .form-control { padding-right: 48px; }
    .form-control:focus { border-color: var(--accent); }
    .form-control::placeholder { color: var(--muted); }
    .toggle-pw { position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
                 background: none; border: none; cursor: pointer; color: var(--muted); font-size: 18px; padding: 0; }
    .toggle-pw:hover { color: var(--accent); }
    .pw-strength { margin-top: 8px; height: 4px; border-radius: 2px; background: var(--border); overflow: hidden; }
    .pw-strength-bar { height: 100%; border-radius: 2px; width: 0; transition: width .3s, background .3s; }
    .pw-strength-label { font-size: 11px; color: var(--muted); margin-top: 4px; }
    .btn-submit { width: 100%; padding: 14px; background: var(--accent); color: #000;
                  border: none; border-radius: var(--radius); font-family: 'Poppins', sans-serif;
                  font-size: 15px; font-weight: 700; cursor: pointer; transition: opacity .2s, transform .15s; }
    .btn-submit:hover { opacity: .9; transform: translateY(-1px); }
    .success-state { text-align: center; padding: 20px 0; }
    .success-icon { font-size: 56px; margin-bottom: 16px; }
    .success-state h2 { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
    .success-state p { color: var(--muted); font-size: 14px; line-height: 1.7; margin-bottom: 24px; }
    .btn-login { display: inline-block; padding: 12px 32px; background: var(--accent); color: #000;
                 border-radius: var(--radius); font-weight: 700; text-decoration: none; font-size: 14px; }
    .btn-login:hover { opacity: .9; }
    .auth-footer { text-align: center; margin-top: 24px; font-size: 13px; color: var(--muted); }
    .auth-footer a { color: var(--accent); text-decoration: none; font-weight: 600; }
  </style>
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-brand">
    <a href="/">
      <div class="logo-text"><span class="white">CODE</span><span class="gold">BYTUSHU</span></div>
      <div class="brand-sub">Reset Password</div>
    </a>
  </div>

  <div class="auth-card">
    <?php if ($done): ?>
      <div class="success-state">
        <div class="success-icon">🔐</div>
        <h2>Password updated!</h2>
        <p>Your password has been changed successfully. You can now sign in with your new password.</p>
        <a href="/auth/login.php" class="btn-login">Sign In Now</a>
      </div>

    <?php elseif ($error && !$email): ?>
      <div class="alert alert-error">⚠️ <?= e($error) ?></div>
      <p style="text-align:center;margin-top:20px;">
        <a href="/auth/forgot-password.php"
           style="color:var(--accent);text-decoration:none;font-weight:600;">
          Request a new reset link →
        </a>
      </p>

    <?php else: ?>
      <h1>Create new password</h1>
      <p class="subtitle">Choose a strong password for your account.</p>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= SITE_URL ?>/auth/reset-password.php?token=<?= urlencode($token) ?>" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div class="form-group">
          <label class="form-label" for="password">New Password</label>
          <div class="input-group">
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="At least 8 characters" required autocomplete="new-password" minlength="8">
            <button type="button" class="toggle-pw" id="togglePw1">👁</button>
          </div>
          <div class="pw-strength"><div class="pw-strength-bar" id="strengthBar"></div></div>
          <div class="pw-strength-label" id="strengthLabel"></div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirm">Confirm New Password</label>
          <div class="input-group">
            <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                   placeholder="Repeat new password" required autocomplete="new-password">
            <button type="button" class="toggle-pw" id="togglePw2">👁</button>
          </div>
        </div>

        <button type="submit" class="btn-submit">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="auth-footer"><a href="/auth/login.php">← Back to login</a></div>
</div>
<script>
  [['togglePw1','password'],['togglePw2','password_confirm']].forEach(([btnId, inputId]) => {
    document.getElementById(btnId)?.addEventListener('click', function() {
      const inp = document.getElementById(inputId);
      inp.type = inp.type === 'text' ? 'password' : 'text';
      this.textContent = inp.type === 'text' ? '🙈' : '👁';
    });
  });
  document.getElementById('password')?.addEventListener('input', function() {
    const val = this.value; const bar = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    if (!val) { bar.style.width='0'; label.textContent=''; return; }
    let s=0;
    if(val.length>=8)s++; if(val.length>=12)s++; if(/[A-Z]/.test(val))s++;
    if(/[0-9]/.test(val))s++; if(/[\W_]/.test(val))s++;
    const levels=[{w:'20%',c:'#ff4d4d',t:'Weak'},{w:'40%',c:'#ff8c42',t:'Fair'},
                  {w:'60%',c:'#ffc400',t:'Good'},{w:'80%',c:'#7bed9f',t:'Strong'},{w:'100%',c:'#2ed573',t:'Very Strong'}];
    const l=levels[Math.min(s,4)]; bar.style.width=l.w; bar.style.background=l.c;
    label.textContent=l.t; label.style.color=l.c;
  });
</script>
</body>
</html>
