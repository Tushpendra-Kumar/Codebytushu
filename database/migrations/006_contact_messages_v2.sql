-- ============================================================
-- Migration 006: Contact Messages v2
-- Adds is_starred column and indexes for the inbox module.
-- ============================================================

-- 1. Add is_starred column
ALTER TABLE `contact_messages`
  ADD COLUMN IF NOT EXISTS `is_starred` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Starred/flagged by admin' AFTER `is_spam`;

-- 2. Add index on is_starred for fast folder queries
ALTER TABLE `contact_messages`
  ADD INDEX IF NOT EXISTS `idx_is_starred` (`is_starred`);

-- 3. Add composite index for common inbox query (unread non-spam)
ALTER TABLE `contact_messages`
  ADD INDEX IF NOT EXISTS `idx_inbox` (`is_spam`, `is_read`, `submitted_at`);

-- 4. Confirm structure
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'contact_messages'
ORDER BY ORDINAL_POSITION;
