<?php
/**
 * CodeByTushu — Forgot Password Page
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Mailer.php';

Auth::boot();
Auth::redirectIfLoggedIn('/');

$sent   = false;
$error  = '';

if (isPost()) {
    requireCsrf();
    $email = sanitizeEmail(post('email'));

    // Rate limit: max 5 reset requests per IP per hour
    $ip = clientIp();
    try {
        $rl = db()->prepare(
            'SELECT COUNT(*) FROM password_resets
              WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND email IN (SELECT email FROM users WHERE id IN (
                  SELECT id FROM users WHERE id > 0
                ))'
        );
        // Simpler IP-based check via login_attempts pattern
        $rlStmt = db()->prepare(
            "SELECT COUNT(*) FROM password_resets pr
              JOIN users u ON u.email = pr.email
             WHERE pr.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
               AND pr.created_at IS NOT NULL LIMIT 1"
        );
        // Use session counter as lightweight rate limiter for password resets
        if (!isset($_SESSION['pw_reset_attempts'])) $_SESSION['pw_reset_attempts'] = [];
        $_SESSION['pw_reset_attempts'] = array_filter(
            $_SESSION['pw_reset_attempts'],
            fn($t) => time() - $t < 3600
        );
        if (count($_SESSION['pw_reset_attempts']) >= 5) {
            $error = 'Too many password reset requests. Please wait before trying again.';
            $email = null;
        }
    } catch (\Throwable) {}

    if (!$email && !$error) {
        $error = 'Please enter a valid email address.';
    } elseif ($email) {
        $_SESSION['pw_reset_attempts'][] = time();

        // Always show the same success message regardless of whether email exists
        // (prevents email enumeration)
        $token = Auth::createPasswordResetToken((string)$email);
        if ($token) {
            // Load user name for email personalisation
            $stmt = db()->prepare('SELECT full_name FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $name = $user['full_name'] ?? 'there';

            try {
                $mailer = new Mailer();
                $mailer->sendPasswordReset((string)$email, $name, $token);
            } catch (\Throwable $e) {
                if (APP_DEBUG) $error = 'Mail error: ' . $e->getMessage();
            }
        }
        $sent = true; // Always show "check your email" — don't leak user existence
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — CodeByTushu</title>
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
    .auth-card .subtitle { font-size: 14px; color: var(--muted); margin-bottom: 28px; line-height: 1.6; }
    .alert { padding: 12px 16px; border-radius: var(--radius); margin-bottom: 20px;
             font-size: 13px; display: flex; align-items: flex-start; gap: 10px; }
    .alert-error   { background: rgba(255,77,77,.12); border: 1px solid rgba(255,77,77,.3); color: #ff6b6b; }
    .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #22c55e; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 500; color: var(--muted); margin-bottom: 8px; }
    .form-control { width: 100%; padding: 13px 16px; background: var(--input-bg);
                    border: 1px solid var(--border); border-radius: var(--radius); color: var(--text);
                    font-family: 'Poppins', sans-serif; font-size: 14px; outline: none; transition: border-color .2s; }
    .form-control:focus { border-color: var(--accent); }
    .form-control::placeholder { color: var(--muted); }
    .btn-submit { width: 100%; padding: 14px; background: var(--accent); color: #000;
                  border: none; border-radius: var(--radius); font-family: 'Poppins', sans-serif;
                  font-size: 15px; font-weight: 700; cursor: pointer;
                  transition: opacity .2s, transform .15s; }
    .btn-submit:hover { opacity: .9; transform: translateY(-1px); }
    .success-state { text-align: center; padding: 20px 0; }
    .success-icon { font-size: 56px; margin-bottom: 16px; }
    .success-state h2 { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
    .success-state p { color: var(--muted); font-size: 14px; line-height: 1.7; }
    .auth-footer { text-align: center; margin-top: 24px; font-size: 13px; color: var(--muted); }
    .auth-footer a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .auth-footer a:hover { text-decoration: underline; }
    .back-link { text-align: center; margin-top: 16px; font-size: 13px; }
    .back-link a { color: var(--muted); text-decoration: none; }
    .back-link a:hover { color: var(--accent); }
  </style>
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-brand">
    <a href="/">
      <div class="logo-text"><span class="white">CODE</span><span class="gold">BYTUSHU</span></div>
      <div class="brand-sub">Password Reset</div>
    </a>
  </div>

  <div class="auth-card">
    <?php if ($sent): ?>
      <div class="success-state">
        <div class="success-icon">📬</div>
        <h2>Check your inbox</h2>
        <p>If an account exists for that email address, we've sent a password reset link.
           <br><br>The link expires in <strong>1 hour</strong>.</p>
      </div>
    <?php else: ?>
      <h1>Forgot password?</h1>
      <p class="subtitle">Enter your email and we'll send you a link to reset your password.</p>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= SITE_URL ?>/auth/forgot-password.php" novalidate>
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="you@example.com" required autocomplete="email"
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-submit">Send Reset Link</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="auth-footer">
    <a href="/auth/login.php">← Back to login</a>
  </div>
  <div class="back-link"><a href="/">← Back to CodeByTushu</a></div>
</div>
</body>
</html>
