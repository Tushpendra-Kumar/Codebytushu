-- ============================================================
-- CodeByTushu — Complete MySQL Database Schema
-- Version: 2.0 | Engine: InnoDB | Charset: utf8mb4
-- Run this file ONCE on a fresh database.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;


-- ============================================================
-- PASS 1 — No foreign key dependencies
-- ============================================================

-- ------------------------------------------------------------
-- 1. users
--    Central user table: both public (Google OAuth + manual
--    signup) and admin accounts are stored here, distinguished
--    by the `role` column.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `full_name`           VARCHAR(200)     NOT NULL,
  `username`            VARCHAR(80)      NOT NULL,
  `email`               VARCHAR(255)     NOT NULL,
  `password_hash`       VARCHAR(255)     DEFAULT NULL COMMENT 'NULL for OAuth-only accounts',
  `google_uid`          VARCHAR(128)     DEFAULT NULL COMMENT 'Google OAuth sub claim',
  `profile_image`       VARCHAR(500)     DEFAULT NULL COMMENT 'Root-relative path or external URL',
  `role`                ENUM('super_admin','admin','editor','user') NOT NULL DEFAULT 'user',
  `status`              ENUM('active','banned','pending') NOT NULL DEFAULT 'active',
  `email_verified`      TINYINT(1)       NOT NULL DEFAULT 0,
  `remember_token`      VARCHAR(100)     DEFAULT NULL,
  `remember_expires`    TIMESTAMP        NULL DEFAULT NULL,
  `login_count`         INT UNSIGNED     NOT NULL DEFAULT 0,
  `last_login`          TIMESTAMP        NULL DEFAULT NULL,
  `created_at`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email`      (`email`),
  UNIQUE KEY `uq_username`   (`username`),
  UNIQUE KEY `uq_google_uid` (`google_uid`),
  KEY `idx_role`             (`role`),
  KEY `idx_status`           (`status`),
  KEY `idx_created_at`       (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. site_config
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_config` (
  `config_key`    VARCHAR(128)   NOT NULL,
  `config_value`  LONGTEXT       DEFAULT NULL,
  `value_type`    ENUM('text','json','url','html','number','bool') NOT NULL DEFAULT 'text',
  `label`         VARCHAR(200)   NOT NULL,
  `description`   VARCHAR(500)   DEFAULT NULL,
  `config_group`  VARCHAR(50)    NOT NULL DEFAULT 'general',
  `is_public`     TINYINT(1)     NOT NULL DEFAULT 1,
  `updated_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_key`),
  KEY `idx_group`  (`config_group`),
  KEY `idx_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. social_links
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `social_links` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `platform`        VARCHAR(50)   NOT NULL,
  `display_name`    VARCHAR(80)   NOT NULL,
  `url`             VARCHAR(500)  NOT NULL,
  `icon_class`      VARCHAR(100)  DEFAULT NULL,
  `brand_color_hex` VARCHAR(7)    DEFAULT NULL,
  `show_in_footer`  TINYINT(1)    NOT NULL DEFAULT 1,
  `show_in_contact` TINYINT(1)    NOT NULL DEFAULT 1,
  `sort_order`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_platform` (`platform`),
  KEY `idx_sort` (`sort_order`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. projects
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`             VARCHAR(200)  NOT NULL,
  `short_description` VARCHAR(500)  NOT NULL,
  `image_path`        VARCHAR(500)  NOT NULL,
  `project_url`       VARCHAR(500)  NOT NULL,
  `github_url`        VARCHAR(500)  DEFAULT NULL,
  `tech_stack`        VARCHAR(300)  DEFAULT NULL COMMENT 'Comma-separated: HTML, CSS, JS, GSAP',
  `is_featured`       TINYINT(1)    NOT NULL DEFAULT 0,
  `sort_order`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sort`     (`sort_order`),
  KEY `idx_active`   (`is_active`),
  KEY `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. services
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`             VARCHAR(100)  NOT NULL,
  `description`       TEXT          NOT NULL,
  `icon_identifier`   VARCHAR(100)  NOT NULL COMMENT 'CSS box ID: first/second/third/fourth',
  `hover_image_path`  VARCHAR(500)  DEFAULT NULL,
  `accent_color_hex`  VARCHAR(7)    DEFAULT '#ffc400',
  `sort_order`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sort`   (`sort_order`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. categories  (shared across blogs, courses, portfolio)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)  NOT NULL,
  `slug`        VARCHAR(100)  NOT NULL,
  `type`        ENUM('blog','course','portfolio','leetcode') NOT NULL DEFAULT 'blog',
  `description` VARCHAR(300)  DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug_type` (`slug`, `type`),
  KEY `idx_type`   (`type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. solution_tags  (LeetCode topic tags)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `solution_tags` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80)   NOT NULL,
  `slug`        VARCHAR(80)   NOT NULL,
  `color_hex`   VARCHAR(7)    DEFAULT '#ffc400',
  `description` VARCHAR(300)  DEFAULT NULL,
  `usage_count` INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name` (`name`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. donate_config
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `donate_config` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `currency_code`   VARCHAR(10)     NOT NULL,
  `currency_name`   VARCHAR(50)     NOT NULL,
  `currency_symbol` VARCHAR(10)     NOT NULL,
  `exchange_rate`   DECIMAL(12,6)   NOT NULL,
  `base_amount`     DECIMAL(10,2)   NOT NULL,
  `min_qty`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `max_qty`         SMALLINT UNSIGNED NOT NULL DEFAULT 999,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `sort_order`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`currency_code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. testimonials
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `content`         VARCHAR(1000) NOT NULL,
  `author_name`     VARCHAR(100)  DEFAULT NULL,
  `author_handle`   VARCHAR(100)  DEFAULT NULL,
  `author_platform` VARCHAR(50)   DEFAULT NULL,
  `author_avatar`   VARCHAR(500)  DEFAULT NULL,
  `rating`          TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5',
  `sort_order`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sort`   (`sort_order`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. faq_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `faq_items` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `page_scope`  VARCHAR(50)   NOT NULL DEFAULT 'general',
  `question`    VARCHAR(500)  NOT NULL,
  `answer`      TEXT          NOT NULL,
  `icon_name`   VARCHAR(80)   DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scope_sort` (`page_scope`, `sort_order`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. password_resets
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(255)  NOT NULL,
  `token_hash` VARCHAR(255)  NOT NULL COMMENT 'SHA-256 hash of the raw token',
  `expires_at` TIMESTAMP     NOT NULL,
  `used`       TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email`      (`email`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_expires`    (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. email_verifications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED  NOT NULL,
  `token_hash` VARCHAR(255)  NOT NULL,
  `expires_at` TIMESTAMP     NOT NULL,
  `used`       TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. contact_messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `source_page` ENUM('main_portfolio','leetcode','video_editor') NOT NULL DEFAULT 'main_portfolio',
  `user_id`     INT UNSIGNED   DEFAULT NULL,
  `name`        VARCHAR(200)   NOT NULL,
  `email`       VARCHAR(255)   NOT NULL,
  `subject`     VARCHAR(300)   DEFAULT NULL,
  `message`     TEXT           NOT NULL,
  `ip_address`  VARCHAR(45)    DEFAULT NULL,
  `user_agent`  TEXT           DEFAULT NULL,
  `is_read`     TINYINT(1)     NOT NULL DEFAULT 0,
  `is_spam`     TINYINT(1)     NOT NULL DEFAULT 0,
  `submitted_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_source`  (`source_page`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_is_spam` (`is_spam`),
  KEY `idx_date`    (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 14. page_visits  (analytics)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `page_visits` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `page_url`    VARCHAR(500)  NOT NULL,
  `page_title`  VARCHAR(300)  DEFAULT NULL,
  `user_id`     INT UNSIGNED  DEFAULT NULL,
  `ip_address`  VARCHAR(45)   DEFAULT NULL,
  `user_agent`  TEXT          DEFAULT NULL,
  `browser`     VARCHAR(100)  DEFAULT NULL,
  `referer`     VARCHAR(500)  DEFAULT NULL,
  `country`     VARCHAR(80)   DEFAULT NULL,
  `device_type` ENUM('desktop','tablet','mobile','bot','unknown') NOT NULL DEFAULT 'unknown',
  `visited_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_url`         (`page_url`(191)),
  KEY `idx_visited_at`       (`visited_at`),
  KEY `idx_user_id`          (`user_id`),
  KEY `idx_ip`               (`ip_address`),
  KEY `idx_device`           (`device_type`),
  KEY `idx_pv_device_time`   (`device_type`, `visited_at`),
  KEY `idx_pv_time_ip`       (`visited_at`, `ip_address`),
  KEY `idx_pv_browser`       (`browser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 15. file_uploads  (upload manager)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `file_uploads` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `uploaded_by`   INT UNSIGNED  DEFAULT NULL,
  `original_name` VARCHAR(255)  NOT NULL,
  `stored_name`   VARCHAR(255)  NOT NULL COMMENT 'Renamed UUID-based filename',
  `file_path`     VARCHAR(500)  NOT NULL COMMENT 'Root-relative path',
  `file_type`     VARCHAR(100)  NOT NULL COMMENT 'MIME type',
  `file_size`     INT UNSIGNED  NOT NULL COMMENT 'Bytes',
  `file_category` ENUM('image','video','pdf','zip','other') NOT NULL DEFAULT 'other',
  `context`       VARCHAR(80)   DEFAULT NULL COMMENT 'project/blog/course/avatar/general',
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `uploaded_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uploaded_by`  (`uploaded_by`),
  KEY `idx_category`     (`file_category`),
  KEY `idx_context`      (`context`),
  KEY `idx_active`       (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PASS 2 — Depend on categories
-- ============================================================

-- ------------------------------------------------------------
-- 16. blog_articles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_articles` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `category_id`      INT UNSIGNED  DEFAULT NULL,
  `author_id`        INT UNSIGNED  DEFAULT NULL,
  `slug`             VARCHAR(250)  NOT NULL,
  `title`            VARCHAR(300)  NOT NULL,
  `card_title`       VARCHAR(100)  DEFAULT NULL,
  `excerpt`          TEXT          NOT NULL,
  `content`          LONGTEXT      NOT NULL,
  `thumbnail_path`   VARCHAR(500)  DEFAULT NULL,
  `cover_image_path` VARCHAR(500)  DEFAULT NULL,
  `icon_name`        VARCHAR(80)   DEFAULT NULL,
  `meta_description` VARCHAR(160)  DEFAULT NULL,
  `meta_keywords`    VARCHAR(300)  DEFAULT NULL,
  `og_image_url`     VARCHAR(500)  DEFAULT NULL,
  `read_time_mins`   TINYINT UNSIGNED DEFAULT NULL,
  `view_count`       INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_featured`      TINYINT(1)    NOT NULL DEFAULT 0,
  `is_published`     TINYINT(1)    NOT NULL DEFAULT 0,
  `published_at`     TIMESTAMP     NULL DEFAULT NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_category`    (`category_id`),
  KEY `idx_author`      (`author_id`),
  KEY `idx_published`   (`is_published`, `published_at`),
  KEY `idx_featured`    (`is_featured`),
  FULLTEXT KEY `ft_search` (`title`, `excerpt`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 17. courses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `category_id`      INT UNSIGNED    DEFAULT NULL,
  `instructor_id`    INT UNSIGNED    DEFAULT NULL,
  `title`            VARCHAR(300)    NOT NULL,
  `slug`             VARCHAR(250)    NOT NULL,
  `short_description` VARCHAR(500)   NOT NULL,
  `description`      LONGTEXT        DEFAULT NULL,
  `thumbnail_path`   VARCHAR(500)    DEFAULT NULL,
  `preview_video`    VARCHAR(500)    DEFAULT NULL,
  `price`            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `discount_price`   DECIMAL(10,2)   DEFAULT NULL,
  `currency`         VARCHAR(10)     NOT NULL DEFAULT 'INR',
  `level`            ENUM('beginner','intermediate','advanced','all') NOT NULL DEFAULT 'all',
  `language`         VARCHAR(50)     NOT NULL DEFAULT 'Hindi',
  `requirements`     TEXT            DEFAULT NULL,
  `what_you_learn`   TEXT            DEFAULT NULL,
  `duration_hours`   DECIMAL(5,1)    DEFAULT NULL,
  `total_lessons`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `enrollment_count` INT UNSIGNED    NOT NULL DEFAULT 0,
  `rating`           DECIMAL(3,2)    DEFAULT NULL,
  `is_free`          TINYINT(1)      NOT NULL DEFAULT 1,
  `is_featured`      TINYINT(1)      NOT NULL DEFAULT 0,
  `is_published`     TINYINT(1)      NOT NULL DEFAULT 0,
  `published_at`     TIMESTAMP       NULL DEFAULT NULL,
  `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`    (`slug`),
  KEY `idx_category`      (`category_id`),
  KEY `idx_instructor`    (`instructor_id`),
  KEY `idx_published`     (`is_published`, `published_at`),
  KEY `idx_featured`      (`is_featured`),
  KEY `idx_level`         (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 18. portfolio_items  (video editor portfolio)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portfolio_items` (
  `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `category_id`         INT UNSIGNED  DEFAULT NULL,
  `title`               VARCHAR(200)  NOT NULL,
  `emoji`               VARCHAR(10)   DEFAULT NULL,
  `short_description`   VARCHAR(500)  DEFAULT NULL,
  `thumb_path`          VARCHAR(500)  NOT NULL,
  `video_path`          VARCHAR(500)  DEFAULT NULL,
  `external_video_url`  VARCHAR(500)  DEFAULT NULL,
  `client_name`         VARCHAR(100)  DEFAULT NULL,
  `tools_used`          VARCHAR(300)  DEFAULT NULL,
  `is_featured`         TINYINT(1)    NOT NULL DEFAULT 0,
  `sort_order`          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`           TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category`    (`category_id`),
  KEY `idx_sort_active` (`is_active`, `sort_order`),
  KEY `idx_featured`    (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PASS 3 — Depend on courses
-- ============================================================

-- ------------------------------------------------------------
-- 19. course_chapters
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_chapters` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `course_id`   INT UNSIGNED  NOT NULL,
  `title`       VARCHAR(200)  NOT NULL,
  `description` TEXT          DEFAULT NULL,
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_sort`      (`sort_order`),
  CONSTRAINT `fk_chapter_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 20. course_enrollments
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_enrollments` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `course_id`        INT UNSIGNED  NOT NULL,
  `user_id`          INT UNSIGNED  NOT NULL,
  `enrolled_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at`     TIMESTAMP     NULL DEFAULT NULL,
  `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `payment_status`   ENUM('free','paid','refunded') NOT NULL DEFAULT 'free',
  `amount_paid`      DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`course_id`, `user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_user_id`   (`user_id`),
  CONSTRAINT `fk_enroll_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enroll_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PASS 4 — Depend on course_chapters
-- ============================================================

-- ------------------------------------------------------------
-- 21. course_lessons
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_lessons` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `chapter_id`       INT UNSIGNED  NOT NULL,
  `course_id`        INT UNSIGNED  NOT NULL COMMENT 'Denormalized for fast queries',
  `title`            VARCHAR(200)  NOT NULL,
  `description`      TEXT          DEFAULT NULL,
  `content_type`     ENUM('video','pdf','text','quiz','zip') NOT NULL DEFAULT 'video',
  `video_path`       VARCHAR(500)  DEFAULT NULL,
  `pdf_path`         VARCHAR(500)  DEFAULT NULL,
  `text_content`     LONGTEXT      DEFAULT NULL,
  `duration_seconds` INT UNSIGNED  DEFAULT NULL,
  `sort_order`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_preview`       TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Free preview without enrollment',
  `is_active`        TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chapter_id` (`chapter_id`),
  KEY `idx_course_id`  (`course_id`),
  KEY `idx_sort`       (`sort_order`),
  CONSTRAINT `fk_lesson_chapter` FOREIGN KEY (`chapter_id`) REFERENCES `course_chapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lesson_course`  FOREIGN KEY (`course_id`)  REFERENCES `courses`         (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PASS 5 — LeetCode hierarchy
-- ============================================================

-- ------------------------------------------------------------
-- 22. leetcode_years
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leetcode_years` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `year`             YEAR          NOT NULL,
  `badge_label`      VARCHAR(60)   NOT NULL,
  `badge_color`      VARCHAR(7)    DEFAULT '#ffc400',
  `card_title`       VARCHAR(100)  NOT NULL,
  `description`      TEXT          NOT NULL,
  `status`           ENUM('active','upcoming','future','archived') NOT NULL DEFAULT 'upcoming',
  `explore_url`      VARCHAR(200)  DEFAULT NULL,
  `total_solutions`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_visible`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_year` (`year`),
  KEY `idx_status`    (`status`),
  KEY `idx_visible`   (`is_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 23. leetcode_months
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leetcode_months` (
  `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `year_id`             INT UNSIGNED  NOT NULL,
  `year`                YEAR          NOT NULL,
  `month_num`           TINYINT UNSIGNED NOT NULL,
  `month_name`          VARCHAR(30)   NOT NULL,
  `month_short`         VARCHAR(10)   NOT NULL,
  `total_days`          TINYINT UNSIGNED NOT NULL,
  `published_solutions` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_visible`          TINYINT(1)    NOT NULL DEFAULT 1,
  `is_locked`           TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_year_month` (`year_id`, `month_num`),
  KEY `idx_year_id`   (`year_id`),
  KEY `idx_year_mon`  (`year`, `month_num`),
  KEY `idx_visible`   (`is_visible`),
  CONSTRAINT `fk_month_year` FOREIGN KEY (`year_id`) REFERENCES `leetcode_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 24. leetcode_solutions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leetcode_solutions` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `month_id`          INT UNSIGNED  NOT NULL,
  `solution_date`     DATE          NOT NULL,
  `day_number`        TINYINT UNSIGNED NOT NULL,
  `year`              YEAR          NOT NULL,
  `month`             TINYINT UNSIGNED NOT NULL,
  `slug`              VARCHAR(250)  NOT NULL,
  `problem_title`     VARCHAR(300)  NOT NULL,
  `problem_number`    SMALLINT UNSIGNED DEFAULT NULL,
  `leetcode_url`      VARCHAR(500)  DEFAULT NULL,
  `difficulty`        ENUM('Easy','Medium','Hard') NOT NULL,
  `time_complexity`   VARCHAR(50)   DEFAULT NULL,
  `space_complexity`  VARCHAR(50)   DEFAULT NULL,
  `explanation`       LONGTEXT      DEFAULT NULL,
  `approach_summary`  TEXT          DEFAULT NULL,
  `youtube_url`       VARCHAR(500)  DEFAULT NULL,
  `youtube_thumbnail` VARCHAR(500)  DEFAULT NULL,
  `meta_description`  VARCHAR(160)  DEFAULT NULL,
  `og_image_url`      VARCHAR(500)  DEFAULT NULL,
  `is_featured`       TINYINT(1)    NOT NULL DEFAULT 0,
  `is_published`      TINYINT(1)    NOT NULL DEFAULT 0,
  `published_at`      TIMESTAMP     NULL DEFAULT NULL,
  `view_count`        INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_date`         (`solution_date`),
  UNIQUE KEY `uq_slug`         (`slug`),
  KEY `idx_month_id`           (`month_id`),
  KEY `idx_month_published`    (`month_id`, `is_published`),
  KEY `idx_difficulty`         (`difficulty`),
  KEY `idx_featured`           (`is_featured`),
  KEY `idx_problem_num`        (`problem_number`),
  KEY `idx_year_month`         (`year`, `month`),
  FULLTEXT KEY `ft_search`     (`problem_title`, `explanation`),
  CONSTRAINT `fk_solution_month` FOREIGN KEY (`month_id`) REFERENCES `leetcode_months` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PASS 6 — Depend on leetcode_solutions
-- ============================================================

-- ------------------------------------------------------------
-- 25. solution_code_blocks
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `solution_code_blocks` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `solution_id`   INT UNSIGNED  NOT NULL,
  `language`      VARCHAR(50)   NOT NULL,
  `language_slug` VARCHAR(30)   NOT NULL,
  `code`          LONGTEXT      NOT NULL,
  `is_primary`    TINYINT(1)    NOT NULL DEFAULT 0,
  `sort_order`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_solution_lang` (`solution_id`, `language`),
  KEY `idx_solution_sort`       (`solution_id`, `sort_order`),
  CONSTRAINT `fk_code_solution` FOREIGN KEY (`solution_id`) REFERENCES `leetcode_solutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 26. solution_tag_map
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `solution_tag_map` (
  `solution_id`  INT UNSIGNED  NOT NULL,
  `tag_id`       INT UNSIGNED  NOT NULL,
  `assigned_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`solution_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`),
  CONSTRAINT `fk_tagmap_solution` FOREIGN KEY (`solution_id`) REFERENCES `leetcode_solutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tagmap_tag`      FOREIGN KEY (`tag_id`)      REFERENCES `solution_tags`      (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INITIAL SEED DATA
-- ============================================================

-- Default admin account (password: Admin@123! — CHANGE IMMEDIATELY)
INSERT IGNORE INTO `users`
  (`full_name`, `username`, `email`, `password_hash`, `role`, `status`, `email_verified`)
VALUES
  ('Tushpendra Kumar', 'tushpendra', 'admin@codebytushu.com',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt of 'Admin@123!'
   'super_admin', 'active', 1);

-- Solution tags seed
INSERT IGNORE INTO `solution_tags` (`name`, `slug`, `color_hex`) VALUES
  ('Array',               'array',                '#ffc400'),
  ('Hash Map',            'hash-map',             '#ffc400'),
  ('Two Pointers',        'two-pointers',         '#ffc400'),
  ('Sliding Window',      'sliding-window',       '#ffc400'),
  ('Binary Search',       'binary-search',        '#ffc400'),
  ('Dynamic Programming', 'dynamic-programming',  '#ffc400'),
  ('Recursion',           'recursion',            '#ffc400'),
  ('Backtracking',        'backtracking',         '#ffc400'),
  ('Graph',               'graph',                '#ffc400'),
  ('BFS',                 'bfs',                  '#ffc400'),
  ('DFS',                 'dfs',                  '#ffc400'),
  ('Tree',                'tree',                 '#ffc400'),
  ('Binary Tree',         'binary-tree',          '#ffc400'),
  ('Stack',               'stack',                '#ffc400'),
  ('Queue',               'queue',                '#ffc400'),
  ('Heap / Priority Queue','heap',                '#ffc400'),
  ('Linked List',         'linked-list',          '#ffc400'),
  ('String',              'string',               '#ffc400'),
  ('Sorting',             'sorting',              '#ffc400'),
  ('Greedy',              'greedy',               '#ffc400'),
  ('Math',                'math',                 '#ffc400'),
  ('Bit Manipulation',    'bit-manipulation',     '#ffc400'),
  ('Matrix',              'matrix',               '#ffc400'),
  ('Monotonic Stack',     'monotonic-stack',      '#ffc400'),
  ('Union Find',          'union-find',           '#ffc400'),
  ('Trie',                'trie',                 '#ffc400'),
  ('Prefix Sum',          'prefix-sum',           '#ffc400');

-- Donate config seed
INSERT IGNORE INTO `donate_config`
  (`currency_code`, `currency_name`, `currency_symbol`, `exchange_rate`, `base_amount`, `sort_order`)
VALUES
  ('INR', 'Indian Rupee',     '₹',    1.000000,  100.00, 1),
  ('USD', 'US Dollar',        '$',    0.012000,    1.20, 2),
  ('GBP', 'British Pound',    '£',    0.009500,    0.95, 3),
  ('EUR', 'Euro',             '€',    0.011000,    1.10, 4),
  ('CAD', 'Canadian Dollar',  'CA$',  0.016200,    1.62, 5),
  ('AUD', 'Australian Dollar','A$',   0.018500,    1.85, 6);

-- Default site config
INSERT IGNORE INTO `site_config` (`config_key`, `config_value`, `value_type`, `label`, `config_group`) VALUES
  ('site_name',            'CodeByTushu',                    'text',   'Website Name',              'general'),
  ('site_tagline',         'Learn. Code. Grow.',             'text',   'Site Tagline',              'general'),
  ('contact_email',        'codebytushu@gmail.com',          'text',   'Contact Email',             'general'),
  ('contact_phone',        '+91-7055072196',                 'text',   'Contact Phone',             'general'),
  ('contact_address',      'Muzaffarnagar, India',           'text',   'Address',                   'general'),
  ('footer_year',          '2026',                           'number', 'Footer Year',               'general'),
  ('maintenance_mode',     '0',                              'bool',   'Maintenance Mode',          'general'),
  ('hero_typing_strings',  '["Learn Coding","Watch Tutorials","Explore Projects","Code for Free"]',
                                                             'json',   'Hero Typing Strings',       'main_portfolio'),
  ('boba_base_price_inr',  '100',                            'number', 'Boba Base Price (INR)',     'donate'),
  ('google_maps_embed',    '',                               'url',    'Google Maps Embed URL',     'general'),
  ('seo_default_title',    'CodeByTushu — Learn. Code. Grow.','text', 'Default SEO Title',         'seo'),
  ('seo_default_description','Daily DSA solutions, web dev tutorials, and projects by CodeByTushu.',
                                                             'text',   'Default Meta Description',  'seo');

-- Social links seed
INSERT IGNORE INTO `social_links`
  (`platform`, `display_name`, `url`, `brand_color_hex`, `sort_order`)
VALUES
  ('youtube',   'YouTube',   'https://youtube.com/@codebytushu',              '#FF0000', 1),
  ('instagram', 'Instagram', 'https://www.instagram.com/codebytushu',         '#E4405F', 2),
  ('linkedin',  'LinkedIn',  'https://www.linkedin.com/company/codebytushu/', '#0A66C2', 3),
  ('whatsapp',  'WhatsApp',  'https://wa.me/917055072196',                    '#25D366', 4);

-- LeetCode 2026 year seed
INSERT IGNORE INTO `leetcode_years`
  (`year`, `badge_label`, `card_title`, `description`, `status`, `is_visible`)
VALUES
  (2026, 'ACTIVE YEAR', '2026 Daily Solutions',
   'Daily LeetCode problem solutions for 2026. Java, Python, C++, JavaScript.',
   'active', 1),
  (2027, 'UPCOMING', '2027 Daily Solutions',
   '2027 daily solutions — coming soon.',
   'upcoming', 1),
  (2028, 'FUTURE', '2028 Daily Solutions',
   '2028 — planned.',
   'future', 0);

-- Blog categories
INSERT IGNORE INTO `categories` (`name`, `slug`, `type`, `sort_order`) VALUES
  ('Video Editing',    'video-editing',    'blog', 1),
  ('Web Development',  'web-development',  'blog', 2),
  ('DSA',              'dsa',              'blog', 3),
  ('Career',           'career',           'blog', 4);

-- Portfolio categories
INSERT IGNORE INTO `categories` (`name`, `slug`, `type`, `sort_order`) VALUES
  ('Cinematic',    'cinematic',    'portfolio', 1),
  ('Documentary',  'documentary',  'portfolio', 2),
  ('Reels',        'reels',        'portfolio', 3),
  ('Brand',        'brand',        'portfolio', 4);

-- Course categories
INSERT IGNORE INTO `categories` (`name`, `slug`, `type`, `sort_order`) VALUES
  ('DSA & Algorithms',  'dsa-algorithms',  'course', 1),
  ('Web Development',   'web-dev',         'course', 2),
  ('Video Editing',     'video-editing',   'course', 3);
