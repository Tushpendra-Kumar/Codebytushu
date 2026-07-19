# CodeByTushu - API Documentation Overview

This document provides a high-level map of the API endpoints used to power dynamic features without page reloads.

## 1. Authentication Endpoints (`/api/auth/`)
All files here manage session states and login verifications.

- **`POST /api/auth/login.php`**
  - **Purpose**: Authenticates a user via email and password.
  - **Inputs**: `email`, `password`, `csrf_token`.
  - **Outputs**: `{ success: true, redirect: '/user/dashboard.php' }` or `{ success: false, error: '...' }`.

- **`POST /api/auth/register.php`**
  - **Purpose**: Creates a new user account.
  - **Inputs**: `name`, `email`, `password`, `csrf_token`.
  - **Outputs**: JSON success or error message.

- **`GET /api/auth/logout.php`**
  - **Purpose**: Destroys the PHP session and clears "Remember Me" cookies.
  - **Outputs**: Redirects to `/`.

- **`GET /api/auth/google.php`**
  - **Purpose**: Generates the Google OAuth consent URL and redirects the user.

- **`GET /api/auth/callback.php`**
  - **Purpose**: Handles the response from Google, exchanges the code for a token, and logs the user in.

## 2. API Design Principles
1. **JSON Only**: Unless redirecting, all API endpoints must set `header('Content-Type: application/json')` and return valid JSON.
2. **Error Handling**: APIs should catch PDOExceptions and return clean error messages rather than raw SQL errors.
3. **Security**: All `POST`, `PUT`, `DELETE` operations require a CSRF token. All protected actions require the session user ID (`$_SESSION['user_id']`).

> [!CAUTION]
> **Data Leak Prevention Rule:**
> When returning a "user" object in a JSON response, you MUST unset or exclude the `password_hash` and `google_uid` fields.
