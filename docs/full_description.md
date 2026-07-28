# CodeByTushu - Full Codebase Description

This document provides a comprehensive overview of the CodeByTushu platform. It is designed to act as a primer for future AI models or developers joining the project.

## 1. Platform Overview
CodeByTushu is a premium educational platform offering programming courses, LeetCode solutions, video editing assets, and developer blogs. The platform emphasizes a modern, dark-themed, glassmorphism UI with a robust and secure server-side backend. **The platform is currently live and functional** with a full PHP + MySQL stack.

## 2. Tech Stack
- **Frontend**: HTML5, Vanilla CSS3 (custom CSS architecture, no Tailwind), Vanilla JavaScript.
- **Backend**: Core PHP 8+ (No heavy frameworks like Laravel — purely custom MVC-inspired routing and includes).
- **Database**: MySQL (accessed via PHP PDO with strict prepared statements).
- **Authentication**: Native PHP Sessions + Google OAuth 2.0 integration via the `/api/auth/` endpoints.
- **Email**: PHPMailer (SMTP) via `classes/Mailer.php` for transactional emails (verification, password reset).
- **Environment**: `vlucas/phpdotenv` for secure `.env` loading via Composer.

## 3. Directory Structure
Understanding where files live is critical for navigating the codebase:

| Directory / File | Purpose |
|---|---|
| `/` (Root) | Public HTML/PHP entry points (`index.html`, `about-platform.html`, `blog-detail.php`, `blogs.php`, `404.php`) |
| `/api/` | Backend API endpoints — only return JSON or perform redirects |
| `/api/auth/` | Auth APIs: `google.php`, `callback.php`, `logout.php`, `status.php`, `save_phone.php`, `resend-verification.php` |
| `/api/admin/` | Admin AJAX APIs: blogs, courses, leetcode, users, dashboard, settings, payments, messages, categories, import_pdf |
| `/api/cart/` | Cart APIs: `add.php`, `remove.php`, `view.php` |
| `/api/checkout/` | Checkout APIs: `submit.php`, `submit_single.php` |
| `/api/courses/` | Course download API: `download.php` (secure PDF streaming) |
| `/auth/` | Auth pages: `login.php`, `signup.php`, `logout.php`, `forgot-password.php`, `reset-password.php`, `verify-email.php` |
| `/classes/` | Core PHP classes: `Auth.php`, `Mailer.php`, `Upload.php` |
| `/config/` | App configuration: `app.php` (boot file + constants), `database.php` (PDO singleton) |
| `/includes/` | Shared UI/logic: `navbar.php`, `functions.php`, `analytics.php`, `error_page.php`, `auth_middleware.php` |
| `/admin/` | Full Admin Panel (dashboard, blogs, courses, leetcode, users, analytics, uploads, settings, payments, messages, newsletter, categories) |
| `/admin/includes/` | Admin-specific includes: `auth_check.php`, `head.php`, `header.php`, `sidebar.php`, `footer.php`, `breadcrumb.php`, `pagination.php`, `page_template.php` |
| `/user/` | User-facing protected pages: `dashboard.php`, `purchases.php`, `courses.php`, `settings.php`, `orders.php` (stub), `certificates.php` (stub), `downloads.php` (stub) |
| `/user/includes/` | User panel includes: `header.php`, `sidebar.php`, `footer.php` |
| `/blogs/` | Blogs module: `index.php` (DB-driven listing) |
| `/courses/` | Courses module: `index.php` (listing), `details.php`, `payment_modal.php` |
| `/Leetcode/` | LeetCode module: `index.php`, `month.php`, `problems.php`, `solution.php`, `donate.php` |
| `/video-editing/` | Video Editing module: `index.php`, `app.js`, `style.css` |
| `/store/` | Store module: `index.php` (static), `product-details/index.html` (static), `cart/index.html` (static) |
| `/checkout/` | Checkout pages: `index.php`, `success.php` |
| `/cart/` | Cart page: `index.php` (PHP, active) + `index.html` (legacy static) |
| `/database/` | SQL schemas (`schema.sql`) and migrations (`migrations/002–012`, 13 files) |
| `/database/backups/` | Pre-migration backup files (e.g., `pre_migration_008.sql`) |
| `/private/` | Server-only files: `courses/` (PDFs), migration tools (`execute_migration.php`, `preview_migration.php`) |
| `/uploads/` | User-uploaded content (gitignored) |
| `/assets/` | Static assets (`images/` subdirectory, default course image) |
| `/image1/` | Landing page hero/section images |
| `/image2/` | Additional landing page images |
| `/docs/` | All developer/AI documentation (7 files) |

## 4. Design Aesthetics
The platform uses a strict design system defined globally in `styles.css`:
- **Color Palette**: Dark background (`#0a0a0a`), slightly lighter cards (`#111111`), with vibrant gold/yellow accents (`#ffc400`).
- **Typography**: Inter/Poppins font families (Google Fonts).
- **Interactions**: Smooth hover states, micro-animations, and glassmorphism (translucent backgrounds with backdrop blur).
- **No External UI Frameworks**: Pure Vanilla CSS — no Bootstrap, no Tailwind.

## 5. Key Module States (July 2026)

| Module | Backend | Auth-Gated | Admin CMS | Notes |
|---|---|---|---|---|
| Landing Page | Static HTML | ❌ Public | N/A | `index.html` |
| Blogs | ✅ DB-powered | ✅ Login Required | ✅ Full CMS | `/blogs/index.php` + `/blog-detail.php` |
| Courses | ✅ DB-powered + Purchase | ✅ Login Required | ✅ Full CMS | `/courses/index.php` + `/courses/details.php` |
| LeetCode | ✅ DB-powered | ✅ Login Required | ✅ Full CMS | `/Leetcode/*.php` |
| Video Editing | ✅ DB-powered | ✅ Login Required | Partial | `/video-editing/index.php` |
| Store | ❌ Static HTML/JS | ❌ Public | ❌ Pending | Backend migration pending |
| Auth System | ✅ Full flow | N/A | N/A | `/auth/*.php` + `/api/auth/*.php` |
| Admin Panel | ✅ Full CMS | ✅ Admin Only | N/A | `/admin/*.php` |
| User Dashboard | ✅ DB-powered | ✅ Login Required | N/A | `/user/*.php` |
| Cart/Checkout | ✅ DB-powered | ✅ Login Required | N/A | `/cart/index.php` + `/checkout/*.php` |

## 6. Development Principles
1. **No Client-Side Secrets**: All sensitive logic (API keys, DB credentials) must reside in `.env` and be executed on the server via PHP.
2. **Server-Side Authentication**: The UI depends on `Auth::user()` server-side to render elements (like the Avatar vs Login button), eliminating client-side UI flashing.
3. **Vanilla Mastery**: We avoid heavy frontend frameworks (React/Vue) or CSS frameworks (Tailwind) unless explicitly requested, preferring optimized Vanilla code.
4. **Firebase is FULLY REMOVED**: PHP Sessions + Google OAuth via `api/auth/` is the active and only authentication system.
5. **PDO Everywhere**: 100% of DB queries use PDO prepared statements. Raw SQL concatenation is strictly forbidden.
6. **CSRF on All POST**: Every state-changing form or API call must validate CSRF tokens via `requireCsrf()`.
