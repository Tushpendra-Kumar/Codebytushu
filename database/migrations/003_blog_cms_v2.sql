-- ============================================================
-- Migration: Blog CMS v2 additions
-- Adds seo_title to blog_articles, creates blog_tags and
-- blog_tag_map tables for tagging support.
-- Safe to run multiple times (IF NOT EXISTS / ADD IF NOT EXISTS).
-- ============================================================

-- 1. Add missing columns to blog_articles
ALTER TABLE `blog_articles`
  ADD COLUMN IF NOT EXISTS `seo_title` VARCHAR(120) DEFAULT NULL COMMENT 'Custom SEO <title> tag' AFTER `og_image_url`;

-- 2. Blog tags master table
CREATE TABLE IF NOT EXISTS `blog_tags` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80)   NOT NULL,
  `slug`        VARCHAR(100)  NOT NULL,
  `color_hex`   VARCHAR(7)    DEFAULT NULL,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `usage_count` INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Blog tag mapping table
CREATE TABLE IF NOT EXISTS `blog_tag_map` (
  `article_id`  INT UNSIGNED NOT NULL,
  `tag_id`      INT UNSIGNED NOT NULL,
  PRIMARY KEY (`article_id`, `tag_id`),
  CONSTRAINT `fk_btm_article` FOREIGN KEY (`article_id`) REFERENCES `blog_articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_btm_tag`     FOREIGN KEY (`tag_id`)     REFERENCES `blog_tags`     (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Seed some sample tags
INSERT IGNORE INTO `blog_tags` (`name`, `slug`, `color_hex`) VALUES
  ('PHP',           'php',            '#7a86b8'),
  ('MySQL',         'mysql',          '#00758f'),
  ('JavaScript',    'javascript',     '#f7df1e'),
  ('Tutorial',      'tutorial',       '#22c55e'),
  ('DSA',           'dsa',            '#f59e0b'),
  ('Career',        'career',         '#8b5cf6'),
  ('Web Dev',       'web-dev',        '#3b82f6'),
  ('Video Editing', 'video-editing',  '#ef4444'),
  ('Tips',          'tips',           '#14b8a6'),
  ('Projects',      'projects',       '#f97316');

-- Confirm
SELECT 'blog_tags created' AS status;
SELECT 'blog_tag_map created' AS status;
