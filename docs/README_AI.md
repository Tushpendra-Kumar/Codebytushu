# AI Context & Developer Onboarding Guide

Welcome! If you are an AI model or a new developer joining the CodeByTushu project, this document is your starting point. It provides the essential context needed to understand the codebase without risking sensitive data exposure.

## Project Context
CodeByTushu is a premium educational platform built for developers, offering premium courses, LeetCode daily solutions, video editing assets, and technical blogs. The platform also has a **print-on-demand merchandise store** integrated with Qikink. The platform has been **fully migrated** from a static HTML/JavaScript + Firebase approach to a robust **Server-Side PHP 8+ + MySQL** architecture.

## Current Architecture State (August 2026)
- ✅ **Authentication is LIVE** — Google OAuth + Email/Password (full flow with email verification, forgot password, remember-me)
- ✅ **Admin Panel is LIVE** — Full CMS for Blogs, Courses, LeetCode, Users, Analytics, Uploads, Settings, Store Products, Store Orders
- ✅ **Backend APIs are LIVE** — All core modules powered by PHP + MySQL PDO
- ✅ **All key modules require login** — Blogs, Courses, LeetCode, Video Editing are auth-gated
- ✅ **Migrations 002–013 applied** — 15 migration files total in `database/migrations/`
- ✅ **Store Phase 1 LIVE** — DB-powered product listing, Admin CMS, checkout with shipping, order tracking, invoice generator, Qikink webhook integration
- ✅ **AI Assistant LIVE** — Node.js microservice (Render) + Gemini v2 streaming API with context-aware Hinglish custom welcome flow
- ⏳ **Store Phase 2 pending** — PHP product-details page, DB-backed cart, Razorpay payment gateway for store
- ⏳ **Qikink integration** — Webhook listener live; full automatic Qikink order dispatch not yet implemented
- ⏳ **Payment webhooks (Courses)** — Order creation done, automated webhook verification pending
- ⏳ **Advanced course features** — Video player, progress tracking, certificates not yet started
- ⏳ **User dashboard stubs** — `orders.php`, `downloads.php`, `certificates.php` exist but show only empty-state placeholders

## How to Navigate the Docs
Before suggesting architectural changes or modifying core files, please review the following files in the `/docs/` directory:

1. **`connection.md`**: Understand how the frontend views connect to backend APIs and the full request lifecycle.
2. **`schemas.md`**: Review the MySQL database schema before writing queries.
3. **`project_flow.md`**: Understand the Authentication and User Journey flows.
4. **`api_documentation.md`**: Review the standards for writing JSON API endpoints.
5. **`security.md`**: Read the strict security requirements that must be followed.
6. **`full_description.md`**: Complete codebase description and design system overview.

## Directory Quick Reference

| Path | Purpose |
|---|---|
| `/config/app.php` | Boot file — loads `.env`, defines all constants |
| `/config/database.php` | PDO singleton — use `db()` everywhere |
| `/classes/Auth.php` | Session, login, OAuth, role checks |
| `/classes/Mailer.php` | PHPMailer transactional email wrapper |
| `/classes/Upload.php` | Secure file upload handler |
| `/includes/functions.php` | Global helpers (`sanitize`, `post`, `get`, `requireCsrf`, etc.) |
| `/includes/navbar.php` | Shared navbar (auth-aware, server-side rendered) |
| `/includes/analytics.php` | Page view tracker (logs to `analytics_events` table) |
| `/api/` | All AJAX/JSON endpoints |
| `/api/store/` | Store-specific API: `checkout.php`, `invoice.php` |
| `/api/webhooks/` | Webhook receivers: `qikink.php` |
| `/admin/` | Admin Panel (role-protected) |
| `/admin/store-products.php` | Admin: Store product management (CRUD) |
| `/admin/store-orders.php` | Admin: Store order management + fulfillment tracking |
| `/auth/` | Login, Signup, Forgot/Reset Password pages |
| `/user/` | User Dashboard, Profile, Purchases |
| `/store/` | Store module: `index.php` (DB-driven), `checkout/`, `order-tracking/` |
| `/ai-service/` | Node.js microservice running on Render for Gemini AI |
| `/ai-widget/` | Frontend HTML/JS/CSS widget for the AI chat |
| `/database/schema.sql` | Complete MySQL schema |
| `/database/migrations/` | Incremental migration scripts (002–013, 15 files total) |

## ⚠️ Legacy Files — DO NOT Use for Reference
The following files exist but are **from the old static frontend-first approach** and are NOT the active implementation for their respective module:

| File/Folder | Why It's Legacy |
|---|---|
| `courses/js/data.js` | Old hardcoded mock course data. Active courses come from MySQL. |
| `store/js/data.js` | Hardcoded store product data (71KB). Store listing is now DB-powered via `store/index.php`. Still used by legacy `store/product-details/index.html` and `store/cart/index.html`. |
| `store/product-details/index.html` | Legacy static product detail page. Will be replaced by a PHP page in Store Phase 2. |
| `store/cart/index.html` | Legacy static store cart. Will be replaced by DB-backed cart in Store Phase 2. |

## AI Assistant Rules
As an AI modifying this codebase, you MUST adhere to the following rules:

1. **No Data Leaks**: Never print, expose, or write API keys, `.env` variables, or database passwords in any generated code, console output, or documentation.
2. **Design Language**: If you create a new UI component, it must match the global aesthetic defined in `styles.css` (Dark theme, `#0a0a0a` background, `#ffc400` accent, glassmorphism). Do NOT introduce Tailwind CSS or external component libraries.
3. **Server-Side Auth**: Always rely on `Auth::user()` and PHP Sessions for determining login state. Do NOT use client-side JavaScript fetching for critical auth checks.
4. **Security First**: All database interactions must use PDO prepared statements. All POST forms must include CSRF tokens via `<?= csrfField() ?>`.
5. **Boot Chain**: Every PHP page/API must start with: `require_once 'config/app.php'` → `require_once 'classes/Auth.php'` → `Auth::boot()`.
6. **Firebase is REMOVED**: Do not reference or use Firebase for authentication. It is a fully removed legacy system. PHP Auth is the active system.
7. **Do NOT use legacy static files as templates** — Always base new pages on existing PHP-powered pages (e.g., `blogs/index.php`, `courses/index.php`, `store/index.php`).
8. **Store Architecture**: The store checkout uses `/api/store/checkout.php` (not `/api/checkout/submit.php` which is for courses). Store orders have `order_type = 'store'` and include shipping fields.
9. **Qikink Integration**: Store products can have `print_file_path` and `qikink_base_sku` fields for print-on-demand. The Qikink webhook is at `/api/webhooks/qikink.php`.

By following these guidelines, you will help maintain a secure, fast, and beautiful platform for CodeByTushu users.
