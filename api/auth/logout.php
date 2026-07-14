<?php
/**
 * CodeByTushu — AJAX Logout Endpoint
 * POST /api/auth/logout.php
 *
 * Supports both browser redirect (POST form) and AJAX logout.
 * Requires CSRF token.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();

// Allow GET logout via simple navbar link (browser redirect)
// POST+CSRF is still required for AJAX logout calls
if (!isPost() && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Simple GET logout — clear session and redirect
    Auth::logout();
    redirect('/', 302);
}

if (!isPost()) {
    jsonError('Method not allowed.', 405);
}

requireCsrf();

$userId = Auth::id();

// Revoke session in DB if registered
if ($userId) {
    try {
        $sid = session_id();
        if ($sid) {
            db()->prepare('UPDATE user_sessions SET is_revoked = 1 WHERE id = ?')
               ->execute([$sid]);
        }
    } catch (\Throwable) {}
}

Auth::logout();

if (isAjax()) {
    jsonSuccess(null, 'Logged out successfully.');
}

redirectWithMessage('/', 'success', 'You have been signed out.');
