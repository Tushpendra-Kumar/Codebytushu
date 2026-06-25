<?php
/**
 * CodeByTushu — Google OAuth Initiator
 * GET /api/auth/google.php
 *
 * Redirects the user to Google's OAuth 2.0 authorization endpoint
 * with a CSRF state token stored in the database.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();

// Already logged in — go home
if (Auth::check()) redirect('/');

// Google OAuth must be configured
if (!GOOGLE_CLIENT_ID) {
    redirect('/auth/login.php?error=google_not_configured');
}

// Generate cryptographic state to prevent CSRF
$state = bin2hex(random_bytes(24));
$ip    = clientIp();

// Store state in DB (expires in 15 minutes)
try {
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO oauth_states (state, ip_address) VALUES (?, ?)'
    )->execute([$state, $ip]);
} catch (\Throwable $e) {
    // Fallback: store in session if DB is unavailable
    $_SESSION['oauth_state'] = $state;
}

// Build Google OAuth URL
$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params, 302);
