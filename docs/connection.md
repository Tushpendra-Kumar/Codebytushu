# CodeByTushu - File Connections & Architecture

This document maps how different components of the CodeByTushu platform connect and communicate with each other.

## 1. High-Level Architecture
The platform is built on a custom PHP + MySQL stack using a classic Model-View-Controller (MVC) inspired architecture, though it relies heavily on direct API endpoints rather than a strict router.
- **Frontend**: HTML/PHP pages with Vanilla JavaScript and CSS.
- **Backend API**: PHP scripts located in the `/api/` directory that handle AJAX requests.
- **Database Layer**: PDO-based MySQL connection via `config/database.php` (singleton `db()` function).
- **Third-Party**: Qikink API for print-on-demand fulfillment (webhook-based).

## 2. Request Lifecycle (Frontend to Backend)
1. **User Interaction**: A user clicks a button (e.g., "Add to Cart") on the frontend.
2. **AJAX/Fetch Call**: JavaScript sends an asynchronous POST or GET request to a specific endpoint in the `/api/` folder (e.g., `/api/cart/add.php`).
3. **Session & Security Verification**:
   - The API script boots the session via `Auth::boot()`.
   - For state-changing requests: verifies the CSRF token using `requireCsrf()`.
4. **Database Execution**: The script interacts with the database using the global `db()` PDO singleton.
5. **JSON Response**: The script returns a JSON response (`json_encode()`) containing success/error status and data.
6. **Frontend Update**: JavaScript parses the JSON and updates the DOM dynamically without reloading the page.

## 3. Core Boot Chain
Every secure PHP page and API endpoint must include the core configuration and authentication chain:

```
require_once __DIR__ . '/config/app.php'
```
- Loads `.env` variables via Composer (vlucas/phpdotenv).
- Sets up error reporting, timezones, and constants.
- Defines all app-wide constants: `SITE_URL`, `DB_*`, `GOOGLE_*`, `SMTP_*`, `UPLOAD_*`, etc.

```
require_once __DIR__ . '/classes/Auth.php'
```
- Contains the `Auth` class for session management, login, logout, and OAuth.
- Also loads `config/database.php` and `includes/functions.php`.

```
Auth::boot()
```
- Starts the PHP session safely (only if not already started).
- Automatically handles "Remember Me" cookies.
- Refreshes the user's database state periodically (every 30 minutes).

```
Auth::requireLogin()  ← Optional, for protected pages
```
- Used on protected pages (like `user/dashboard.php`, all `/admin/` pages). Redirects unauthorized users to `/auth/login.php`.

## 4. Admin Panel Boot Chain
Admin pages use a dedicated include instead of calling `Auth::requireLogin()` directly:

```
require_once __DIR__ . '/includes/auth_check.php'
```
- Internally calls `Auth::boot()` + `Auth::requireAdmin()`.
- Also loads the page template wrapper (header, sidebar, footer).

## 5. API Endpoint Directory Map

| API Endpoint | Purpose |
|---|---|
| `POST /api/auth/` | Auth is handled at `/auth/*.php` pages (server-side, not AJAX-only) |
| `GET /api/auth/google.php` | Generates Google OAuth consent URL, redirects |
| `GET /api/auth/callback.php` | Handles Google OAuth response, logs user in |
| `GET /api/auth/logout.php` | Destroys session, clears cookies, redirects |
| `GET /api/auth/status.php` | Returns JSON auth status (used for navbar state) |
| `POST /api/auth/save_phone.php` | Saves phone number post Google login |
| `POST /api/auth/resend-verification.php` | Resends email verification |
| `POST /api/cart/add.php` | Add course to cart |
| `POST /api/cart/remove.php` | Remove course from cart |
| `GET /api/cart/view.php` | Get course cart contents |
| `POST /api/checkout/submit.php` | Submit course checkout (multiple items) |
| `POST /api/checkout/submit_single.php` | Submit single-course checkout |
| `GET /api/courses/download.php` | Securely stream purchased PDF to user |
| `POST /api/store/checkout.php` | Submit store checkout (with shipping details) |
| `GET /api/store/invoice.php` | Generate HTML invoice for store order |
| `POST /api/webhooks/qikink.php` | Receive Qikink fulfillment status updates |
| `POST /api/contact.php` | Contact form submission (rate-limited) |
| `POST /api/subscribe.php` | Newsletter subscription |
| `POST /api/subscribe_ve.php` | Video editing newsletter subscription |
| `POST /api/upload.php` | File upload handler (admin only) |
| `GET/POST /api/admin/blogs.php` | Admin blog CRUD |
| `GET/POST /api/admin/courses.php` | Admin courses CRUD |
| `GET/POST /api/admin/leetcode.php` | Admin LeetCode CRUD |
| `GET/POST /api/admin/users.php` | Admin user management |
| `GET /api/admin/dashboard.php` | Admin dashboard KPIs (AJAX refresh) |
| `GET/POST /api/admin/settings.php` | Site settings management |
| `GET /api/admin/payments.php` | Course payment/order data |
| `GET /api/admin/messages.php` | Contact messages inbox |
| `GET/POST/DELETE /api/admin/categories.php` | Category management |
| `POST /api/admin/import_pdf.php` | PDF course import tool |
| `GET/POST/PUT/DELETE /api/admin/store-products.php` | Store product CRUD |
| `GET/POST /api/admin/store-orders.php` | Store orders + fulfillment management |

## 6. Shared Includes (UI)
To maintain consistency across pages, shared UI components are loaded via PHP includes:
- **Global Navbar**: `<?php require_once __DIR__ . '/includes/navbar.php'; ?>` — Handles login state (avatar dropdown vs login button), server-side rendered.
- **Functions**: `includes/functions.php` — `sanitize()`, `post()`, `get()`, `isPost()`, `requireCsrf()`, `csrfField()`, `json_success()`, `json_error()`, etc.
- **Analytics Tracker**: `includes/analytics.php` — Logs page views, device type, referrer to `analytics_events` table.
- **Admin Panel includes**: `admin/includes/sidebar.php`, `admin/includes/header.php`, `admin/includes/breadcrumb.php`, `admin/includes/pagination.php`, `admin/includes/page_template.php` — Admin panel navigation and layout.
- **User Panel includes**: `user/includes/sidebar.php`, `user/includes/header.php`, `user/includes/footer.php` — User dashboard navigation.

## 7. Page-to-API Connection Map

| User Action | PHP Page | API Called |
|---|---|---|
| Login with Google | `/auth/login.php` | `/api/auth/google.php` → `/api/auth/callback.php` |
| Email/Password Login | `/auth/login.php` | Server-side POST (no AJAX) |
| Signup | `/auth/signup.php` | Server-side POST (no AJAX) |
| Save phone (post-OAuth) | redirect to `/api/auth/save_phone.php` | `/api/auth/save_phone.php` |
| Add to Cart (course) | `/courses/details.php` | `POST /api/cart/add.php` |
| View Cart (course) | `/cart/index.php` | `GET /api/cart/view.php` |
| Checkout (course) | `/checkout/index.php` | `POST /api/checkout/submit.php` |
| Download PDF | `/user/purchases.php` | `GET /api/courses/download.php?order_id=...` |
| Store Checkout | `/store/checkout/index.php` | `POST /api/store/checkout.php` |
| View Store Invoice | `/store/checkout/success.php` | `GET /api/store/invoice.php?order=...` |
| Track Store Order | `/store/order-tracking/index.php` | Reads DB via PHP directly |
| Subscribe Newsletter | Any page | `POST /api/subscribe.php` |
| Contact Form | Root pages | `POST /api/contact.php` |
| Admin: Manage Blogs | `/admin/blogs.php` | `/api/admin/blogs.php` |
| Admin: Manage Courses | `/admin/courses.php` | `/api/admin/courses.php` |
| Admin: Dashboard Stats | `/admin/index.php` | `GET /api/admin/dashboard.php` |
| Admin: Upload Media | `/admin/uploads.php` | `POST /api/upload.php` |
| Admin: Import PDF | `/admin/run_course_import.php` | `POST /api/admin/import_pdf.php` |
| Admin: Manage Store Products | `/admin/store-products.php` | `/api/admin/store-products.php` |
| Admin: Manage Store Orders | `/admin/store-orders.php` | `/api/admin/store-orders.php` |
| Qikink: Status Update | (External webhook) | `POST /api/webhooks/qikink.php` |

## 8. Security & Connections Audited
- **Database Connection**: `config/database.php` uses prepared statements (PDO) strictly, preventing SQL injection.
- **Session Handling**: Uses strict PHP sessions with `httponly`, `samesite=Lax`. No sensitive data in client cookies.
- **API Protection**: API endpoints return 401/403 HTTP status codes if unauthorized, rather than leaking data.
- **CSRF Protection**: All state-changing requests validated via `requireCsrf()`.
- **Rate Limiting**: Contact form and login attempts are rate-limited (DB-backed via `rate_limit_log` table).
- **File Downloads**: PDFs in `/private/courses/` are never served directly — always streamed via `/api/courses/download.php` with ownership verification.
- **Webhook Security**: Qikink webhook logs all payloads to `private/logs/webhooks.log` for audit trail.
- **Store Checkout**: Store orders require login (`Auth::requireLogin()`). Cart data is validated server-side before order creation.
