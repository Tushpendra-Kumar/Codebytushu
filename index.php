<?php
/**
 * CodeByTushu — Public Website Placeholder
 * This file prevents 403 Forbidden / directory listing errors
 * until the actual frontend is built in Phase 4.
 */
require_once __DIR__ . '/config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CodeByTushu — Coming Soon</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #0f1115;
      color: #fff;
      font-family: system-ui, -apple-system, sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      text-align: center;
    }
    h1 {
      font-size: 3rem;
      margin-bottom: 10px;
    }
    .gold { color: #ffc400; }
    p {
      color: #94a3b8;
      font-size: 1.2rem;
      max-width: 600px;
      margin-bottom: 30px;
    }
    .links a {
      display: inline-block;
      margin: 0 10px;
      padding: 10px 20px;
      background-color: #1e222a;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      border: 1px solid #334155;
      transition: all 0.2s;
    }
    .links a:hover {
      background-color: #292d36;
      border-color: #ffc400;
      color: #ffc400;
    }
  </style>
</head>
<body>
  <h1>CODE<span class="gold">BYTUSHU</span></h1>
  <p>The public website is currently under construction. Phase 4 development will begin shortly.</p>
  
  <div class="links">
    <a href="<?= SITE_URL ?>/auth/login.php">Login</a>
    <a href="<?= SITE_URL ?>/admin/">Admin Panel</a>
  </div>
</body>
</html>
