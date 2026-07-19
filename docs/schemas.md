# CodeByTushu - Database Schemas

This document outlines the core tables used in the MySQL database based on the `database/schema.sql` architecture.

## 1. Users Table (`users`)
The central table for all authentication and user profiles.
- `id` (INT, PK): Unique identifier.
- `full_name` (VARCHAR): User's full name.
- `username` (VARCHAR): Unique username.
- `email` (VARCHAR): Unique email address.
- `password_hash` (VARCHAR): Bcrypt hash of the user's password (NULL for Google OAuth users).
- `google_uid` (VARCHAR): Google OAuth unique ID.
- `profile_image` (VARCHAR): URL or relative path to the user's avatar.
- `role` (ENUM): `user`, `editor`, `admin`, `super_admin`.
- `status` (ENUM): `active`, `banned`, `pending`.
- `created_at` / `updated_at` (TIMESTAMP)

> [!WARNING]
> **Data Leak Prevention Rule:**
> Future AI models and developers MUST NEVER expose `password_hash`, `google_uid`, or `remember_token` in any public API JSON response. Always filter user objects before returning them to the frontend.

## 2. Site Configuration (`site_config`)
Stores dynamic website settings (like maintenance mode, platform features, etc.).
- `setting_key` (VARCHAR, PK): Unique key (e.g., `maintenance_mode`).
- `setting_value` (TEXT): Value of the setting.

## 3. Blog System
- `blog_categories`: Stores blog categories (id, name, slug).
- `blog_tags`: Stores blog tags (id, name, slug).
- `blogs`: The main posts table (id, title, slug, content, excerpt, author_id, category_id, is_published, views).
- `blog_post_tags`: Pivot table linking blogs and tags.

## 4. Courses & Modules
- `courses`: Stores premium and free courses (id, title, slug, description, price, instructor_id, thumbnail).
- `course_modules`: Sections within a course.
- `course_lessons`: Video/text lessons within a module.
- `user_courses`: Tracks which users have purchased or enrolled in which courses.

## 5. LeetCode Solutions
- `leetcode_solutions`: Stores daily DSA solutions (id, problem_title, problem_url, difficulty, solution_content, language).

## 6. Contact & Newsletters
- `contact_messages`: Stores messages from the "Contact Us" form (name, email, subject, message).
- `newsletter_subscribers`: Stores emails of users who subscribed to the newsletter.

## 7. Media Library (`media_library`)
Tracks all files uploaded by admins/users to the server.
- `id`, `filename`, `file_path`, `mime_type`, `file_size`, `uploaded_by`.

## Security Audit & Data Leak Check
- Passwords are securely hashed.
- The schema design is normalized and uses InnoDB constraints (Foreign Keys) to maintain referential integrity.
- There are no visible data leaks in the schema design.
