<?php
/**
 * CodeByTushu — Frontend Auth Status API
 * GET /api/auth/status.php
 * Returns JSON containing the current user's session status.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();

if (Auth::check()) {
    $user = Auth::user();
    jsonSuccess([
        'logged_in' => true,
        'user' => [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            // Add a default avatar if none exists
            'photo_url' => $user['avatar'] ?? null
        ]
    ]);
} else {
    jsonResponse([
        'success' => true,
        'data' => [
            'logged_in' => false,
            'user' => null
        ]
    ]);
}
