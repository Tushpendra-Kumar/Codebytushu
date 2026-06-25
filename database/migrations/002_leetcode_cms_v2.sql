-- ============================================================
-- Migration: Add new columns to leetcode_solutions
-- Required for LeetCode CMS v2
-- Run once against your MySQL database.
-- ============================================================

ALTER TABLE `leetcode_solutions`
  ADD COLUMN IF NOT EXISTS `platform`         VARCHAR(50)   NOT NULL DEFAULT 'LeetCode'  COMMENT 'Source platform' AFTER `slug`,
  ADD COLUMN IF NOT EXISTS `description`      LONGTEXT      DEFAULT NULL                  COMMENT 'Full problem statement' AFTER `approach_summary`,
  ADD COLUMN IF NOT EXISTS `notes`            TEXT          DEFAULT NULL                  COMMENT 'Internal admin notes' AFTER `description`,
  ADD COLUMN IF NOT EXISTS `seo_title`        VARCHAR(120)  DEFAULT NULL                  COMMENT 'Custom SEO <title>' AFTER `meta_description`,
  ADD COLUMN IF NOT EXISTS `thumbnail_path`   VARCHAR(500)  DEFAULT NULL                  COMMENT 'Relative path /uploads/leetcode/thumbs/...' AFTER `og_image_url`,
  ADD COLUMN IF NOT EXISTS `pdf_path`         VARCHAR(500)  DEFAULT NULL                  COMMENT 'Relative path /uploads/leetcode/pdfs/...' AFTER `thumbnail_path`;

-- Index on platform for filtering
ALTER TABLE `leetcode_solutions`
  ADD INDEX IF NOT EXISTS `idx_platform` (`platform`);

-- Confirm
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'leetcode_solutions'
ORDER BY ORDINAL_POSITION;
