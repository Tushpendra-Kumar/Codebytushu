# CodeByTushu - Project Flow & User Journey

This document outlines the primary data flows and user journeys throughout the application.

## 1. Authentication Flows

### Google OAuth Login (Primary Method)
1. **Client**: User clicks "Continue with Google" at `/auth/login.php`.
2. **Redirect**: PHP redirects to `/api/auth/google.php`, which generates the Google consent URL.
3. **Google**: User completes Google sign-in. Google redirects to `/api/auth/callback.php?code=...`.
4. **Verification**: PHP exchanges the authorization code for an Access Token. Fetches Google User Profile (name, email, avatar, google_uid).
5. **Database**: Checks `users` table for `google_uid` or `email`:
   - **Existing user** → Log them in: `Auth::loginWithGoogle()` → sets `$_SESSION['user_id']`.
   - **New user** → Create row in `users` table, then log in.
   - **New user needs phone** → Redirect to `/api/auth/save_phone.php` to collect phone number first.
6. **Session**: `$_SESSION['user_id']`, `$_SESSION['cbt_user']` populated.
7. **Client**: Redirected to `/?loggedin=1` or the `redirect_after_login` URL stored in session.

### Email/Password Login
1. **Client**: User submits credentials at `/auth/login.php` (standard form).
2. **Server-Side PHP**: CSRF token verified → queries `users` table by email → `password_verify()` against `password_hash`.
3. **Email Verification Check**: If `email_verified_at` is NULL, redirect to verification-required page.
4. **Session**: `Auth::login($user)` sets `$_SESSION['user_id']`.
5. **Remember Me**: If checked, a secure token is stored in `users` table and an `httponly` cookie is set.
6. **Client**: Redirected to dashboard or original page.

### Email/Password Registration
1. **Client**: User submits at `/auth/signup.php`.
2. **Server-Side PHP**: Validates input → `password_hash()` → inserts into `users` table → sends verification email via `Mailer`.
3. **Email Verification**: User clicks link in email → `/auth/verify-email.php?token=...` → updates `email_verified_at`.

### Forgot Password Flow
1. **Client**: Submits email at `/auth/forgot-password.php`.
2. **Server**: Generates secure reset token → stores in `users.password_reset_token` → sends email via `Mailer`.
3. **Client**: Clicks link in email → `/auth/reset-password.php?token=...` → enters new password.
4. **Server**: Verifies token expiry → `password_hash()` new password → clears token.

## 2. Protected Page Flow
When a user visits a protected route (e.g., `/user/dashboard.php`, `/Leetcode/index.php`, `/blogs/index.php`):

1. **Bootstrap**: `config/app.php` and `classes/Auth.php` are required.
2. **Session Boot**: `Auth::boot()` starts session, checks "Remember Me" cookie if needed.
3. **Enforcement**: `Auth::requireLogin()` is called. If the user is NOT logged in, they are immediately redirected (HTTP 302) to `/auth/login.php?next=/original/path`.
4. **Render**: If authorized, the server queries the database for user-specific data and renders the HTML.

## 3. Admin Panel Flow
When a user visits any `/admin/*.php` page:

1. `require_once __DIR__ . '/includes/auth_check.php'` is the first include.
2. This internally calls `Auth::boot()` + `Auth::requireAdmin()`.
3. `requireAdmin()` checks that `$_SESSION['cbt_user']['role']` is `admin` or `super_admin`. Otherwise redirects to `/` with a 403.
4. The page template (header + sidebar + content + footer) is assembled via admin includes.

## 4. Course Purchase Flow
1. **Browse**: User visits `/courses/index.php` (login required).
2. **Details**: User clicks a course → `/courses/details.php?slug=...`.
3. **Buy/Add to Cart**: User clicks "Add to Cart" → AJAX to `POST /api/cart/add.php`.
4. **Cart Review**: User visits `/cart/index.php`.
5. **Checkout**: User submits order → `POST /api/checkout/submit.php` or `submit_single.php` → Order created in `orders` (with `order_type = 'course'`) + `order_items` tables.
6. **Payment**: UPI payment shown. After payment, user uploads screenshot or references transaction ID.
7. **Verification**: Admin manually verifies in `/admin/payments.php` → marks order as `verified`.
8. **Download**: Once verified, user sees "Download PDF" in `/user/purchases.php` → `GET /api/courses/download.php?order_id=...` streams the PDF securely.

> [!NOTE]
> Automatic webhook-based payment verification (Razorpay) is planned but not yet implemented for courses. Current flow requires manual admin verification.

## 5. Store Purchase Flow (Phase 1 — Live)
1. **Browse**: User visits `/store/index.php` (publicly accessible, no login required). Products loaded from DB via Admin CMS.
2. **Product Detail**: User visits static `store/product-details/index.html` (reads from `store/js/data.js` — legacy, pending PHP migration).
3. **Add to Cart**: Cart is managed via browser localStorage (no DB yet).
4. **Checkout**: User goes to `store/checkout/index.php` (login required). Enters shipping details.
5. **Order Submit**: JavaScript sends `POST /api/store/checkout.php` with cart items + shipping address.
6. **Order Created**: Server creates order in `orders` table with `order_type = 'store'`, shipping fields, and individual `order_items` with `product_id`.
7. **Success**: User redirected to `store/checkout/success.php` with order number.
8. **Tracking**: User can track order at `store/order-tracking/index.php`.
9. **Invoice**: User can view/print invoice via `GET /api/store/invoice.php?order=CBT-2026-XXXXXX`.
10. **Admin**: Admin manages products in `/admin/store-products.php`, manages/fulfills orders in `/admin/store-orders.php`.

### Qikink Print-on-Demand Sub-flow
- If a product has a `qikink_base_sku`, it's a print-on-demand product.
- After order is placed, admin (or future automation) dispatches the order to Qikink.
- Qikink sends shipping status updates to `/api/webhooks/qikink.php`.
- Webhook updates `orders` table (`qikink_status`, `awb_number`, `courier_name`, `tracking_number`).
- Mailer sends a shipping confirmation email to the user.

> [!NOTE]
> Phase 2 will replace the static `store/product-details/index.html` with a PHP page, move cart to DB-backed storage, and add Razorpay payment gateway.

## 6. Data Modification Flow (CSRF Protection)
Whenever a user modifies data (e.g., updating profile, submitting a form):
1. **Form**: A hidden CSRF token is embedded in the form using `<?= csrfField() ?>`.
2. **Submission**: The token is sent alongside the POST data.
3. **API Validation**: The receiving API endpoint calls `requireCsrf()`. If the token is missing or invalid, the request is rejected with a 403 Forbidden error.
4. **Execution**: The database query executes securely via PDO prepared statements.

## 7. Analytics Tracking Flow
Every PHP page includes `includes/analytics.php` which:
1. Detects the current page URL, device type (mobile/desktop/tablet), and referrer.
2. Inserts a row into the `analytics_events` table.
3. This data is consumed by `/admin/analytics.php` to show visitor charts, device breakdowns, and top pages.

## 8. Email Flow (via Mailer)
The `Mailer` class (`classes/Mailer.php`) wraps PHPMailer and is used for:
- **Email Verification** — Sent on new signup.
- **Password Reset** — Sent on forgot-password request.
- **Shipping Confirmation** — Sent when Qikink webhook updates order status to `Dispatched`.
- Configured via SMTP constants from `config/app.php` (SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS).

## 9. PDF Course Import Flow (Admin)
When admin imports a course from a PDF:
1. Admin visits `/admin/run_course_import.php`.
2. PDF file is uploaded and parsed by `/api/admin/import_pdf.php`.
3. PDF metadata (page count, topics JSON) is extracted and stored in the `courses` table.
4. Course download file is stored securely in `/private/courses/` (never publicly accessible).
5. Users can only download the PDF via `/api/courses/download.php` after purchase verification.

## 10. AI Chat Assistant Flow
1. **Loading**: The AI widget is injected via `includes/ai-widget-loader.php` (for PHP pages) or inline scripts (for static HTML pages like `index.html`).
2. **Connection**: The frontend widget connects via Socket.IO to the Node.js microservice hosted on Render (`ai-service`).
3. **Context Gathering**: The frontend sends the user's current URL and interaction history to the backend.
4. **Prompt Building**: The Node.js server constructs a context-aware system prompt, instructing the AI (Gemini v2 API) to respond in a friendly Hinglish tone and prioritize CodeByTushu specific content (Courses, Blogs, LeetCode, Store).
5. **Streaming & Fallback**: The Gemini API streams the response back to the Node.js server, which pipes it via Socket.IO back to the user's chat window in real-time. If the primary model hits a rate limit or quota error, a built-in multi-model fallback system seamlessly switches to alternative models (e.g. `gemini-3.5-flash-lite`, `gemini-3.6-flash`) to ensure consistent availability.
