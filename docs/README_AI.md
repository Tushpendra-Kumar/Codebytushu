# AI Context & Developer Onboarding Guide

Welcome! If you are an AI model or a new developer joining the CodeByTushu project, this document is your starting point. It provides the essential context needed to understand the codebase without risking sensitive data exposure.

## Project Context
CodeByTushu is a premium educational platform built for developers, offering premium courses, LeetCode daily solutions, video editing assets, and technical blogs. The platform has been **fully migrated** from a static HTML/JavaScript + Firebase approach to a robust **Server-Side PHP 8+ + MySQL** architecture.

## Current Architecture State (July 2026)
- ✅ **Authentication is LIVE** — Google OAuth + Email/Password (full flow with email verification, forgot password, remember-me)
- ✅ **Admin Panel is LIVE** — Full CMS for Blogs, Courses, LeetCode, Users, Analytics, Uploads, Settings
- ✅ **Backend APIs are LIVE** — All core modules powered by PHP + MySQL PDO
- ✅ **All key modules require login** — Blogs, Courses, LeetCode, Video Editing are auth-gated
- ✅ **Migrations 002–012 applied** — 13 migration files total in `database/migrations/`
- ⏳ **Store backend** — Frontend complete (static HTML + JS data), backend integration pending
- ⏳ **Payment webhooks** — Order creation done, webhook verification pending
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
| `/admin/` | Admin Panel (role-protected) |
| `/auth/` | Login, Signup, Forgot/Reset Password pages |
| `/user/` | User Dashboard, Profile, Purchases |
| `/database/schema.sql` | Complete MySQL schema |
| `/database/migrations/` | Incremental migration scripts (002–012, 13 files total) |

## ⚠️ Legacy Files — DO NOT Use for Reference
The following files exist but are **from the old static frontend-first approach** and are NOT the active implementation:

| File/Folder | Why It's Legacy |
|---|---|
| `blogs/js/data.js` | Old hardcoded mock blog data (134KB). Active blogs come from MySQL. |
| `blogs/blog-details/index.html` | Old static blog detail page. Active: `/blog-detail.php` |
| `courses/js/data.js` | Old hardcoded mock course data. Active courses come from MySQL. |
| `courses/course-details/index.html` | Old static course detail. Active: `/courses/details.php` |
| `cart/index.html` | Old static cart page. Active: `/cart/index.php` |
| `update_lesson_counts.php` | One-time data fix script (root level) — should be removed |
| `update_thumbnails.php` | One-time data fix script (root level) — should be removed |

## AI Assistant Rules
As an AI modifying this codebase, you MUST adhere to the following rules:

1. **No Data Leaks**: Never print, expose, or write API keys, `.env` variables, or database passwords in any generated code, console output, or documentation.
2. **Design Language**: If you create a new UI component, it must match the global aesthetic defined in `styles.css` (Dark theme, `#0a0a0a` background, `#ffc400` accent, glassmorphism). Do NOT introduce Tailwind CSS or external component libraries.
3. **Server-Side Auth**: Always rely on `Auth::user()` and PHP Sessions for determining login state. Do NOT use client-side JavaScript fetching for critical auth checks.
4. **Security First**: All database interactions must use PDO prepared statements. All POST forms must include CSRF tokens via `<?= csrfField() ?>`.
5. **Boot Chain**: Every PHP page/API must start with: `require_once 'config/app.php'` → `require_once 'classes/Auth.php'` → `Auth::boot()`.
6. **Firebase is REMOVED**: Do not reference or use Firebase for authentication. It is a fully removed legacy system. PHP Auth is the active system.
7. **Do NOT use legacy static files as templates** — Always base new pages on existing PHP-powered pages (e.g., `blogs/index.php`, `courses/index.php`).

By following these guidelines, you will help maintain a secure, fast, and beautiful platform for CodeByTushu users.
