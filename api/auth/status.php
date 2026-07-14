<?php
/**
 * CodeByTushu — Auth Status API
 * GET /api/auth/status.php
 * Returns JSON: { logged_in: bool, user: {...} }
 * Used by the frontend navbar to swap Login -> Avatar.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

Auth::boot();

if (!Auth::check()) {
    echo json_encode(['logged_in' => false, 'user' => null]);
    exit;
}

$user = Auth::user();

echo json_encode([
    'logged_in' => true,
    'user' => [
        'id'            => $user['id'] ?? null,
        'full_name'     => $user['full_name'] ?? '',
        'username'      => $user['username'] ?? '',
        'email'         => $user['email'] ?? '',
        'profile_image' => $user['profile_image'] ?? $user['avatar'] ?? '',
        'role'          => $user['role'] ?? 'user',
    ],
]);
