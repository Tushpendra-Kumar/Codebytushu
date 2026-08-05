# CodeByTushu - API Documentation Overview

This document provides a high-level map of the API endpoints used to power dynamic features without page reloads.

## 1. Authentication Endpoints (`/api/auth/`)
All files here manage session states and OAuth flows. Note: Standard login/signup/forgot-password are handled via full PHP pages in `/auth/`, not AJAX endpoints.

- **`GET /api/auth/google.php`**
  - **Purpose**: Generates the Google OAuth 2.0 consent URL and redirects the user.
  - **Output**: HTTP redirect to Google.

- **`GET /api/auth/callback.php`**
  - **Purpose**: Handles the response from Google, exchanges the authorization code for an Access Token, fetches the Google profile, and logs the user in (or creates account if first time).
  - **Output**: HTTP redirect to `/?loggedin=1` or `/api/auth/save_phone.php`.

- **`GET /api/auth/logout.php`**
  - **Purpose**: Destroys the PHP session and clears "Remember Me" cookies.
  - **Output**: Redirects to `/`.

- **`GET /api/auth/status.php`**
  - **Purpose**: Returns current authentication status as JSON.
  - **Output**: `{ "logged_in": true/false, "user": { ... } }`.

- **`POST /api/auth/save_phone.php`**
  - **Purpose**: Saves a user's phone number after Google OAuth registration.
  - **Inputs**: `phone`, `csrf_token`.
  - **Output**: JSON success or error.

- **`POST /api/auth/resend-verification.php`**
  - **Purpose**: Resends the email verification link.
  - **Inputs**: `csrf_token`.
  - **Output**: JSON success or error.

## 2. Cart Endpoints (`/api/cart/`) — Courses Only

- **`POST /api/cart/add.php`**
  - **Purpose**: Adds a course to the user's cart.
  - **Requires**: Login. `course_id`, `csrf_token`.
  - **Output**: `{ "success": true }` or error.

- **`POST /api/cart/remove.php`**
  - **Purpose**: Removes a course from the user's cart.
  - **Requires**: Login. `course_id`, `csrf_token`.
  - **Output**: JSON success or error.

- **`GET /api/cart/view.php`**
  - **Purpose**: Returns current cart contents for logged-in user.
  - **Requires**: Login.
  - **Output**: `{ "items": [...], "total": 0 }`.

## 3. Checkout Endpoints (`/api/checkout/`) — Courses Only

- **`POST /api/checkout/submit.php`**
  - **Purpose**: Creates an order from all cart items (courses).
  - **Requires**: Login. `csrf_token`, payment details.
  - **Output**: JSON with order ID or error.

- **`POST /api/checkout/submit_single.php`**
  - **Purpose**: Creates an order from a single course (direct buy).
  - **Requires**: Login. `course_id`, `csrf_token`.
  - **Output**: JSON with order ID or error.

## 4. Store Endpoints (`/api/store/`) — Store Products

- **`POST /api/store/checkout.php`**
  - **Purpose**: Creates a store order with shipping details from cart items (store products).
  - **Requires**: Login. JSON body with `items[]`, `shipping` object, `payment_method`.
  - **Note**: Creates order with `order_type = 'store'` including full shipping address.
  - **Output**: `{ "success": true, "order_number": "CBT-2026-XXXXXX" }` or error.

- **`GET /api/store/invoice.php?order=CBT-2026-XXXXXX`**
  - **Purpose**: Generates a professional printable HTML invoice for a store order.
  - **Requires**: Login + order ownership verification.
  - **Output**: HTML invoice page or 403/404 error.

## 5. Course Download Endpoint (`/api/courses/`)

- **`GET /api/courses/download.php?order_id=...`**
  - **Purpose**: Securely streams a purchased PDF course to the user.
  - **Requires**: Login + verified order ownership check.
  - **Output**: File stream (`application/pdf`) or 403 Forbidden.

## 6. Webhook Endpoints (`/api/webhooks/`)

- **`POST /api/webhooks/qikink.php`**
  - **Purpose**: Receives order status updates from Qikink (print-on-demand fulfillment).
  - **Triggers on**: Qikink order status changes (e.g., `In Production`, `Dispatched`, `Delivered`).
  - **Actions**: Updates `orders` table with `qikink_status`, `awb_number`, `courier_name`; sends shipping confirmation email to user via Mailer.
  - **Logs**: All webhook payloads are logged to `private/logs/webhooks.log`.
  - **Output**: `{ "success": true }` or error status.

## 7. Public/Contact Endpoints

- **`POST /api/contact.php`**
  - **Purpose**: Handles the Contact Us form submission, stores in `contact_messages` table.
  - **Inputs**: `name`, `email`, `subject`, `message`, `csrf_token`.
  - **Rate Limit**: 3 requests per 10 minutes per IP.
  - **Output**: JSON success or error.

- **`POST /api/subscribe.php`**
  - **Purpose**: Adds email to `newsletter_subscribers` table.
  - **Inputs**: `email`, `csrf_token`.
  - **Output**: JSON success or error.

- **`POST /api/subscribe_ve.php`**
  - **Purpose**: Adds email to video editing newsletter subscribers.
  - **Inputs**: `email`, `csrf_token`.
  - **Output**: JSON success or error.

## 8. Admin API Endpoints (`/api/admin/`)
All admin endpoints require the user to have `role = 'admin'` or `'super_admin'`.

| Endpoint | Methods | Purpose |
|---|---|---|
| `/api/admin/blogs.php` | GET, POST, PUT, DELETE | Blog CRUD operations |
| `/api/admin/courses.php` | GET, POST, PUT, DELETE | Course CRUD operations |
| `/api/admin/leetcode.php` | GET, POST, PUT, DELETE | LeetCode solution CRUD |
| `/api/admin/users.php` | GET, POST, PUT, DELETE | User management |
| `/api/admin/dashboard.php` | GET | KPI stats for live refresh |
| `/api/admin/settings.php` | GET, POST | Site settings read/write |
| `/api/admin/payments.php` | GET, POST | Course orders/payment management |
| `/api/admin/messages.php` | GET, POST | Contact messages inbox |
| `/api/admin/categories.php` | GET, POST, DELETE | Category management |
| `/api/admin/import_pdf.php` | POST | PDF course import/parsing |
| `/api/admin/store-products.php` | GET, POST, PUT, DELETE | Store product CRUD |
| `/api/admin/store-orders.php` | GET, POST | Store orders + fulfillment management |
| `/api/upload.php` | POST | Media file upload handler |

## 9. API Design Principles
1. **JSON Only**: Unless redirecting or serving an HTML invoice, all API endpoints must set `header('Content-Type: application/json')` and return valid JSON.
2. **Error Handling**: APIs catch `PDOException` and return clean error messages rather than raw SQL errors.
3. **Security**: All `POST`, `PUT`, `DELETE` operations require a CSRF token via `requireCsrf()`. All protected actions require the session user ID (`$_SESSION['user_id']`).
4. **HTTP Status Codes**: Return appropriate status codes (200, 201, 400, 401, 403, 404, 405, 500) — not just JSON `success: false`.
5. **Store vs Course separation**: Course cart/checkout uses `/api/cart/` and `/api/checkout/`. Store uses `/api/store/checkout.php` (separate endpoint with shipping address support).

> [!CAUTION]
> **Data Leak Prevention Rule:**
> When returning a "user" object in a JSON response, you MUST unset or exclude the `password_hash`, `google_uid`, `remember_token`, and `email_verify_token` fields before encoding.
