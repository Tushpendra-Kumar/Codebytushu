# CodeByTushu - Project Flow & User Journey

This document outlines the primary data flows and user journeys throughout the application.

## 1. Authentication Flow
The most critical flow in the application is how a user proves their identity.

### Standard Login (Email & Password)
1. **Client**: User submits credentials at `/auth/login.php`.
2. **API**: AJAX POST to `/api/auth/login.php`.
3. **Database**: Queries `users` table by email. Verifies `password_hash` using `password_verify()`.
4. **Session**: If valid, `Auth::login($user)` is called. PHP `$_SESSION['user_id']` is populated.
5. **Client**: Redirects to `/user/dashboard.php`.

### Google OAuth Login
1. **Client**: User clicks "Login with Google".
2. **API**: Redirects to Google's OAuth 2.0 consent screen via `/api/auth/google.php`.
3. **Callback**: Google redirects to `/api/auth/callback.php` with an authorization code.
4. **Verification**: PHP exchanges the code for an Access Token and fetches the Google User Profile.
5. **Database**: If `google_uid` exists, log them in. If not, create a new row in the `users` table and log them in.

## 2. Protected Page Flow
When a user visits a protected route (e.g., `/user/dashboard.php`):
1. **Bootstrap**: `config/app.php` and `classes/Auth.php` are required.
2. **Check**: `Auth::boot()` checks if `$_SESSION` exists.
3. **Enforcement**: `Auth::requireLogin()` is called. If the user is NOT logged in, they are immediately redirected (HTTP 302) to `/auth/login.php`.
4. **Render**: If authorized, the server queries the database for user-specific data (e.g., purchased courses) and renders the HTML.

## 3. Data Modification Flow (CSRF Protection)
Whenever a user modifies data (e.g., updating their profile, deleting an account):
1. **Form**: A hidden CSRF token is embedded in the form using `<?= csrfField() ?>`.
2. **Submission**: The token is sent alongside the POST data.
3. **API Validation**: The receiving API endpoint calls `verifyCsrfToken()`. If the token is missing or invalid, the request is rejected with a 403 Forbidden error.
4. **Execution**: The database query executes securely via PDO prepared statements.
