<?php
/**
 * Admin Panel — <head> partial
 * Outputs everything between <html> and </head>.
 *
 * Required variables (set before including):
 *   $adminTitle  — page <title>
 *   $user        — current admin user array
 */
$adminTitle ??= 'Admin Panel — CodeByTushu';
$extraHead  ??= '';   // Pages can inject extra <link> or <meta> tags

// Unread messages count for header badge
$pdo = db();
try {
    $unreadCount = (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    $newUsers    = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();
} catch (\Throwable) {
    $unreadCount = 0;
    $newUsers    = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($adminTitle) ?></title>

  <!-- Favicon -->
  <link rel="icon" href="<?= SITE_URL ?>/favicon.ico?v=6" sizes="any">
  <link rel="apple-touch-icon" href="<?= SITE_URL ?>/apple-touch-icon.png?v=6">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- FontAwesome for EasyMDE Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Admin CSS -->
  <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css?v=2">

  <!-- Theme flash prevention (must be inline, before CSS) -->
  <script>
    (function() {
      const t = localStorage.getItem('cbt_admin_theme') || 'dark';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>

  <?= $extraHead ?>
</head>
