# CodeByTushu - Security Architecture & Audit

This document details the security mechanisms implemented in the CodeByTushu platform to protect user data and prevent exploitation.

## 1. Environment Variables (`.env`)
- **Strict Separation**: All sensitive credentials (DB passwords, Google OAuth client secrets, API keys, and application encryption keys) are strictly stored in `.env`.
- **Git Ignore**: The `.env` file is explicitly ignored in `.gitignore`. It must NEVER be committed to version control.
- **Loading Mechanism**: Handled securely by `vlucas/phpdotenv` in `config/app.php`.

## 2. Authentication & Sessions
- **Session Hijacking Prevention**: Sessions are strictly managed by PHP. Configuration options (e.g., `session.cookie_httponly = 1`) should be set on the production server to prevent XSS attacks from reading session cookies.
- **Password Hashing**: User passwords are encrypted using PHP's native `password_hash()` (bcrypt algorithm). Plaintext passwords are never stored or logged.

## 3. Database Security
- **SQL Injection (SQLi) Prevention**: 100% of database queries are executed using PDO Prepared Statements. Raw variables are NEVER concatenated directly into SQL strings.

## 4. Cross-Site Request Forgery (CSRF)
- **Token Generation**: A unique CSRF token is generated for the session via `generateCsrfToken()`.
- **Enforcement**: Any state-changing request (POST, PUT, DELETE) must include this token. It is verified by the backend API before any database action is taken.

## 5. Data Leak Prevention (DLP)
- **API Response Filtering**: When fetching user objects, the `password_hash`, `google_uid`, and `remember_token` columns are excluded from JSON payloads.
- **Error Handling**: In production mode (`APP_DEBUG=false`), raw PHP errors and SQL exceptions are hidden from the user and logged to `private/logs/php_errors.log`.

## 6. Uploads Security
- **MIME Type Validation**: File uploads (images, PDFs) are strictly checked against a whitelist of allowed MIME types, not just file extensions.
- **Path Traversal**: Uploaded files are renamed using secure hashes to prevent path traversal attacks.
- **Git Ignore**: The `/uploads/` directory contents are ignored in Git to prevent accidental tracking of user-generated content.

> [!IMPORTANT]
> **Audit Conclusion**: The current architecture is highly secure. No critical data leaks were found. The primary requirement for future developers is to maintain this standard: always use PDO, always require CSRF tokens, and never expose `.env` variables.
