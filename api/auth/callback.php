<?php
/**
 * CodeByTushu — Google OAuth Callback Handler
 * GET /api/auth/callback.php?code=xxx&state=yyy
 *
 * Handles the OAuth 2.0 authorization code exchange:
 * 1. Validates CSRF state token
 * 2. Exchanges auth code for access token
 * 3. Fetches Google user profile
 * 4. Creates or logs in the user
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();

// Already logged in
if (Auth::check()) redirect('/');

// ── 1. Check for OAuth errors ─────────────────────────────────────────────
$oauthError = $_GET['error'] ?? null;
if ($oauthError) {
    $msg = match ($oauthError) {
        'access_denied' => 'Google sign-in was cancelled.',
        default         => 'Google authentication failed. Please try again.',
    };
    redirectWithMessage('/auth/login.php', 'error', $msg);
}

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (!$code || !$state) {
    redirectWithMessage('/auth/login.php', 'error', 'Invalid OAuth response. Please try again.');
}

// ── 2. Validate CSRF state token ──────────────────────────────────────────
$stateValid = false;
$pdo        = db();

try {
    $stmt = $pdo->prepare(
        'SELECT id FROM oauth_states
          WHERE state = ? AND used = 0
            AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
          LIMIT 1'
    );
    $stmt->execute([$state]);
    $stateRow = $stmt->fetch();

    if ($stateRow) {
        $stateValid = true;
        // Mark used immediately (one-time token)
        $pdo->prepare('UPDATE oauth_states SET used = 1 WHERE id = ?')
            ->execute([$stateRow['id']]);
    }
} catch (\Throwable) {
    // Fallback: check session state
    $stateValid = isset($_SESSION['oauth_state']) && hash_equals($_SESSION['oauth_state'], $state);
    unset($_SESSION['oauth_state']);
}

if (!$stateValid) {
    redirectWithMessage('/auth/login.php', 'error', 'Invalid state token. Possible CSRF attack. Please try again.');
}

// ── 3. Exchange auth code for access token ────────────────────────────────
$tokenResponse = googleApiPost('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (!$tokenResponse || empty($tokenResponse['access_token'])) {
    redirectWithMessage('/auth/login.php', 'error', 'Failed to exchange Google auth code. Please try again.');
}

// ── 4. Fetch Google user profile ──────────────────────────────────────────
$profile = googleApiGet(
    'https://www.googleapis.com/oauth2/v3/userinfo',
    $tokenResponse['access_token']
);

if (!$profile || empty($profile['sub'])) {
    redirectWithMessage('/auth/login.php', 'error', 'Failed to retrieve Google profile. Please try again.');
}

// Verify email is confirmed by Google
if (empty($profile['email_verified']) || !$profile['email_verified']) {
    redirectWithMessage('/auth/login.php', 'error', 'Your Google email is not verified. Please verify it with Google first.');
}

// ── 5. Login or create user ───────────────────────────────────────────────
$result = Auth::loginOrCreateGoogleUser([
    'id'             => $profile['sub'],
    'email'          => $profile['email'],
    'name'           => $profile['name'] ?? 'Google User',
    'picture'        => $profile['picture'] ?? null,
    'email_verified' => true,
]);

if (!$result['success']) {
    redirectWithMessage('/auth/login.php', 'error', $result['error'] ?? 'Google sign-in failed.');
}

// ── 6. Redirect to intended destination ───────────────────────────────────
$defaultRedirect = Auth::isAdmin() ? SITE_URL . '/admin/' : SITE_URL . '/';
$intended = $_SESSION['redirect_after_login'] ?? $defaultRedirect;

// Normalize: if stored as relative path (starts with /), prepend SITE_URL
if (str_starts_with($intended, '/')) {
    $intended = SITE_URL . $intended;
}

// Safety: ensure the redirect stays on the same host (prevent open redirect)
$parsedIntended = parse_url($intended);
$parsedSiteUrl  = parse_url(SITE_URL);
if (!isset($parsedIntended['host']) || $parsedIntended['host'] !== $parsedSiteUrl['host']) {
    $intended = $defaultRedirect;
}

// Empty or root path → use default
if ($intended === '/' || $intended === SITE_URL . '/' || empty($intended)) {
    $intended = $defaultRedirect;
}
unset($_SESSION['redirect_after_login']);

redirectWithMessage($intended, 'success', 'Welcome, ' . ($result['user']['full_name'] ?? 'User') . '! 👋');

// ── Helpers ───────────────────────────────────────────────────────────────

/**
 * POST to Google API with JSON body.
 */
function googleApiPost(string $url, array $data): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err || !$body) return null;
    return json_decode($body, true);
}

/**
 * GET from Google API with Bearer token.
 */
function googleApiGet(string $url, string $accessToken): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $accessToken"],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err || !$body) return null;
    return json_decode($body, true);
}
