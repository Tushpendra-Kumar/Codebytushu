<?php
/**
 * CodeByTushu — Page Visit Tracker
 * Include this at the top of every public PHP page to record visits.
 * Auto-detects device type, referrer, and skips bots + admin users.
 */

declare(strict_types=1);

function trackPageVisit(): void
{
    static $tracked = false;
    if ($tracked) return;
    $tracked = true;

    // Skip admin and editor roles
    if (function_exists('Auth::check') || class_exists('Auth')) {
        try {
            Auth::boot();
            if (Auth::isAdmin()) return;
        } catch (\Throwable) {}
    }

    $ua        = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceType = detectDevice($ua);

    // Skip known bots
    if ($deviceType === 'bot') return;

    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $referrer = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);
    $pageUrl  = substr(($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''), 0, 500);
    $sessionId = session_id() ?: null;

    try {
        $pdo = db();
        $pdo->prepare(
            'INSERT INTO page_visits
               (session_id, ip_address, page_url, referrer, user_agent, browser, device_type)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $sessionId,
            $ip,
            $pageUrl,
            $referrer ?: null,
            substr($ua, 0, 500),
            parseBrowser($ua),
            $deviceType,
        ]);
    } catch (\Throwable) {
        // Never break the page — analytics failures are silent
    }
}

function detectDevice(string $ua): string
{
    $ua = strtolower($ua);

    // Bot patterns
    $botPatterns = [
        'bot', 'crawl', 'spider', 'slurp', 'googlebot', 'bingbot', 'facebookexternalhit',
        'twitterbot', 'linkedinbot', 'ahrefsbot', 'semrushbot', 'mj12bot', 'wget', 'curl',
        'python-requests', 'libwww-perl', 'go-http-client',
    ];
    foreach ($botPatterns as $bot) {
        if (str_contains($ua, $bot)) return 'bot';
    }

    // Mobile (check before tablet)
    if (preg_match('/(android|iphone|ipod|blackberry|windows phone|mobile)/i', $ua)) {
        return 'mobile';
    }

    // Tablet
    if (preg_match('/(ipad|tablet|kindle|playbook|silk)/i', $ua)) {
        return 'tablet';
    }

    return 'desktop';
}
