<?php
/**
 * CodeByTushu — Auth Middleware
 *
 * Reusable guard file. Include this at the TOP of any page that
 * requires a logged-in user or a specific role.
 *
 * Usage:
 *   // Require any logged-in user:
 *   $authRole = 'user';
 *   require_once __DIR__ . '/../includes/auth_middleware.php';
 *
 *   // Require admin or above:
 *   $authRole = 'admin';
 *   require_once __DIR__ . '/../includes/auth_middleware.php';
 *
 *   // Require super_admin only:
 *   $authRole = 'super_admin';
 *   require_once __DIR__ . '/../includes/auth_middleware.php';
 *
 * After inclusion, $authUser is available as the current user array.
 */

declare(strict_types=1);

// Expect these to already be loaded by the page that includes this file:
// - config/app.php, config/database.php, includes/functions.php, classes/Auth.php

Auth::boot();

$authRole ??= 'user'; // Default: require any authenticated user

$authUser = Auth::user();

if (!$authUser) {
    // Store the full absolute URL so redirect() doesn't prepend SITE_URL again
    $fullUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    $_SESSION['redirect_after_login'] = $fullUri;
    if (isAjax()) {
        jsonError('Authentication required. Please log in.', 401);
    }
    redirectWithMessage('/auth/login.php', 'error', 'Please log in to continue.');
}

// Role hierarchy check
$roleHierarchy = ['user' => 1, 'editor' => 2, 'admin' => 3, 'super_admin' => 4];
$userLevel     = $roleHierarchy[$authUser['role']] ?? 0;
$requiredLevel = $roleHierarchy[$authRole]         ?? 1;

if ($userLevel < $requiredLevel) {
    if (isAjax()) {
        jsonError('You do not have permission to perform this action.', 403);
    }
    http_response_code(403);
    // Show a friendly forbidden page
    $title   = '403 — Access Forbidden';
    $message = 'You do not have sufficient permissions to access this page.';
    require __DIR__ . '/../includes/error_page.php';
    exit;
}

// Make $authUser available to the including page
// (already set above, just confirming it's in scope)
