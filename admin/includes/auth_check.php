<?php
/**
 * Admin Panel — Auth Guard
 * Include at the very top of EVERY admin page.
 * Boots Auth, enforces admin role, sets $user.
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/classes/Auth.php';

Auth::boot();
Auth::requireAdmin();

$user        = Auth::user();
$adminSection = $adminSection ?? 'Dashboard';   // Pages set this before including
$adminTitle   = $adminTitle   ?? 'Admin Panel — CodeByTushu';
$breadcrumbs  = $breadcrumbs  ?? [];            // [['label'=>'', 'url'=>''], ...]
