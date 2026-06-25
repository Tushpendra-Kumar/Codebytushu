<?php
/**
 * CodeByTushu — Email Verification Page
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();

$token = get('token', '');
$result = ['success' => false, 'message' => 'Invalid or expired verification link.'];

if ($token) {
    $result = Auth::verifyEmail($token);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Email Verification — CodeByTushu</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="/favicon.ico?v=6" sizes="any">
  <meta name="theme-color" content="#ffc400">
  <script src="/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--bg:#0a0a0c;--card:#111118;--border:rgba(255,196,0,.14);--accent:#ffc400;--text:#f0f0f0;--muted:#888898}
    body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);
         min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
    body::before{content:'';position:fixed;inset:0;z-index:0;
      background:radial-gradient(ellipse 60% 50% at 50% 30%,rgba(255,196,0,.08) 0%,transparent 60%);}
    .box{position:relative;z-index:1;text-align:center;max-width:420px;
         background:var(--card);border:1px solid var(--border);border-radius:20px;
         padding:48px 36px;box-shadow:0 24px 64px rgba(0,0,0,.6);}
    .icon{font-size:64px;margin-bottom:20px;}
    h1{font-size:22px;font-weight:700;margin-bottom:12px;}
    p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:24px;}
    .btn{display:inline-block;padding:12px 32px;background:var(--accent);color:#000;
         border-radius:12px;font-weight:700;text-decoration:none;font-size:14px;
         transition:opacity .2s;}
    .btn:hover{opacity:.9}
    .btn-ghost{background:none;border:1px solid var(--border);color:var(--muted);margin-left:10px;}
    .btn-ghost:hover{border-color:rgba(255,196,0,.3);color:var(--text);}
  </style>
</head>
<body>
<div class="box">
  <?php if ($result['success']): ?>
    <div class="icon">✅</div>
    <h1>Email Verified!</h1>
    <p>Your email address has been successfully verified. You can now access all features of CodeByTushu.</p>
    <a href="/auth/login.php" class="btn">Sign In Now</a>
  <?php else: ?>
    <div class="icon">❌</div>
    <h1>Verification Failed</h1>
    <p><?= e($result['message']) ?></p>
    <a href="/auth/signup.php" class="btn">Sign Up Again</a>
    <a href="/" class="btn btn-ghost">Back to Site</a>
  <?php endif; ?>
</div>
</body>
</html>
