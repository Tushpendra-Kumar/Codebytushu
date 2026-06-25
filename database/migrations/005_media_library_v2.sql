-- ============================================================
-- Migration: Media Library v2
-- Extends file_uploads.file_category ENUM to include 'code' and 'doc'.
-- Adds alt_text, title columns for better asset management.
-- ============================================================

-- 1. Extend file_category ENUM
ALTER TABLE `file_uploads`
  MODIFY COLUMN `file_category`
    ENUM('image','video','pdf','zip','code','doc','other')
    NOT NULL DEFAULT 'other'
    COMMENT 'Media type category';

-- 2. Add optional display fields
ALTER TABLE `file_uploads`
  ADD COLUMN IF NOT EXISTS `title`    VARCHAR(200) DEFAULT NULL COMMENT 'Optional display title' AFTER `original_name`,
  ADD COLUMN IF NOT EXISTS `alt_text` VARCHAR(300) DEFAULT NULL COMMENT 'Accessibility alt text for images' AFTER `title`;

-- 3. Add index on stored_name for deduplication lookups
ALTER TABLE `file_uploads`
  ADD INDEX IF NOT EXISTS `idx_stored_name` (`stored_name`);

-- 4. Confirm
SELECT COLUMN_NAME, COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'file_uploads'
ORDER BY ORDINAL_POSITION;
