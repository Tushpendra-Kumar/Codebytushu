<?php
/**
 * CodeByTushu — Logout Handler
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();
Auth::logout();
redirectWithMessage('/auth/login.php', 'success', 'You have been signed out successfully.');
