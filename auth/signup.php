<?php
/**
 * CodeByTushu — Signup Page
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Mailer.php';

Auth::boot();
Auth::redirectIfLoggedIn('/');

$errors   = [];
$formData = ['full_name' => '', 'username' => '', 'email' => ''];

if (isPost()) {
    requireCsrf();

    $fullName = sanitize(post('full_name'));
    $username = strtolower(sanitize(post('username')));
    $email    = sanitizeEmail(post('email'));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $terms    = isset($_POST['agree_terms']);

    $formData = ['full_name' => $fullName, 'username' => $username, 'email' => (string)$email];

    // Validation
    if (!$fullName || strlen($fullName) < 2)      $errors['full_name'] = 'Full name must be at least 2 characters.';
    if (!$username || !preg_match('/^[a-z0-9_]{3,30}$/', $username))
        $errors['username'] = 'Username: 3-30 chars, letters, numbers, underscores only.';
    if (!$email)                                   $errors['email']    = 'A valid email address is required.';
    if (strlen($password) < 8)                     $errors['password'] = 'Password must be at least 8 characters.';
    elseif (passwordStrength($password) === 'weak') $errors['password'] = 'Password is too weak. Add numbers and symbols.';
    if ($password !== $confirm)                    $errors['confirm']  = 'Passwords do not match.';
    if (!$terms)                                   $errors['terms']    = 'You must agree to the Terms of Service.';

    if (empty($errors)) {
        $result = Auth::register($fullName, $username, (string)$email, $password);
        if ($result['success']) {
            // Send verification email
            $token = Auth::createEmailVerificationToken($result['user_id']);
            try {
                $mailer = new Mailer();
                $mailer->sendEmailVerification((string)$email, $fullName, $token);
                $mailer->sendWelcome((string)$email, $fullName);
            } catch (\Throwable) { /* Non-fatal */ }

            redirectWithMessage(
                '/auth/login.php',
                'success',
                'Account created! Please check your email to verify your address, then log in.'
            );
        } else {
            $errors['general'] = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — CodeByTushu</title>
  <meta name="description" content="Join CodeByTushu — free access to daily LeetCode solutions, tutorials, and projects.">
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= SITE_URL ?>/favicon.ico?v=6" sizes="any">
  <link rel="icon" href="<?= SITE_URL ?>/favicon-32x32.png?v=6" type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= SITE_URL ?>/apple-touch-icon.png?v=6">
  <meta name="theme-color" content="#ffc400">
  <script src="/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0a0a0c; --card: #111118; --border: rgba(255,196,0,.14);
      --accent: #ffc400; --text: #f0f0f0; --muted: #888898;
      --danger: #ff4d4d; --input-bg: #16161e; --radius: 12px;
    }
    body {
      font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text);
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      padding: 40px 20px;
    }
    body::before {
      content: ''; position: fixed; inset: 0; z-index: 0;
      background:
        radial-gradient(ellipse 60% 50% at 20% 30%, rgba(255,196,0,.08) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 80% 70%, rgba(255,196,0,.05) 0%, transparent 60%);
    }
    .auth-wrapper { position: relative; z-index: 1; width: 100%; max-width: 480px; }
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
    .form-control {
      width: 100%; padding: 13px 16px; background: var(--input-bg);
      border: 1px solid var(--border); border-radius: var(--radius); color: var(--text);
      font-family: 'Poppins', sans-serif; font-size: 14px; outline: none;
      transition: border-color .2s;
    }
    .form-control:focus { border-color: var(--accent); }
    .form-control.is-invalid { border-color: var(--danger); }
    .form-control::placeholder { color: var(--muted); }
    .field-error { font-size: 12px; color: var(--danger); margin-top: 6px; }
    .input-group { position: relative; }
    .input-group .form-control { padding-right: 48px; }
    .toggle-pw { position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
                 background: none; border: none; cursor: pointer; color: var(--muted);
                 font-size: 18px; padding: 0; transition: color .2s; }
    .toggle-pw:hover { color: var(--accent); }

    /* Password strength */
    .pw-strength { margin-top: 8px; height: 4px; border-radius: 2px;
                   background: var(--border); overflow: hidden; }
    .pw-strength-bar { height: 100%; border-radius: 2px; width: 0; transition: width .3s, background .3s; }
    .pw-strength-label { font-size: 11px; color: var(--muted); margin-top: 4px; }

    /* Terms */
    .terms-label { display: flex; align-items: flex-start; gap: 10px; cursor: pointer;
                   font-size: 13px; color: var(--muted); line-height: 1.5; margin-bottom: 24px; }
    .terms-label input[type=checkbox] { accent-color: var(--accent); width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; }
    .terms-label a { color: var(--accent); text-decoration: none; }
    .terms-label a:hover { text-decoration: underline; }

    .btn-submit {
      width: 100%; padding: 14px; background: var(--accent); color: #000;
      border: none; border-radius: var(--radius); font-family: 'Poppins', sans-serif;
      font-size: 15px; font-weight: 700; cursor: pointer;
      transition: opacity .2s, transform .15s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { opacity: .9; transform: translateY(-1px); }
    .btn-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; }
    .spinner { display: none; width: 18px; height: 18px; border: 2px solid rgba(0,0,0,.3);
               border-top-color: #000; border-radius: 50%; animation: spin .7s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .divider { display: flex; align-items: center; gap: 12px; margin: 24px 0; }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
    .divider span { font-size: 12px; color: var(--muted); white-space: nowrap; }
    .btn-google {
      width: 100%; padding: 13px; background: var(--input-bg);
      border: 1px solid var(--border); border-radius: var(--radius); color: var(--text);
      font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 500; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 12px;
      text-decoration: none; transition: border-color .2s, background .2s;
    }
    .btn-google:hover { border-color: rgba(255,196,0,.4); background: rgba(255,196,0,.05); }
    .btn-google svg { width: 20px; height: 20px; }
    .auth-footer { text-align: center; margin-top: 24px; font-size: 13px; color: var(--muted); }
    .auth-footer a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .auth-footer a:hover { text-decoration: underline; }
    .back-link { text-align: center; margin-top: 20px; font-size: 13px; }
    .back-link a { color: var(--muted); text-decoration: none; }
    .back-link a:hover { color: var(--accent); }
    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 480px) { .form-row-2 { grid-template-columns: 1fr; } .auth-card { padding: 28px 20px; } }
  </style>
</head>
<body>
<div class="auth-wrapper">

  <div class="auth-brand">
    <a href="<?= SITE_URL ?>/">
      <div class="logo-text"><span class="white">CODE</span><span class="gold">BYTUSHU</span></div>
      <div class="brand-sub">Create Account</div>
    </a>
  </div>

  <div class="auth-card">
    <h1>Join for free</h1>
    <p class="subtitle">Get access to daily LeetCode solutions, tutorials, and projects</p>

    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error">⚠️ <?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= SITE_URL ?>/auth/signup.php" id="signupForm" novalidate>
      <?= csrfField() ?>

      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label" for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                 value="<?= e($formData['full_name']) ?>" placeholder="Tushpendra Kumar" required autocomplete="name">
          <?php if (!empty($errors['full_name'])): ?>
            <div class="field-error"><?= e($errors['full_name']) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                 value="<?= e($formData['username']) ?>" placeholder="tushu123" required autocomplete="username"
                 pattern="[a-z0-9_]{3,30}">
          <?php if (!empty($errors['username'])): ?>
            <div class="field-error"><?= e($errors['username']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               value="<?= e($formData['email']) ?>" placeholder="you@example.com" required autocomplete="email">
        <?php if (!empty($errors['email'])): ?>
          <div class="field-error"><?= e($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-group">
          <input type="password" id="password" name="password"
                 class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                 placeholder="Min. 8 characters" required autocomplete="new-password" minlength="8">
          <button type="button" class="toggle-pw" id="togglePw1">👁</button>
        </div>
        <div class="pw-strength"><div class="pw-strength-bar" id="strengthBar"></div></div>
        <div class="pw-strength-label" id="strengthLabel"></div>
        <?php if (!empty($errors['password'])): ?>
          <div class="field-error"><?= e($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirm">Confirm Password</label>
        <div class="input-group">
          <input type="password" id="password_confirm" name="password_confirm"
                 class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                 placeholder="Repeat password" required autocomplete="new-password">
          <button type="button" class="toggle-pw" id="togglePw2">👁</button>
        </div>
        <?php if (!empty($errors['confirm'])): ?>
          <div class="field-error"><?= e($errors['confirm']) ?></div>
        <?php endif; ?>
      </div>

      <label class="terms-label">
        <input type="checkbox" name="agree_terms" id="agreeTerms" <?= isset($_POST['agree_terms']) ? 'checked' : '' ?>>
        I agree to the <a href="<?= SITE_URL ?>/" target="_blank">Terms of Service</a>
        and <a href="<?= SITE_URL ?>/" target="_blank">Privacy Policy</a>
      </label>
      <?php if (!empty($errors['terms'])): ?>
        <div class="field-error" style="margin-top:-16px;margin-bottom:16px;"><?= e($errors['terms']) ?></div>
      <?php endif; ?>

      <button type="submit" class="btn-submit" id="submitBtn">
        <span class="spinner" id="spinner"></span>
        <span id="btnText">Create Account</span>
      </button>
    </form>

    <?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID): ?>
    <div class="divider"><span>or sign up with</span></div>
    <a href="<?= SITE_URL ?>/api/auth/google.php" class="btn-google">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
      </svg>
      Sign up with Google
    </a>
    <?php endif; ?>
  </div>

  <div class="auth-footer">
    Already have an account? <a href="<?= SITE_URL ?>/auth/login.php">Sign in</a>
  </div>
  <div class="back-link"><a href="<?= SITE_URL ?>/">← Back to CodeByTushu</a></div>
</div>

<script>
  // Toggle passwords
  [['togglePw1','password'],['togglePw2','password_confirm']].forEach(([btnId, inputId]) => {
    document.getElementById(btnId).addEventListener('click', function() {
      const inp = document.getElementById(inputId);
      const isText = inp.type === 'text';
      inp.type = isText ? 'password' : 'text';
      this.textContent = isText ? '👁' : '🙈';
    });
  });

  // Password strength meter
  document.getElementById('password').addEventListener('input', function() {
    const val   = this.value;
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    if (!val) { bar.style.width='0'; label.textContent=''; return; }
    let score = 0;
    if (val.length >= 8)            score++;
    if (val.length >= 12)           score++;
    if (/[A-Z]/.test(val))          score++;
    if (/[0-9]/.test(val))          score++;
    if (/[\W_]/.test(val))          score++;
    const levels = [
      { w:'20%', c:'#ff4d4d', t:'Weak' },
      { w:'40%', c:'#ff8c42', t:'Fair' },
      { w:'60%', c:'#ffc400', t:'Good' },
      { w:'80%', c:'#7bed9f', t:'Strong' },
      { w:'100%',c:'#2ed573', t:'Very Strong' },
    ];
    const l = levels[Math.min(score, 4)];
    bar.style.width = l.w; bar.style.background = l.c;
    label.textContent = l.t; label.style.color = l.c;
  });

  // Loading state
  document.getElementById('signupForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    const sp  = document.getElementById('spinner');
    const txt = document.getElementById('btnText');
    btn.disabled = true;
    sp.style.display = 'block';
    txt.textContent = 'Creating account…';
  });
</script>
</body>
</html>
