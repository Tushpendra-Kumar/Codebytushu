<?php
require_once __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deleted - <?= e(SITE_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body { background: #0f0f11; color: #fff; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; }
      .container { background: #1a1a1c; padding: 40px; border-radius: 16px; border: 1px solid #333; max-width: 400px; width: 90%; }
      svg { width: 64px; height: 64px; color: #22c55e; margin-bottom: 20px; }
      h2 { margin: 0 0 15px 0; font-size: 1.5rem; }
      p { color: #a1a1aa; margin: 0 0 30px 0; font-size: 0.95rem; line-height: 1.5; }
      a { display: inline-block; background: #eab308; color: #000; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; transition: opacity 0.2s; }
      a:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <h2>Account Deleted Successfully</h2>
        <p>Your account has been permanently deleted. Thank you for being a part of CodeByTushu.</p>
        <a href="/">Go to Home</a>
    </div>
</body>
</html>
