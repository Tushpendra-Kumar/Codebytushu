<?php
/**
 * CodeByTushu — Auth Class
 * Handles login, registration, session management,
 * remember-me tokens, and role checks.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Auth
{
    // ── Session Keys ─────────────────────────────────────────────────────
    private const SESS_USER        = 'cbt_user';
    private const SESS_ADMIN       = 'cbt_admin';
    private const SESS_LOGIN_AT    = 'cbt_login_at';
    private const SESS_DB_CHECK    = 'cbt_db_check';
    private const COOKIE_REMEMBER  = 'cbt_remember';

    // ─────────────────────────────────────────────────────────────────────
    // BOOT — Call once at top of every protected page
    // ─────────────────────────────────────────────────────────────────────

    public static function boot(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => 0,         // Browser session (remember-me sets persistent cookie separately)
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        // Try remember-me auto-login if no session user
        if (!self::check() && isset($_COOKIE[self::COOKIE_REMEMBER])) {
            self::loginViaRememberToken($_COOKIE[self::COOKIE_REMEMBER]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // LOGIN
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Authenticate or register a user via Google OAuth data.
     * Returns ['success' => bool, 'error' => string|null, 'user' => array|null]
     */
    public static function loginWithGoogle(array $googleUser, bool $remember = true): array
    {
        $pdo = db();
        $email = strtolower(trim($googleUser['email']));
        $googleUid = $googleUser['id'];
        $fullName = sanitize($googleUser['name'] ?? 'Google User');
        $profileImage = $googleUser['picture'] ?? null;
        
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'banned') {
                return ['success' => false, 'error' => 'Your account has been suspended. Please contact support.'];
            }
            if ($user['status'] === 'pending') {
                return ['success' => false, 'error' => 'Your account is pending approval.'];
            }
            // Only update profile image if they don't have a custom local uploaded one
            $newProfileImage = $user['profile_image'];
            if (empty($user['profile_image']) || strpos($user['profile_image'], 'http') === 0) {
                $newProfileImage = $profileImage;
            }

            $pdo->prepare(
                'UPDATE users SET google_uid = ?, profile_image = ?, full_name = ?, last_login = NOW(), login_count = login_count + 1 WHERE id = ?'
            )->execute([$googleUid, $newProfileImage, $fullName, $user['id']]);
            $user['profile_image'] = $newProfileImage;
            $user['full_name'] = $fullName;
        } else {
            // Create new user (SSO Registration)
            $username = explode('@', $email)[0] . rand(100, 999);
            // Ensure unique username
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $checkStmt->execute([$username]);
            if ($checkStmt->fetch()) {
                $username .= rand(1000, 9999);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, username, email, google_uid, profile_image, role, status, email_verified, last_login, login_count)
                 VALUES (?, ?, ?, ?, ?, "user", "active", 1, NOW(), 1)'
            );
            $stmt->execute([
                $fullName,
                $username,
                $email,
                $googleUid,
                $profileImage
            ]);
            $userId = (int) $pdo->lastInsertId();
            
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }

        self::createSession($user);
        
        if ($remember) {
            self::setRememberToken($user['id']);
        }
        
        return ['success' => true, 'user' => $user];
    }

    // ─────────────────────────────────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────────────────────────────────

    public static function logout(): void
    {
        // Clear remember-me cookie + DB token
        if (isset($_COOKIE[self::COOKIE_REMEMBER])) {
            $token = $_COOKIE[self::COOKIE_REMEMBER];
            // Hash the token to find it in DB
            $pdo = db();
            $pdo->prepare(
                'UPDATE users SET remember_token = NULL, remember_expires = NULL
                  WHERE remember_token = ?'
            )->execute([hash('sha256', $token)]);

            setcookie(self::COOKIE_REMEMBER, '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ─────────────────────────────────────────────────────────────────────
    // SESSION CHECKS
    // ─────────────────────────────────────────────────────────────────────

    /** Is a user logged in right now? */
    public static function check(): bool
    {
        if (empty($_SESSION[self::SESS_USER])) return false;

        // Check session has not expired
        $loginAt = $_SESSION[self::SESS_LOGIN_AT] ?? 0;
        if (time() - $loginAt > SESSION_LIFETIME) {
            self::logout();
            return false;
        }

        // Periodic DB re-validation (every 30 min)
        $lastCheck = $_SESSION[self::SESS_DB_CHECK] ?? 0;
        if (time() - $lastCheck > SESSION_DB_CHECK) {
            $stmt = db()->prepare('SELECT id, status FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$_SESSION[self::SESS_USER]['id']]);
            $fresh = $stmt->fetch();
            if (!$fresh || $fresh['status'] !== 'active') {
                self::logout();
                return false;
            }
            $_SESSION[self::SESS_DB_CHECK] = time();
        }

        return true;
    }

    /**
     * Get the currently logged-in user array.
     * @param bool $fresh Reload from DB (use after profile update)
     */
    public static function user(bool $fresh = false): ?array
    {
        if (!self::check()) return null;
        if ($fresh) {
            $stmt = db()->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
            $stmt->execute([$_SESSION[self::SESS_USER]['id']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION[self::SESS_USER] = array_merge($_SESSION[self::SESS_USER], (array)$user);
            }
        }
        return $_SESSION[self::SESS_USER];
    }

    /** Get a specific field from the current user. */
    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    /** Does current user have this role (or higher)? */
    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();
        if (!$user) return false;
        return in_array($user['role'], $roles, true);
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('super_admin', 'admin', 'editor');
    }

    public static function isSuperAdmin(): bool
    {
        return self::hasRole('super_admin');
    }

    // ─────────────────────────────────────────────────────────────────────
    // GUARDS (middleware)
    // ─────────────────────────────────────────────────────────────────────

    /** Redirect to login if not authenticated. */
    public static function requireLogin(string $redirect = '/auth/login.php'): void
    {
        if (!self::check()) {
            $fullUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
            $_SESSION['redirect_after_login'] = $fullUri;
            redirect($redirect . '?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
        }
    }

    /** Abort with 403 if not admin. */
    public static function requireAdmin(): void
    {
        self::requireLogin('/auth/login.php');
        if (!self::isAdmin()) {
            http_response_code(403);
            die('403 Forbidden — Insufficient permissions.');
        }
    }

    /** Redirect logged-in users away from auth pages. */
    public static function redirectIfLoggedIn(string $to = '/'): void
    {
        if (self::check()) redirect($to);
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSWORD RESET
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Create a password-reset token for an email.
     * Returns the raw token (to be emailed) or false if email not found.
     */
    public static function createPasswordResetToken(string $email): string|false
    {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower($email)]);
        if (!$stmt->fetch()) return false;

        $rawToken  = bin2hex(random_bytes(32)); // 64-char hex
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        // Invalidate any existing tokens for this email
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE email = ?')->execute([$email]);

        $pdo->prepare(
            'INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)'
        )->execute([strtolower($email), $tokenHash, $expiresAt]);

        return $rawToken;
    }

    /**
     * Validate a password-reset token.
     * Returns the associated email or null if invalid/expired.
     */
    public static function validateResetToken(string $rawToken): ?string
    {
        $tokenHash = hash('sha256', $rawToken);
        $stmt = db()->prepare(
            'SELECT email FROM password_resets
              WHERE token_hash = ? AND used = 0 AND expires_at > NOW()
              LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();
        return $row ? $row['email'] : null;
    }

    /**
     * Complete the password reset — update password, mark token used.
     */
    public static function resetPassword(string $rawToken, string $newPassword): bool
    {
        $email = self::validateResetToken($rawToken);
        if (!$email) return false;

        $pdo  = db();
        $hash = hashPassword($newPassword);

        $pdo->prepare(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?'
        )->execute([$hash, $email]);

        $pdo->prepare(
            'UPDATE password_resets SET used = 1 WHERE token_hash = ?'
        )->execute([hash('sha256', $rawToken)]);

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────
    // EMAIL VERIFICATION
    // ─────────────────────────────────────────────────────────────────────

    public static function createEmailVerificationToken(int $userId): string
    {
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 hours

        db()->prepare(
            'INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
        )->execute([$userId, $tokenHash, $expiresAt]);

        return $rawToken;
    }

    public static function verifyEmail(string $rawToken): array
    {
        if (!$rawToken) return ['success' => false, 'message' => 'No verification token provided.'];
        $tokenHash = hash('sha256', $rawToken);
        $stmt = db()->prepare(
            'SELECT user_id FROM email_verifications
              WHERE token_hash = ? AND used = 0 AND expires_at > NOW()
              LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();
        if (!$row) return ['success' => false, 'message' => 'This verification link is invalid or has expired. Please request a new one.'];

        $pdo = db();
        $pdo->prepare('UPDATE users SET email_verified = 1 WHERE id = ?')->execute([$row['user_id']]);
        $pdo->prepare('UPDATE email_verifications SET used = 1 WHERE token_hash = ?')->execute([$tokenHash]);
        return ['success' => true, 'message' => 'Email verified successfully.'];
    }

    // ─────────────────────────────────────────────────────────────────────
    // GOOGLE OAUTH
    // ─────────────────────────────────────────────────────────────────────

    public static function loginOrCreateGoogleUser(array $googleProfile): array
    {
        $pdo    = db();
        $uid    = $googleProfile['id'];
        $email  = strtolower($googleProfile['email']);
        $name   = $googleProfile['name'] ?? 'Google User';
        $photo  = $googleProfile['picture'] ?? null;

        // Check existing by google_uid OR email
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, role, status FROM users
              WHERE google_uid = ? OR email = ?
              LIMIT 1'
        );
        $stmt->execute([$uid, $email]);
        $user = $stmt->fetch();

        // Determine target role based on email
        $targetRole = ($email === 'tushpendrakumar@gmail.com') ? 'admin' : 'user';

        if ($user) {
            if ($user['status'] === 'banned') {
                return ['success' => false, 'error' => 'Account suspended.'];
            }
            // Update Google UID, profile image, last login, and enforce the target role
            $pdo->prepare(
                'UPDATE users SET google_uid = ?, profile_image = COALESCE(profile_image, ?),
                        role = ?, last_login = NOW(), login_count = login_count + 1
                  WHERE id = ?'
            )->execute([$uid, $photo, $targetRole, $user['id']]);
        } else {
            // New user — register
            $username = self::generateUsername($name, $email);
            $pdo->prepare(
                'INSERT INTO users (full_name, username, email, google_uid, profile_image,
                                   role, status, email_verified)
                 VALUES (?, ?, ?, ?, ?, ?, "active", 1)'
            )->execute([$name, $username, $email, $uid, $photo, $targetRole]);
            $user = ['id' => (int)$pdo->lastInsertId(), 'role' => $targetRole, 'status' => 'active',
                     'full_name' => $name, 'email' => $email];
        }

        // Reload full user row
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $fullUser = $stmt->fetch();

        self::createSession($fullUser);
        return ['success' => true, 'user' => $fullUser];
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private static function createSession(array $user): void
    {
        // Regenerate ID on privilege change — prevents session fixation
        session_regenerate_id(true);

        $_SESSION[self::SESS_USER] = [
            'id'             => (int) $user['id'],
            'full_name'      => $user['full_name'],
            'email'          => $user['email'],
            'role'           => $user['role'],
            'profile_image'  => $user['profile_image'] ?? null,
            'email_verified' => (int) ($user['email_verified'] ?? 0),
        ];
        $_SESSION[self::SESS_LOGIN_AT]  = time();
        $_SESSION[self::SESS_DB_CHECK]  = time();
    }

    private static function setRememberToken(int $userId): void
    {
        $rawToken    = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expires     = date('Y-m-d H:i:s', time() + 2592000); // 30 days

        db()->prepare(
            'UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?'
        )->execute([$hashedToken, $expires, $userId]);

        setcookie(
            self::COOKIE_REMEMBER,
            $rawToken,
            time() + 2592000,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }

    private static function loginViaRememberToken(string $rawToken): void
    {
        $hashedToken = hash('sha256', $rawToken);
        $stmt = db()->prepare(
            'SELECT * FROM users
              WHERE remember_token = ? AND remember_expires > NOW()
                AND status = "active"
              LIMIT 1'
        );
        $stmt->execute([$hashedToken]);
        $user = $stmt->fetch();
        if ($user) {
            self::createSession($user);
            db()->prepare('UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?')
               ->execute([$user['id']]);
            // Refresh cookie
            self::setRememberToken($user['id']);
        }
    }

    // ── DB-backed Rate Limiting ───────────────────────────────────────────

    private static function isRateLimited(string $ip): bool
    {
        try {
            $stmt = db()->prepare(
                'SELECT COUNT(*) FROM login_attempts
                  WHERE ip_address = ? AND was_success = 0
                    AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
            );
            $stmt->execute([$ip, RATE_LIMIT_LOGIN_WIN]);
            return (int)$stmt->fetchColumn() >= RATE_LIMIT_LOGIN_MAX;
        } catch (\Throwable) {
            return false; // Fail open if table doesn't exist yet
        }
    }

    private static function recordAttempt(string $email, string $ip, bool $success, ?string $reason): void
    {
        try {
            db()->prepare(
                'INSERT INTO login_attempts (email, ip_address, user_agent, was_success, failure_reason)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $email,
                $ip,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                $success ? 1 : 0,
                $reason,
            ]);
        } catch (\Throwable) { /* Non-fatal */ }
    }

    private static function registerSession(int $userId): void
    {
        try {
            $sid = session_id();
            if (!$sid) return;
            db()->prepare(
                'INSERT INTO user_sessions (id, user_id, ip_address, user_agent)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE last_active = NOW(), is_revoked = 0'
            )->execute([
                $sid,
                $userId,
                clientIp(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (\Throwable) { /* Non-fatal */ }
    }

    private static function generateUsername(string $name, string $email): string
    {
        // Base: first part of email, lowercase alphanumeric only
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', explode('@', $email)[0]));
        $base = substr($base, 0, 20) ?: 'user';

        // ARCH-05 Fix: Single query fetches all taken variants — avoids N+1 loop.
        // Fetch all usernames that exactly match the base OR start with base+digits.
        $stmt = db()->prepare(
            'SELECT username FROM users WHERE username = ? OR username LIKE ?'
        );
        $stmt->execute([$base, $base . '%']);
        $taken = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $taken = array_flip($taken); // flip for O(1) isset() lookup

        $candidate = $base;
        $i = 1;
        while (isset($taken[$candidate])) {
            $candidate = $base . $i++;
        }
        return $candidate;
    }
}
