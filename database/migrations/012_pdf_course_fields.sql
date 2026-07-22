-- ============================================================
-- Migration 012: PDF Course System Fields
-- Adds secure PDF path, content_type, SEO, USD price,
-- and import tracking to the courses table.
-- ============================================================

-- 1. Secure download file path (stored inside private/courses/)
ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `download_file_path` VARCHAR(500) DEFAULT NULL
  COMMENT 'Relative path inside private/courses/ — never exposed publicly'
  AFTER `preview_video`;

-- 2. Content type (pdf or video) — future-proof
ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `content_type` ENUM('pdf','video','mixed') NOT NULL DEFAULT 'pdf'
  COMMENT 'Primary content delivery type'
  AFTER `download_file_path`;

-- 3. USD price for international buyers
ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `price_usd` DECIMAL(10,2) DEFAULT NULL
  COMMENT 'Price in USD for international buyers'
  AFTER `discount_price`;

-- 4. SEO fields
ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `seo_title` VARCHAR(70) DEFAULT NULL
  COMMENT 'SEO page title (max 60-70 chars)'
  AFTER `meta_description`;

ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `seo_description` VARCHAR(165) DEFAULT NULL
  COMMENT 'SEO meta description (max 155-165 chars)'
  AFTER `seo_title`;

ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `seo_keywords` VARCHAR(500) DEFAULT NULL
  COMMENT 'Comma-separated SEO keywords'
  AFTER `seo_description`;

-- 5. Import tracking — marks auto-imported courses
ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `import_source` VARCHAR(200) DEFAULT NULL
  COMMENT 'Original PDF filename if auto-imported'
  AFTER `seo_keywords`;

-- 6. What you learn as JSON array (cleaner than plain text)
ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `what_you_learn_json` JSON DEFAULT NULL
  COMMENT 'Structured learning outcomes as JSON array'
  AFTER `what_you_learn`;

-- 7. Requirements as JSON array
ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `requirements_json` JSON DEFAULT NULL
  COMMENT 'Structured requirements as JSON array'
  AFTER `requirements`;

-- 8. Verify the final columns
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'courses'
ORDER BY ORDINAL_POSITION;
