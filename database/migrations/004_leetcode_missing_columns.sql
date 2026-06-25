-- ============================================================
-- Migration 004 — LeetCode Missing Columns
-- Adds columns that the Admin CMS and solution.php reference
-- but that were absent from schema.sql v2.0.
-- Safe to run multiple times (uses IF NOT EXISTS / IGNORE).
-- ============================================================

USE `codebytushu_db`;

-- ── leetcode_solutions: platform column ──────────────────────
-- Referenced by admin/leetcode.php filter and api/admin/leetcode.php
ALTER TABLE `leetcode_solutions`
  ADD COLUMN IF NOT EXISTS `platform`
    VARCHAR(50) NOT NULL DEFAULT 'LeetCode'
    COMMENT 'Source platform: LeetCode, GeeksForGeeks, HackerRank, etc.'
    AFTER `slug`;

-- ── leetcode_solutions: thumbnail_path column ────────────────
-- Referenced in admin/leetcode.php list view thumbnail display
ALTER TABLE `leetcode_solutions`
  ADD COLUMN IF NOT EXISTS `thumbnail_path`
    VARCHAR(500) DEFAULT NULL
    COMMENT 'Uploaded thumbnail image path (relative to /uploads/)'
    AFTER `youtube_thumbnail`;

-- ── leetcode_solutions: pdf_path column ──────────────────────
-- Referenced in api/admin/leetcode.php delete action (cleanup)
ALTER TABLE `leetcode_solutions`
  ADD COLUMN IF NOT EXISTS `pdf_path`
    VARCHAR(500) DEFAULT NULL
    COMMENT 'Optional attached PDF solution path'
    AFTER `thumbnail_path`;

-- ── users: phone_number column ───────────────────────────────
-- user/profile.php runs ALTER TABLE on every load — move it here
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `phone_number`
    VARCHAR(20) DEFAULT NULL
    COMMENT 'Optional user phone number'
    AFTER `email`;

-- ── page_visits: session_id column ───────────────────────────
-- includes/analytics.php inserts session_id but column was missing
ALTER TABLE `page_visits`
  ADD COLUMN IF NOT EXISTS `session_id`
    VARCHAR(128) DEFAULT NULL
    COMMENT 'PHP session_id() at time of visit'
    AFTER `id`;

-- Add index for session lookups
ALTER TABLE `page_visits`
  ADD INDEX IF NOT EXISTS `idx_session_id` (`session_id`);

-- ── Verify additions ─────────────────────────────────────────
SELECT
  COLUMN_NAME,
  COLUMN_TYPE,
  COLUMN_DEFAULT,
  IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'leetcode_solutions'
  AND COLUMN_NAME IN ('platform', 'thumbnail_path', 'pdf_path')
ORDER BY ORDINAL_POSITION;
