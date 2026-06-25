<?php
/**
 * CodeByTushu — API Auth: Resend Verification Email
 * POST /api/auth/resend-verification.php
 *
 * Lets a user request a new email verification token.
 * Rate-limited to 1 request per 5 minutes per user.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Mailer.php';

Auth::boot();
requireCsrf();

if (!Auth::check()) jsonError('Login required.', 401);

$user = Auth::user();

// Already verified
if ($user['email_verified']) {
    jsonSuccess(null, 'Your email is already verified.');
}

// Rate limit: session-based, 1 per 5 minutes
if (!empty($_SESSION['last_resend_at']) && time() - $_SESSION['last_resend_at'] < 300) {
    $wait = 300 - (time() - $_SESSION['last_resend_at']);
    jsonError("Please wait {$wait} seconds before requesting another verification email.", 429);
}

// Invalidate any pending verification tokens for this user
$pdo = db();
$pdo->prepare('UPDATE email_verifications SET used = 1 WHERE user_id = ? AND used = 0')
    ->execute([$user['id']]);

// Create new token
$token = Auth::createEmailVerificationToken($user['id']);

try {
    $mailer = new Mailer();
    $mailer->sendEmailVerification($user['email'], $user['full_name'], $token);
    $_SESSION['last_resend_at'] = time();
    jsonSuccess(null, 'Verification email sent! Check your inbox.');
} catch (\Throwable $e) {
    jsonError(APP_DEBUG ? 'Mail error: ' . $e->getMessage() : 'Failed to send email. Please try again later.');
}
