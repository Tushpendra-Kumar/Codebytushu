# CodeByTushu - Database Schemas

This document outlines the core tables used in the MySQL database based on the `database/schema.sql` architecture (last updated with migrations 001–012).

> [!NOTE]
> The complete schema is in `database/schema.sql`. Incremental changes are in `database/migrations/` (files 002–012). Always run new migrations via `database/run_migrations.php` or apply SQL directly.

## 1. Users Table (`users`)
The central table for all authentication and user profiles.
- `id` (INT, PK, AUTO_INCREMENT): Unique identifier.
- `full_name` (VARCHAR 150): User's full name.
- `username` (VARCHAR 50, UNIQUE): Unique username (auto-generated or user-set).
- `email` (VARCHAR 191, UNIQUE): Unique email address.
- `phone` (VARCHAR 20, NULL): Optional phone number (collected after Google OAuth).
- `password_hash` (VARCHAR 255, NULL): Bcrypt hash of the user's password (NULL for Google OAuth-only users).
- `google_uid` (VARCHAR 100, NULL): Google OAuth unique ID.
- `profile_image` (VARCHAR 500, NULL): URL or relative path to the user's avatar.
- `role` (ENUM): `user`, `editor`, `admin`, `super_admin`. Default: `user`.
- `status` (ENUM): `active`, `banned`, `pending`. Default: `pending`.
- `email_verified_at` (TIMESTAMP, NULL): Set when email is verified.
- `email_verify_token` (VARCHAR 100, NULL): Token for email verification link.
- `password_reset_token` (VARCHAR 100, NULL): Token for password reset link.
- `password_reset_expires` (TIMESTAMP, NULL): Expiry for reset token.
- `remember_token` (VARCHAR 100, NULL): Persistent "Remember Me" token.
- `last_login_at` (TIMESTAMP, NULL): Last login timestamp.
- `created_at` / `updated_at` (TIMESTAMP)

> [!WARNING]
> **Data Leak Prevention Rule:**
> Future AI models and developers MUST NEVER expose `password_hash`, `google_uid`, `remember_token`, or `email_verify_token` in any public API JSON response. Always filter user objects before returning them to the frontend.

## 2. Site Configuration (`site_config`)
Stores dynamic website settings (managed via `/admin/settings.php`).
- `setting_key` (VARCHAR, PK): Unique key (e.g., `maintenance_mode`, `site_tagline`).
- `setting_value` (TEXT): Value of the setting.
- `updated_at` (TIMESTAMP): Last updated timestamp.

## 3. Blog System
- **`blog_categories`**: Stores blog categories.
  - `id`, `name`, `slug`, `description`, `created_at`
- **`blog_tags`**: Stores blog tags.
  - `id`, `name`, `slug`
- **`blog_articles`**: The main posts table.
  - `id`, `title`, `slug`, `content` (LONGTEXT), `excerpt`, `author_id` (FK → users), `category_id` (FK → blog_categories), `thumbnail_url`, `is_published`, `views`, `created_at`, `updated_at`
- **`blog_post_tags`**: Pivot table linking blog_articles and blog_tags.
  - `article_id`, `tag_id`

## 4. Courses & Purchasing
- **`categories`**: Shared categories for courses.
  - `id`, `name`, `slug`, `icon`, `created_at`
- **`courses`**: Stores premium and free courses.
  - `id`, `title`, `slug`, `description` (LONGTEXT), `short_description`, `price` (DECIMAL), `original_price`, `category_id` (FK), `thumbnail_path`, `download_file_path` (path to private PDF), `instructor_name`, `is_published`, `is_featured`, `created_at`, `updated_at`
  - Additional fields (from migration 012): `pdf_page_count`, `pdf_topics` (JSON), `pdf_source_file`
- **`cart_items`**: Active cart items for users.
  - `id`, `user_id` (FK → users), `course_id` (FK → courses), `created_at`
- **`orders`**: Order records.
  - `id`, `user_id` (FK → users), `total_amount` (DECIMAL), `payment_method` (VARCHAR), `payment_status` (ENUM: `pending`, `verified`, `failed`), `upi_transaction_id`, `notes`, `created_at`, `updated_at`
- **`order_items`**: Items within each order.
  - `id`, `order_id` (FK → orders), `course_id` (FK → courses), `price` (DECIMAL)

## 5. LeetCode Solutions (`leetcode_solutions`)
Stores daily DSA solutions managed via Admin CMS.
- `id`, `problem_title`, `problem_url`, `problem_number`, `difficulty` (ENUM: Easy/Medium/Hard), `solution_content` (LONGTEXT — supports code blocks, explanations), `language`, `month`, `year`, `solve_date`, `created_at`, `updated_at`
- Additional fields (from migrations 002, 004): `approach_type`, `time_complexity`, `space_complexity`, `video_url`, `tags`

## 6. Contact & Newsletters
- **`contact_messages`**: Stores messages from the "Contact Us" form.
  - `id`, `name`, `email`, `subject`, `message` (TEXT), `ip_address`, `is_read`, `created_at`
- **`newsletter_subscribers`**: General newsletter subscribers.
  - `id`, `email` (UNIQUE), `subscribed_at`, `is_active`
- **`video_editing_subscribers`**: Video editing newsletter subscribers.
  - `id`, `email` (UNIQUE), `subscribed_at`, `is_active`

## 7. Media Library (`media_library`)
Tracks all files uploaded by admins via `/admin/uploads.php`.
- `id`, `filename`, `original_name`, `file_path`, `file_url`, `mime_type`, `file_size`, `uploaded_by` (FK → users), `created_at`

## 8. Analytics (`analytics_events`)
Tracks page views and visitor data for the analytics dashboard.
- `id`, `page_url`, `device_type` (ENUM: desktop/mobile/tablet), `referrer`, `browser`, `ip_hash`, `user_id` (FK, NULL for guests), `created_at`

## 9. Rate Limiting (`rate_limit_log`)
Used for contact form and login rate limiting.
- `id`, `action_key` (VARCHAR — e.g., `contact_ip`, `login_email`), `ip_address`, `created_at`

## Security Audit & Data Leak Check
- Passwords are securely hashed using PHP `password_hash()` (bcrypt).
- The schema uses InnoDB engine with Foreign Keys for referential integrity.
- Sensitive columns (`password_hash`, `google_uid`, `remember_token`, `email_verify_token`) are excluded from all JSON API responses.
- No visible data leaks in the schema design.
