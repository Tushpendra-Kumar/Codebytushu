# CodeByTushu - Security Architecture & Audit

This document details the security mechanisms implemented in the CodeByTushu platform to protect user data and prevent exploitation.

## 1. Environment Variables (`.env`)
- **Strict Separation**: All sensitive credentials (DB passwords, Google OAuth client secrets, SMTP credentials, and application encryption keys) are strictly stored in `.env`.
- **Git Ignore**: The `.env` file is explicitly ignored in `.gitignore`. It must NEVER be committed to version control. An `.env.example` is provided as a reference template.
- **Loading Mechanism**: Handled securely by `vlucas/phpdotenv` (Composer package) in `config/app.php`. A manual fallback parser is included for edge cases.

## 2. Authentication & Sessions
- **Session Hijacking Prevention**: Sessions are strictly managed by PHP. Cookie parameters set: `httponly = true`, `samesite = Lax`, `secure = true` (on HTTPS). Session name is customized (`CBT_SESSION`).
- **Password Hashing**: User passwords are encrypted using PHP's native `password_hash()` (bcrypt algorithm). Plaintext passwords are NEVER stored or logged.
- **Remember Me**: Persistent "Remember Me" is implemented via a secure random token stored in the `users` table (hashed). A cookie contains only the raw token, never the user ID or session data directly.
- **Email Verification**: New email/password registrations require email verification before login is allowed. Tokens expire after 24 hours.
- **Password Reset**: Reset tokens are single-use with expiry timestamps. Consumed immediately on use.
- **Google OAuth**: OAuth state parameter should be validated to prevent CSRF in the OAuth flow. The `google_uid` is used as the primary identifier for OAuth users.

## 3. Database Security
- **SQL Injection (SQLi) Prevention**: 100% of database queries use PDO Prepared Statements (`$pdo->prepare()` + `->execute([])`). Raw variables are NEVER concatenated directly into SQL strings.
- **PDO Singleton**: The `db()` function in `config/database.php` returns a shared PDO instance. `ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` is set so errors throw exceptions.

## 4. Cross-Site Request Forgery (CSRF)
- **Token Generation**: A unique CSRF token is generated per session via `generateCsrfToken()` in `includes/functions.php`.
- **Embedding**: Forms include `<?= csrfField() ?>` which outputs a hidden `<input>` with the CSRF token.
- **Enforcement**: Any state-changing request (POST, PUT, DELETE) calls `requireCsrf()`. If the token is missing or invalid, the request is rejected with HTTP 403 and execution halts.

## 5. Data Leak Prevention (DLP)
- **API Response Filtering**: When fetching user objects, the following columns are EXCLUDED from JSON payloads: `password_hash`, `google_uid`, `remember_token`, `email_verify_token`, `password_reset_token`.
- **Error Handling**: In production mode (`APP_DEBUG=false`), raw PHP errors and SQL exceptions are hidden from the user and logged to `private/logs/php_errors.log`. The `private/` directory is protected by `.htaccess` (Deny from all).

## 6. Uploads Security
- **MIME Type Validation**: File uploads (images, PDFs, ZIPs, Videos) are strictly validated against an allowlist of MIME types defined in `config/app.php` (`ALLOWED_IMAGE_MIME`, `ALLOWED_PDF_MIME`, etc.). File extension alone is NOT trusted.
- **File Size Limits**: Max file sizes are enforced: Images 5MB, PDFs 20MB, Videos 200MB, ZIPs 50MB.
- **Filename Sanitization**: Uploaded files are renamed using a hash + timestamp to prevent path traversal and overwrite attacks.
- **Upload Directory**: The `/uploads/` directory contents are ignored in Git. A `.htaccess` in `/uploads/` prevents direct PHP execution.

## 7. Access Control
- **Public Routes**: Only `index.html`, `about-platform.html`, `404.php`, `blogs.php` (root), and legal pages are publicly accessible.
- **Auth-Required Routes**: `/blogs/`, `/courses/`, `/Leetcode/`, `/video-editing/` require `Auth::requireLogin()`.
- **Admin Routes**: All `/admin/*.php` files require `role = 'admin'` or `'super_admin'` via `Auth::requireAdmin()` inside `/admin/includes/auth_check.php`.
- **Private Files**: `/private/courses/*.pdf` are never served directly. They are streamed exclusively through `/api/courses/download.php` after ownership verification.
- **API `.htaccess`**: `/api/.htaccess` restricts direct directory browsing.

## 8. Rate Limiting
- **Contact Form**: 3 submissions per IP per 10 minutes (enforced via `rate_limit_log` table).
- **Login Attempts**: 5 failed attempts per email triggers a 15-minute lockout.
- Both implemented server-side in PHP — no client-side bypass possible.

## 9. HTTP Security Headers
Recommended headers to implement on the web server (Hostinger / Apache via `.htaccess`):
```
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
```
These should be set in the root `.htaccess` or server config.

> [!IMPORTANT]
> **Audit Conclusion**: The current architecture is highly secure. The primary requirement for future developers is to maintain this standard:
> - Always use PDO prepared statements
> - Always require CSRF tokens on state-changing endpoints
> - Never expose `.env` variables or sensitive user columns in API responses
> - Always verify file MIME types on upload
> - Always use `Auth::requireLogin()` or `Auth::requireAdmin()` on protected pages
