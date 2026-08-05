# CodeByTushu - Database Schemas

This document outlines the core tables used in the MySQL database based on the `database/schema.sql` architecture (last updated with migrations 002–013, 15 migration files total).

> [!NOTE]
> The complete schema is in `database/schema.sql`. Incremental changes are in `database/migrations/` (files 002–013, 15 files total). Always run new migrations via `database/run_migrations.php` or apply SQL directly.

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

> Migration `003_blog_cms_v2.sql` added extended blog CMS fields.

## 4. Courses & Purchasing
- **`categories`**: Shared categories for courses.
  - `id`, `name`, `slug`, `icon`, `created_at`
- **`courses`**: Stores premium and free courses.
  - `id`, `title`, `slug`, `description` (LONGTEXT), `short_description`, `price` (DECIMAL), `original_price`, `category_id` (FK), `thumbnail_path`, `download_file_path` (path to private PDF), `instructor_name`, `is_published`, `is_featured`, `created_at`, `updated_at`
  - Additional fields (from migration 012-pdf): `pdf_page_count`, `pdf_topics` (JSON), `pdf_source_file`
- **`cart_items`**: Active cart items for users (courses only).
  - `id`, `user_id` (FK → users), `course_id` (FK → courses), `created_at`
- **`orders`**: Order records — shared between courses and store products.
  - `id`, `user_id` (FK → users), `order_type` (ENUM: `course`, `store`, `mixed`), `total_amount` (DECIMAL), `payment_method` (ENUM: `upi`, `razorpay`, `free`, `cod`), `payment_status` (ENUM: `pending`, `verified`, `failed`), `upi_transaction_id`, `payment_reference`, `notes`, `admin_notes`
  - Shipping fields (store orders): `shipping_name`, `shipping_phone`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_pincode`
  - Fulfillment: `fulfillment_status` (ENUM: `pending`, `processing`, `shipped`, `delivered`, `cancelled`), `tracking_number`
  - Qikink fields: `qikink_order_id`, `qikink_status`, `awb_number`, `courier_name`
  - `created_at`, `updated_at`
- **`order_items`**: Items within each order (courses or store products).
  - `id`, `order_id` (FK → orders), `course_id` (FK → courses, NULL for store items), `product_id` (FK → store_products, NULL for course items), `product_name`, `quantity`, `price` (DECIMAL)

> Migration `011_course_store_v1.sql` added store/cart tables. Migration `012_pdf_course_fields.sql` added PDF metadata fields.
> Migration `012_store_products_v1.sql` created `store_products` table and enhanced `orders`/`order_items` with store-specific fields.
> Migration `013_qikink_integration.sql` added Qikink tracking fields to `store_products` and `orders`.

## 5. Store Products (`store_products`)
Manages the merchandise/print-on-demand product catalog.
- `id` (INT, PK, AUTO_INCREMENT)
- `title` (VARCHAR 255): Product title.
- `description` (TEXT): Product description.
- `price` (DECIMAL 10,2): Selling price.
- `category` (VARCHAR 100): Product category (e.g., `T-Shirts`, `Hoodies`). Default: `Merchandise`.
- `images` (JSON): Array of image paths.
- `print_file_path` (VARCHAR 500): High-res transparent PNG for Qikink print-on-demand.
- `qikink_base_sku` (VARCHAR 100): Qikink product SKU (e.g., `MNS-RN-BLK`).
- `thumbnail` (VARCHAR 500): Primary thumbnail path.
- `features` (JSON): Array of product feature strings.
- `stock_status` (ENUM: `in-stock`, `out-of-stock`): Default `in-stock`.
- `rating` (DECIMAL 2,1), `reviews_count` (INT): Display rating/review counts.
- `is_active` (TINYINT): Product visibility toggle.
- `is_new_arrival` (TINYINT): Badge toggle.
- `sort_order` (INT): Manual display ordering.
- `created_at`, `updated_at` (TIMESTAMP)

## 6. LeetCode Solutions (`leetcode_solutions`)
Stores daily DSA solutions managed via Admin CMS.
- `id`, `problem_title`, `problem_url`, `problem_number`, `difficulty` (ENUM: Easy/Medium/Hard), `solution_content` (LONGTEXT — supports code blocks, explanations), `language`, `month`, `year`, `solve_date`, `created_at`, `updated_at`
- Additional fields (from migrations 002, 004): `approach_type`, `time_complexity`, `space_complexity`, `video_url`, `tags`

> Migration `002_leetcode_cms_v2.sql` and `004_leetcode_missing_columns.sql` added extended fields.

## 7. Contact & Newsletters
- **`contact_messages`**: Stores messages from the "Contact Us" form.
  - `id`, `name`, `email`, `subject`, `message` (TEXT), `ip_address`, `is_read`, `created_at`
- **`newsletter_subscribers`**: General newsletter subscribers.
  - `id`, `email` (UNIQUE), `subscribed_at`, `is_active`
- **`video_editing_subscribers`**: Video editing newsletter subscribers.
  - `id`, `email` (UNIQUE), `subscribed_at`, `is_active`

> Migrations `009_newsletter_subscribers.sql` and `010_video_editing_subscribers.sql` created these tables.

## 8. Media Library (`media_library`)
Tracks all files uploaded by admins via `/admin/uploads.php`.
- `id`, `filename`, `original_name`, `file_path`, `file_url`, `mime_type`, `file_size`, `uploaded_by` (FK → users), `created_at`

> Migration `005_media_library_v2.sql` created this table.

## 9. Analytics (`analytics_events`)
Tracks page views and visitor data for the analytics dashboard.
- `id`, `page_url`, `device_type` (ENUM: desktop/mobile/tablet), `referrer`, `browser`, `ip_hash`, `user_id` (FK, NULL for guests), `created_at`

> Migration `008_analytics_performance.sql` added performance indexes.

## 10. Rate Limiting (`rate_limit_log`)
Used for contact form and login rate limiting.
- `id`, `action_key` (VARCHAR — e.g., `contact_ip`, `login_email`), `ip_address`, `created_at`

## Security Audit & Data Leak Check
- Passwords are securely hashed using PHP `password_hash()` (bcrypt).
- The schema uses InnoDB engine with Foreign Keys for referential integrity.
- Sensitive columns (`password_hash`, `google_uid`, `remember_token`, `email_verify_token`) are excluded from all JSON API responses.
- No visible data leaks in the schema design.

## Migration File Index

| File | Purpose |
|---|---|
| `002_leetcode_cms_v2.sql` | Extended LeetCode solution fields |
| `003_blog_cms_v2.sql` | Extended Blog CMS fields |
| `004_courses_cms_v2.sql` | Extended Course CMS fields |
| `004_leetcode_missing_columns.sql` | Additional LeetCode columns (patch) |
| `005_media_library_v2.sql` | Created `media_library` table |
| `005_migrate_may2026_solutions.sql` | Migrated May 2026 LeetCode solutions to DB |
| `006_contact_messages_v2.sql` | Created `contact_messages` table |
| `007_settings_v2.sql` | Created `site_config` table with default values |
| `008_analytics_performance.sql` | Added indexes for analytics performance |
| `009_newsletter_subscribers.sql` | Created `newsletter_subscribers` table |
| `010_video_editing_subscribers.sql` | Created `video_editing_subscribers` table |
| `011_course_store_v1.sql` | Created `cart_items`, `orders`, `order_items` tables |
| `012_pdf_course_fields.sql` | Added PDF metadata fields to `courses` table |
| `012_store_products_v1.sql` | Created `store_products` table; enhanced `orders` & `order_items` with store/shipping/fulfillment fields |
| `013_qikink_integration.sql` | Added Qikink tracking fields (`print_file_path`, `qikink_base_sku`) to `store_products`; added `qikink_order_id`, `qikink_status`, `awb_number`, `courier_name` to `orders` |
