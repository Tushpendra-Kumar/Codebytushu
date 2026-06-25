<?php
/**
 * CodeByTushu — 404 / Error Page
 */
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Auth.php';
Auth::boot();

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>404 — Page Not Found · CodeByTushu</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="/favicon.ico?v=6" sizes="any">
  <meta name="theme-color" content="#ffc400">
  <script src="/theme.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--bg:#0a0a0c;--accent:#ffc400;--text:#f0f0f0;--muted:#888898;}
    body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);
         min-height:100vh;display:flex;align-items:center;justify-content:center;
         padding:20px;text-align:center;}
    body::before{content:'';position:fixed;inset:0;z-index:0;
      background:radial-gradient(ellipse 70% 60% at 50% 30%,rgba(255,196,0,.08) 0%,transparent 60%);}
    .box{position:relative;z-index:1;max-width:560px;}
    .num{font-size:120px;font-weight:800;line-height:1;
         background:linear-gradient(135deg,#ffc400,#ff8c00);
         -webkit-background-clip:text;-webkit-text-fill-color:transparent;
         background-clip:text;margin-bottom:16px;}
    h1{font-size:24px;font-weight:700;margin-bottom:12px;}
    p{font-size:15px;color:var(--muted);line-height:1.7;margin-bottom:32px;}
    .btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
    .btn{padding:13px 28px;border-radius:12px;font-family:'Poppins',sans-serif;
         font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .2s;}
    .btn-gold{background:var(--accent);color:#000;}
    .btn-gold:hover{opacity:.9;}
    .btn-ghost{background:none;border:1px solid rgba(255,196,0,.25);color:var(--muted);}
    .btn-ghost:hover{border-color:rgba(255,196,0,.5);color:var(--text);}
    .suggestions{margin-top:40px;display:flex;flex-wrap:wrap;gap:10px;justify-content:center;}
    .sug{display:flex;align-items:center;gap:8px;padding:10px 18px;
         background:rgba(255,255,255,.04);border:1px solid rgba(255,196,0,.1);
         border-radius:20px;font-size:13px;color:var(--muted);text-decoration:none;
         transition:all .2s;}
    .sug:hover{border-color:rgba(255,196,0,.3);color:var(--text);}
  </style>
</head>
<body>
<div class="box">
  <div class="num">404</div>
  <h1>Page not found</h1>
  <p>The page you're looking for doesn't exist or has been moved.<br>
     Here are some helpful links instead:</p>
  <div class="btns">
    <a href="/" class="btn btn-gold">🏠 Go Home</a>
    <a href="/Leetcode/" class="btn btn-ghost">💡 LeetCode</a>
  </div>
  <div class="suggestions">
    <a href="/" class="sug">🏠 Portfolio</a>
    <a href="/Leetcode/" class="sug">💡 LeetCode Solutions</a>
    <a href="/Video-Editor/" class="sug">🎬 Video Editing</a>
    <a href="/web-dev/" class="sug">💻 Web Dev</a>
  </div>
</div>
</body>
</html>
