<?php
/**
 * CodeByTushu — Global Helper Functions
 * Pure functions — no side effects, no DB calls.
 * Available throughout every PHP file.
 */

declare(strict_types=1);

// ── Output Security ─────────────────────────────────────────────────────

/**
 * Escape a string (or int/float) for safe HTML output.
 */
function e(mixed $value): string {
    if ($value === null) return '';
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Echo an escaped string.
 */
function ee(mixed $value): void {
    echo e($value);
}

// ── Redirects ───────────────────────────────────────────────────────────

function redirect(string $url, int $code = 302): never {
    if (str_starts_with($url, '/')) {
        $url = SITE_URL . $url;
    }
    header("Location: $url", true, $code);
    exit;
}

function redirectWithMessage(string $url, string $type, string $message): never {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    redirect($url);
}

// ── Flash Messages ───────────────────────────────────────────────────────

function flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function flashHtml(): string {
    $flash = flash();
    if (!$flash) return '';
    $type    = e($flash['type']);   // success | error | warning | info
    $message = e($flash['message']);
    return <<<HTML
    <div class="alert alert-{$type}" role="alert" id="flash-message">
        <span>{$message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
    </div>
    HTML;
}

// ── Input Sanitization ───────────────────────────────────────────────────

function sanitize(string $input): string {
    return trim(strip_tags($input));
}

function sanitizeEmail(string $email): string|false {
    $clean = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($clean, FILTER_VALIDATE_EMAIL) ? strtolower($clean) : false;
}

function sanitizeUrl(string $url): string|false {
    $clean = filter_var(trim($url), FILTER_SANITIZE_URL);
    return filter_var($clean, FILTER_VALIDATE_URL) ? $clean : false;
}

function sanitizeInt(mixed $value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int|false {
    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
    return $int;
}

// ── JSON Response ────────────────────────────────────────────────────────

function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonSuccess(mixed $data = null, string $message = 'Success', int $code = 200): never {
    $payload = ['success' => true, 'message' => $message];
    if ($data !== null) $payload['data'] = $data;
    jsonResponse($payload, $code);
}

function jsonError(string $message, int $code = 400, ?string $field = null): never {
    $payload = ['success' => false, 'error' => $message, 'code' => $code];
    if ($field !== null) $payload['field'] = $field;
    jsonResponse($payload, $code);
}

// ── CSRF ────────────────────────────────────────────────────────────────

function csrfToken(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(?string $token = null): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $sessionToken = $_SESSION[CSRF_TOKEN_KEY] ?? null;
    $submitted    = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));
    if (!$sessionToken || !$submitted) return false;
    return hash_equals($sessionToken, $submitted);
}

function requireCsrf(?string $explicitToken = null): void {
    // If caller passes an explicit token (e.g. from $_POST or header), use it.
    // Otherwise verifyCsrf() reads from $_POST / HTTP_X_CSRF_TOKEN automatically.
    if ($explicitToken !== null) {
        if (!verifyCsrf($explicitToken)) {
            if (isAjax()) {
                jsonError('Invalid or missing CSRF token.', 403);
            }
            http_response_code(403);
            die('403 Forbidden — Invalid CSRF token.');
        }
        return;
    }
    if (!verifyCsrf()) {
        if (isAjax()) {
            jsonError('Invalid or missing CSRF token.', 403);
        }
        http_response_code(403);
        die('403 Forbidden — Invalid CSRF token.');
    }
}

// ── Request Helpers ──────────────────────────────────────────────────────

function isAjax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

function post(string $key, mixed $default = ''): string {
    return isset($_POST[$key]) ? sanitize((string) $_POST[$key]) : $default;
}

function get(string $key, mixed $default = ''): string {
    return isset($_GET[$key]) ? sanitize((string) $_GET[$key]) : $default;
}

function clientIp(): string {
    $candidates = [
        'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR',
    ];
    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ── Slugs & Strings ──────────────────────────────────────────────────────

function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);  // keep letters/numbers/spaces/hyphens
    $text = preg_replace('/[\s_]+/', '-', $text);              // spaces → hyphens
    $text = preg_replace('/-+/', '-', $text);                  // multiple hyphens → one
    return trim($text, '-');
}

function truncate(string $text, int $limit = 100, string $append = '…'): string {
    $clean = strip_tags($text);
    if (mb_strlen($clean) <= $limit) return $clean;
    return rtrim(mb_substr($clean, 0, $limit)) . $append;
}

function timeAgo(\DateTime|string $datetime): string {
    $time = is_string($datetime) ? strtotime($datetime) : $datetime->getTimestamp();
    $diff = time() - $time;
    return match (true) {
        $diff < 60        => 'just now',
        $diff < 3600      => (int)($diff / 60)   . ' min ago',
        $diff < 86400     => (int)($diff / 3600)  . ' hr ago',
        $diff < 2592000   => (int)($diff / 86400) . ' days ago',
        $diff < 31536000  => (int)($diff / 2592000) . ' months ago',
        default           => (int)($diff / 31536000) . ' years ago',
    };
}

// ── Pagination ───────────────────────────────────────────────────────────

function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = (int) ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
        'prev_page'    => $currentPage - 1,
        'next_page'    => $currentPage + 1,
    ];
}

// ── Password ─────────────────────────────────────────────────────────────

function hashPassword(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $plain, string $hash): bool {
    return password_verify($plain, $hash);
}

function passwordStrength(string $password): string {
    $len = strlen($password);
    if ($len < 8) return 'weak';
    $score = 0;
    if ($len >= 12) $score++;
    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[\W_]/', $password)) $score++;
    return match (true) {
        $score <= 2 => 'weak',
        $score <= 3 => 'fair',
        $score <= 4 => 'good',
        default     => 'strong',
    };
}

// ── Date ─────────────────────────────────────────────────────────────────

function formatDate(?string $dateStr, string $format = 'd M Y'): string {
    if (empty($dateStr)) return '';
    return date($format, strtotime($dateStr));
}

// ── File Size Formatting ─────────────────────────────────────────────────

function formatFileSize(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)   return round($bytes / 1048576, 1)   . ' MB';
    if ($bytes >= 1024)      return round($bytes / 1024, 0)      . ' KB';
    return $bytes . ' B';
}

// ── Device Detection (basic, for analytics) ──────────────────────────────

function detectDevice(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (str_contains($ua, 'bot') || str_contains($ua, 'crawl') || str_contains($ua, 'spider')) {
        return 'bot';
    }
    if (preg_match('/Mobile|Android|iPhone|iPod/i', $ua)) return 'mobile';
    if (preg_match('/iPad|Tablet/i', $ua)) return 'tablet';
    if (empty($ua)) return 'unknown';
    return 'desktop';
}

// ── Browser Detection (for analytics) ───────────────────────────────────

/**
 * Parse a browser name from a user-agent string.
 * Returns one of: Chrome, Firefox, Safari, Edge, Opera, IE, Bot/Script, Other.
 */
function parseBrowser(string $ua): string {
    if (!$ua) return 'Unknown';
    $ua = substr($ua, 0, 300); // Limit length for safety
    if (str_contains($ua, 'Edg/') || str_contains($ua, 'Edge/'))       return 'Edge';
    if (str_contains($ua, 'OPR/')  || str_contains($ua, 'Opera/'))      return 'Opera';
    if (str_contains($ua, 'Chrome/')  && !str_contains($ua, 'Chromium')) return 'Chrome';
    if (str_contains($ua, 'Firefox/'))                                    return 'Firefox';
    if (str_contains($ua, 'Safari/')  && !str_contains($ua, 'Chrome'))   return 'Safari';
    if (str_contains($ua, 'MSIE')     || str_contains($ua, 'Trident/'))  return 'IE';
    $lower = strtolower($ua);
    if (str_contains($lower, 'curl') || str_contains($lower, 'python') ||
        str_contains($lower, 'bot')  || str_contains($lower, 'spider')) return 'Bot/Script';
    return 'Other';
}

// ── Root path helper ─────────────────────────────────────────────────────

/**
 * Return the absolute filesystem root of the project.
 * Avoids scattered realpath(__DIR__.'/../..') calls.
 */
function rootPath(string $append = ''): string {
    static $root = null;
    if ($root === null) {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
    }
    return $append ? $root . DIRECTORY_SEPARATOR . ltrim($append, '/\\') : $root;
}

