-- ============================================================
-- CodeByTushu — LeetCode Months Data Patch
-- Run this in Hostinger phpMyAdmin → SQL tab
-- Safe to run multiple times (INSERT IGNORE won't duplicate)
-- ============================================================

-- 2026 months
INSERT IGNORE INTO `leetcode_months`
  (`year_id`, `year`, `month_num`, `month_name`, `month_short`, `total_days`, `is_visible`)
SELECT y.id, 2026, m.month_num, m.month_name, m.month_short, m.total_days, 1
FROM `leetcode_years` y
JOIN (
  SELECT 1 AS month_num, 'January'   AS month_name, 'Jan' AS month_short, 31 AS total_days UNION ALL
  SELECT 2,  'February',  'Feb', 28 UNION ALL
  SELECT 3,  'March',     'Mar', 31 UNION ALL
  SELECT 4,  'April',     'Apr', 30 UNION ALL
  SELECT 5,  'May',       'May', 31 UNION ALL
  SELECT 6,  'June',      'Jun', 30 UNION ALL
  SELECT 7,  'July',      'Jul', 31 UNION ALL
  SELECT 8,  'August',    'Aug', 31 UNION ALL
  SELECT 9,  'September', 'Sep', 30 UNION ALL
  SELECT 10, 'October',   'Oct', 31 UNION ALL
  SELECT 11, 'November',  'Nov', 30 UNION ALL
  SELECT 12, 'December',  'Dec', 31
) m ON 1=1
WHERE y.year = 2026;

-- 2027 months
INSERT IGNORE INTO `leetcode_months`
  (`year_id`, `year`, `month_num`, `month_name`, `month_short`, `total_days`, `is_visible`)
SELECT y.id, 2027, m.month_num, m.month_name, m.month_short, m.total_days, 1
FROM `leetcode_years` y
JOIN (
  SELECT 1 AS month_num, 'January'   AS month_name, 'Jan' AS month_short, 31 AS total_days UNION ALL
  SELECT 2,  'February',  'Feb', 28 UNION ALL
  SELECT 3,  'March',     'Mar', 31 UNION ALL
  SELECT 4,  'April',     'Apr', 30 UNION ALL
  SELECT 5,  'May',       'May', 31 UNION ALL
  SELECT 6,  'June',      'Jun', 30 UNION ALL
  SELECT 7,  'July',      'Jul', 31 UNION ALL
  SELECT 8,  'August',    'Aug', 31 UNION ALL
  SELECT 9,  'September', 'Sep', 30 UNION ALL
  SELECT 10, 'October',   'Oct', 31 UNION ALL
  SELECT 11, 'November',  'Nov', 30 UNION ALL
  SELECT 12, 'December',  'Dec', 31
) m ON 1=1
WHERE y.year = 2027;

-- 2028 months (leap year)
INSERT IGNORE INTO `leetcode_months`
  (`year_id`, `year`, `month_num`, `month_name`, `month_short`, `total_days`, `is_visible`)
SELECT y.id, 2028, m.month_num, m.month_name, m.month_short, m.total_days, 1
FROM `leetcode_years` y
JOIN (
  SELECT 1 AS month_num, 'January'   AS month_name, 'Jan' AS month_short, 31 AS total_days UNION ALL
  SELECT 2,  'February',  'Feb', 29 UNION ALL
  SELECT 3,  'March',     'Mar', 31 UNION ALL
  SELECT 4,  'April',     'Apr', 30 UNION ALL
  SELECT 5,  'May',       'May', 31 UNION ALL
  SELECT 6,  'June',      'Jun', 30 UNION ALL
  SELECT 7,  'July',      'Jul', 31 UNION ALL
  SELECT 8,  'August',    'Aug', 31 UNION ALL
  SELECT 9,  'September', 'Sep', 30 UNION ALL
  SELECT 10, 'October',   'Oct', 31 UNION ALL
  SELECT 11, 'November',  'Nov', 30 UNION ALL
  SELECT 12, 'December',  'Dec', 31
) m ON 1=1
WHERE y.year = 2028;

-- Ensure 2028 shows on the year selector
UPDATE `leetcode_years` SET `is_visible` = 1, `badge_label` = 'FUTURE ARCHIVE' WHERE `year` = 2028;

-- Verify row counts
SELECT y.year, COUNT(m.id) AS month_count
FROM `leetcode_years` y
LEFT JOIN `leetcode_months` m ON m.year_id = y.id
GROUP BY y.year ORDER BY y.year;
