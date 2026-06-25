<?php
/**
 * Error page partial — used by auth_middleware.php for 403 errors.
 * Variables available: $title, $message
 */
$title   ??= 'Access Denied';
$message ??= 'You do not have permission to view this page.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= e($title) ?> — CodeByTushu</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="/favicon.ico?v=6" sizes="any">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Poppins',sans-serif;background:#0a0a0c;color:#f0f0f0;
         min-height:100vh;display:flex;align-items:center;justify-content:center;
         padding:20px;text-align:center;}
    body::before{content:'';position:fixed;inset:0;z-index:0;
      background:radial-gradient(ellipse 60% 50% at 50% 30%,rgba(255,77,77,.07) 0%,transparent 60%);}
    .box{position:relative;z-index:1;max-width:500px;}
    .code{font-size:100px;font-weight:800;line-height:1;
          background:linear-gradient(135deg,#ff4d4d,#ff8c00);
          -webkit-background-clip:text;-webkit-text-fill-color:transparent;
          background-clip:text;margin-bottom:12px;}
    h1{font-size:22px;font-weight:700;margin-bottom:10px;}
    p{font-size:14px;color:#888898;line-height:1.7;margin-bottom:28px;}
    .btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
    .btn{padding:12px 28px;border-radius:12px;font-family:'Poppins',sans-serif;
         font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .2s;}
    .btn-primary{background:#ffc400;color:#000;}
    .btn-primary:hover{opacity:.9}
    .btn-ghost{background:none;border:1px solid rgba(255,196,0,.2);color:#888898;}
    .btn-ghost:hover{color:#f0f0f0;}
  </style>
</head>
<body>
<div class="box">
  <div class="code">403</div>
  <h1><?= e($title) ?></h1>
  <p><?= e($message) ?></p>
  <div class="btns">
    <a href="/" class="btn btn-primary">🏠 Home</a>
    <a href="javascript:history.back()" class="btn btn-ghost">← Go Back</a>
  </div>
</div>
</body>
</html>
