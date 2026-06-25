<?php
/**
 * Contact Form API — receives POST from all contact forms on the site.
 * Rate-limited per IP. CSRF required. Saves to DB + sends admin notification.
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Mailer.php';
Auth::boot();

// CORS: only allow same origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && !str_contains($origin, parse_url(SITE_URL, PHP_URL_HOST))) {
    jsonError('Forbidden', 403);
}

// CSRF check
requireCsrf();

// Rate limiting: max 3 submissions per IP per hour
$pdo = db();
$ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM contact_messages WHERE ip_address = ? AND submitted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
);
$stmt->execute([$ip]);
if ((int)$stmt->fetchColumn() >= 3) {
    jsonError('Too many submissions. Please wait before sending another message.', 429);
}

// Validate fields
$name    = sanitize(post('name'));
$email   = sanitizeEmail(post('email'));
$subject = sanitize(post('subject') ?: 'General Enquiry');
$message = sanitize(post('message'));
$source  = post('source_page') ?: 'main_portfolio';

$errors = [];
if (!$name || strlen($name) < 2)       $errors[] = 'Please enter your full name (min 2 characters).';
if (!$email)                            $errors[] = 'A valid email address is required.';
if (!$message || strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';
if (strlen($message) > 5000)           $errors[] = 'Message is too long (max 5000 characters).';

// Honeypot check (bot trap)
if (!empty($_POST['website'])) {
    // Silent discard — bots fill hidden fields
    jsonSuccess(null, 'Thank you! Your message has been sent.');
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Save to database
$userId = Auth::check() ? Auth::id() : null;
$insert = $pdo->prepare(
    'INSERT INTO contact_messages
       (user_id, name, email, subject, message, source_page, ip_address, user_agent)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$insert->execute([
    $userId,
    $name,
    (string)$email,
    $subject,
    $message,
    $source,
    $ip,
    truncate($_SERVER['HTTP_USER_AGENT'] ?? '', 255),
]);
$msgId = (int)$pdo->lastInsertId();

// Send admin notification email (non-blocking)
try {
    $mailer = new Mailer();
    $mailer->sendContactNotification([
        'name'        => $name,
        'email'       => (string)$email,
        'subject'     => $subject,
        'message'     => $message,
        'source_page' => $source,
    ]);
} catch (\Throwable) {
    // Non-fatal — message already saved to DB
}

jsonSuccess(['id' => $msgId], 'Thank you! Your message has been received. I\'ll get back to you soon.');
