-- ============================================================
-- Migration: Courses CMS v2
-- Adds meta_description to courses table.
-- All core tables (courses, course_chapters, course_lessons)
-- already exist in the schema. This migration adds any columns
-- that may be missing.
-- ============================================================

-- 1. Add meta_description if missing
ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `meta_description` VARCHAR(160) DEFAULT NULL COMMENT 'SEO meta description' AFTER `is_published`;

-- 2. Ensure upload directories exist (document only — create via PHP/server)
-- /uploads/courses/thumbs/   — Course thumbnails
-- /uploads/courses/previews/ — Preview videos
-- /uploads/courses/videos/   — Lesson videos
-- /uploads/courses/pdfs/     — Lesson PDFs

-- 3. Verify structure
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('courses', 'course_chapters', 'course_lessons')
ORDER BY TABLE_NAME, ORDINAL_POSITION;
