# CodeByTushu - File Connections & Architecture

This document maps how different components of the CodeByTushu platform connect and communicate with each other.

## 1. High-Level Architecture
The platform is built on a custom PHP + MySQL stack using a classic Model-View-Controller (MVC) inspired architecture, though it relies heavily on direct API endpoints rather than a strict router.
- **Frontend**: HTML/PHP pages with Vanilla JavaScript and CSS.
- **Backend API**: PHP scripts located in the /api/ directory that handle AJAX requests.
- **Database Layer**: PDO-based MySQL connection via config/database.php.

## 2. Request Lifecycle (Frontend to Backend)
1. **User Interaction**: A user clicks a button (e.g., \"Login\") on the frontend.
2. **AJAX/Fetch Call**: JavaScript sends an asynchronous POST or GET request to a specific endpoint in the /api/ folder (e.g., /api/auth/login.php).
3. **Session & Security Verification**: 
   - The API script boots the session via Auth::boot().
   - It verifies the CSRF token using erifyCsrfToken().
4. **Database Execution**: The script interacts with the database using the global db() PDO singleton.
5. **JSON Response**: The script returns a JSON response (json_encode()) containing success/error status and data.
6. **Frontend Update**: JavaScript parses the JSON and updates the DOM dynamically without reloading the page.

## 3. Core Component Chain
Every secure PHP page and API endpoint must include the core configuration and authentication chain:

1. \equire_once __DIR__ . '/config/app.php'\
   - Loads \.env\ variables.
   - Sets up error reporting, timezones, and constants.
2. \equire_once __DIR__ . '/classes/Auth.php'\
   - Contains the \Auth\ class for session management, login, logout, and OAuth.
3. \Auth::boot()\
   - Starts the PHP session safely.
   - Automatically handles "Remember Me" cookies.
   - Refreshes the user's database state periodically.
4. \Auth::requireLogin()\ (Optional)
   - Used on protected pages (like \user/dashboard.php\). Redirects unauthorized users to the login page.

## 4. Shared Includes (UI)
To maintain consistency across pages, shared UI components are loaded via PHP includes:
- **Navbar**: \<?php require_once __DIR__ . '/includes/navbar.php'; ?>\ (Handles login state and user avatar dropdown).
- **Footer**: Custom HTML sections included at the bottom of pages.

## 5. Security & Connections Audited
- **Database Connection**: config/database.php uses prepared statements (PDO) strictly, preventing SQL injection.
- **Session Handling**: Uses strict PHP sessions. No sensitive data is passed via insecure cookies.
- **API Protection**: API endpoints return 401/403 HTTP codes if unauthorized, rather than leaking data.
