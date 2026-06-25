<?php
/**
 * CodeByTushu — Admin Dashboard Stats API
 * GET  /api/admin/dashboard.php          → full stats JSON
 * GET  /api/admin/dashboard.php?section=activity → activity feed only
 * GET  /api/admin/dashboard.php?section=stats    → KPI counters only
 *
 * Used by the dashboard page for live AJAX refresh every 60 seconds.
 * No POST required — read-only endpoint, admin-only.
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/classes/Auth.php';

Auth::boot();
if (!Auth::isAdmin()) jsonError('Forbidden.', 403);

$section = get('section', 'all');
$pdo     = db();

/* ── Helpers ─────────────────────────────────────────────────── */
function sq(PDO $pdo, string $sql): int {
    try { return (int)$pdo->query($sql)->fetchColumn(); }
    catch (\Throwable) { return 0; }
}

/* ── KPI Stats ────────────────────────────────────────────────── */
function buildStats(PDO $pdo): array {
    return [
        'total_users'      => sq($pdo, 'SELECT COUNT(*) FROM users WHERE role="user"'),
        'new_users_7d'     => sq($pdo, 'SELECT COUNT(*) FROM users WHERE role="user" AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)'),
        'total_users_all'  => sq($pdo, 'SELECT COUNT(*) FROM users'),
        'total_blogs'      => sq($pdo, 'SELECT COUNT(*) FROM blog_articles'),
        'pub_blogs'        => sq($pdo, 'SELECT COUNT(*) FROM blog_articles WHERE is_published=1'),
        'total_leet'       => sq($pdo, 'SELECT COUNT(*) FROM leetcode_solutions'),
        'pub_leet'         => sq($pdo, 'SELECT COUNT(*) FROM leetcode_solutions WHERE is_published=1'),
        'total_courses'    => sq($pdo, 'SELECT COUNT(*) FROM courses'),
        'pub_courses'      => sq($pdo, 'SELECT COUNT(*) FROM courses WHERE is_published=1'),
        'total_msgs'       => sq($pdo, 'SELECT COUNT(*) FROM contact_messages'),
        'unread_msgs'      => sq($pdo, 'SELECT COUNT(*) FROM contact_messages WHERE is_read=0'),
        'visitors_today'   => sq($pdo, 'SELECT COUNT(*) FROM page_visits WHERE DATE(visited_at)=CURDATE() AND device_type!="bot"'),
        'visitors_7d'      => sq($pdo, 'SELECT COUNT(*) FROM page_visits WHERE visited_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) AND device_type!="bot"'),
        'visitors_30d'     => sq($pdo, 'SELECT COUNT(*) FROM page_visits WHERE visited_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) AND device_type!="bot"'),
        'total_uploads'    => sq($pdo, 'SELECT COUNT(*) FROM file_uploads WHERE is_active=1'),
        'banned_users'     => sq($pdo, 'SELECT COUNT(*) FROM users WHERE status="banned"'),
        'pending_users'    => sq($pdo, 'SELECT COUNT(*) FROM users WHERE status="pending"'),
    ];
}

/* ── Activity Feed ────────────────────────────────────────────── */
function buildActivity(PDO $pdo): array {
    $items = [];

    // New users (last 10)
    try {
        $rows = $pdo->query(
            'SELECT id, full_name, email, created_at, profile_image, role
               FROM users ORDER BY created_at DESC LIMIT 8'
        )->fetchAll();
        foreach ($rows as $r) {
            $items[] = [
                'type'    => 'user_joined',
                'icon'    => 'user',
                'color'   => 'info',
                'title'   => htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8') . ' joined',
                'sub'     => htmlspecialchars($r['email'], ENT_QUOTES, 'UTF-8'),
                'time'    => $r['created_at'],
                'url'     => '/admin/users.php',
                'avatar'  => $r['profile_image'] ?? null,
                'initials'=> strtoupper(substr($r['full_name'], 0, 1)),
            ];
        }
    } catch (\Throwable) {}

    // New messages (last 8)
    try {
        $rows = $pdo->query(
            'SELECT id, name, email, subject, submitted_at, is_read
               FROM contact_messages ORDER BY submitted_at DESC LIMIT 8'
        )->fetchAll();
        foreach ($rows as $r) {
            $items[] = [
                'type'  => 'message_received',
                'icon'  => 'mail',
                'color' => $r['is_read'] ? 'muted' : 'warning',
                'title' => htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') . ' sent a message',
                'sub'   => htmlspecialchars($r['subject'] ?: 'No subject', ENT_QUOTES, 'UTF-8'),
                'time'  => $r['submitted_at'],
                'url'   => '/admin/messages.php?id=' . (int)$r['id'],
                'unread'=> !(bool)$r['is_read'],
            ];
        }
    } catch (\Throwable) {}

    // New LeetCode solutions (last 5)
    try {
        $rows = $pdo->query(
            'SELECT id, problem_number, title, difficulty, created_at
               FROM leetcode_solutions ORDER BY created_at DESC LIMIT 5'
        )->fetchAll();
        foreach ($rows as $r) {
            $items[] = [
                'type'  => 'solution_added',
                'icon'  => 'code',
                'color' => 'accent',
                'title' => '#' . (int)$r['problem_number'] . ' ' . htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') . ' added',
                'sub'   => 'Difficulty: ' . htmlspecialchars($r['difficulty'], ENT_QUOTES, 'UTF-8'),
                'time'  => $r['created_at'],
                'url'   => '/admin/leetcode.php',
            ];
        }
    } catch (\Throwable) {}

    // New blog posts (last 5)
    try {
        $rows = $pdo->query(
            'SELECT id, title, is_published, created_at
               FROM blog_articles ORDER BY created_at DESC LIMIT 5'
        )->fetchAll();
        foreach ($rows as $r) {
            $items[] = [
                'type'  => 'blog_added',
                'icon'  => 'edit',
                'color' => 'success',
                'title' => 'Blog post "' . htmlspecialchars(mb_substr($r['title'], 0, 40), ENT_QUOTES, 'UTF-8') . '" ' . ($r['is_published'] ? 'published' : 'drafted'),
                'sub'   => $r['is_published'] ? 'Published' : 'Saved as draft',
                'time'  => $r['created_at'],
                'url'   => '/admin/blogs.php',
            ];
        }
    } catch (\Throwable) {}

    // Sort all activity by time descending, take top 15
    usort($items, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
    return array_slice($items, 0, 15);
}

/* ── Monthly Chart Data ───────────────────────────────────────── */
function buildMonthlyChart(PDO $pdo): array {
    try {
        $rows = $pdo->query(
            "SELECT DATE_FORMAT(visited_at,'%Y-%m') AS month,
                    COUNT(*) AS visits,
                    COUNT(DISTINCT ip_address) AS unique_v
               FROM page_visits
              WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                AND device_type != 'bot'
              GROUP BY month ORDER BY month ASC"
        )->fetchAll();
        return $rows;
    } catch (\Throwable) { return []; }
}

/* ── Latest Users ─────────────────────────────────────────────── */
function buildLatestUsers(PDO $pdo): array {
    try {
        return $pdo->query(
            'SELECT id, full_name, email, role, status, created_at, profile_image, last_login
               FROM users ORDER BY created_at DESC LIMIT 8'
        )->fetchAll();
    } catch (\Throwable) { return []; }
}

/* ── Latest Messages ──────────────────────────────────────────── */
function buildLatestMessages(PDO $pdo): array {
    try {
        return $pdo->query(
            'SELECT id, name, email, subject, is_read, submitted_at, source_page
               FROM contact_messages ORDER BY submitted_at DESC LIMIT 8'
        )->fetchAll();
    } catch (\Throwable) { return []; }
}

/* ── Device breakdown (doughnut chart) ───────────────────────── */
function buildDeviceChart(PDO $pdo): array {
    try {
        return $pdo->query(
            "SELECT device_type, COUNT(*) AS cnt
               FROM page_visits
              WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND device_type != 'bot'
              GROUP BY device_type"
        )->fetchAll();
    } catch (\Throwable) { return []; }
}

/* ── Build response ──────────────────────────────────────────── */
$response = ['success' => true, 'generated_at' => date('c')];

if ($section === 'stats' || $section === 'all') {
    $response['stats'] = buildStats($pdo);
}
if ($section === 'activity' || $section === 'all') {
    $response['activity'] = buildActivity($pdo);
}
if ($section === 'chart' || $section === 'all') {
    $response['monthly_chart'] = buildMonthlyChart($pdo);
    $response['device_chart']  = buildDeviceChart($pdo);
}
if ($section === 'users' || $section === 'all') {
    $response['latest_users'] = buildLatestUsers($pdo);
}
if ($section === 'messages' || $section === 'all') {
    $response['latest_messages'] = buildLatestMessages($pdo);
}

jsonResponse($response);
