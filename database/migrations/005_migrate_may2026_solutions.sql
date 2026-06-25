-- ============================================================
-- Migration 005 — Migrate May 2026 LeetCode Content to DB
-- Seeds the only real solution content from the static HTML
-- files (2026-05-01-Next.html: Problem #396 Rotate Function).
--
-- Uses the ACTUAL live database schema (verified by DESCRIBE).
-- Safe to run multiple times (uses INSERT IGNORE).
-- ============================================================

USE `codebytushu_db`;

-- ── 1. Ensure 2026 year record exists ────────────────────────
-- Actual columns: id, year, badge_label, badge_color, card_title,
--                 description, status, explore_url, total_solutions,
--                 is_visible
INSERT IGNORE INTO `leetcode_years`
  (`year`, `badge_label`, `badge_color`, `card_title`, `description`, `status`, `is_visible`)
VALUES
  (2026, '2026', '#f5a623', 'LeetCode Daily Solutions 2026',
   'Daily LeetCode problem solutions for the year 2026, with explanations in Java, Python, C++ and JavaScript.',
   'active', 1);

-- ── 2. Ensure May 2026 month record exists ───────────────────
-- Actual columns: id, year_id, year, month_num, month_name,
--                 month_short, total_days, published_solutions,
--                 is_visible, is_locked
INSERT IGNORE INTO `leetcode_months`
  (`year_id`, `year`, `month_num`, `month_name`, `month_short`, `total_days`, `is_visible`, `is_locked`)
VALUES
  (
    (SELECT id FROM `leetcode_years` WHERE `year` = 2026 LIMIT 1),
    2026,
    5,
    'May',
    'May',
    31,
    1,
    0
  );

-- ── 3. Insert Problem #396 — Rotate Function (May 1, 2026) ──
INSERT IGNORE INTO `leetcode_solutions`
  (
    `month_id`,
    `solution_date`,
    `day_number`,
    `year`,
    `month`,
    `platform`,
    `slug`,
    `problem_title`,
    `problem_number`,
    `leetcode_url`,
    `difficulty`,
    `time_complexity`,
    `space_complexity`,
    `explanation`,
    `approach_summary`,
    `youtube_url`,
    `meta_description`,
    `is_featured`,
    `is_published`,
    `published_at`
  )
VALUES
  (
    (SELECT id FROM `leetcode_months`
     WHERE year_id = (SELECT id FROM `leetcode_years` WHERE `year` = 2026 LIMIT 1)
       AND month_num = 5 LIMIT 1),
    '2026-05-01',
    1,
    2026,
    5,
    'LeetCode',
    '396-rotate-function',
    'Rotate Function O(n) Optimization Explained',
    396,
    'https://leetcode.com/problems/rotate-function/',
    'Medium',
    'O(n)',
    'O(1)',
    'Given an array nums of length n, the Rotate Function F(k) is defined as:\n\nF(k) = 0 * B_k[0] + 1 * B_k[1] + 2 * B_k[2] + ... + (n-1) * B_k[n-1]\n\nwhere B_k is nums rotated k positions.\n\nInstead of re-computing F(k) from scratch each time (O(n^2)), we use the relationship:\n\nF(k) = F(k-1) + sum(nums) - n * nums[n-k]\n\nThis allows us to compute all rotations in O(n) time with O(1) space by building on the previous result.',
    'Use the O(n) rotation formula: F(k) = F(k-1) + totalSum - n * nums[n-k]. Compute F(0) and totalSum once, then iterate to find the maximum.',
    'https://www.youtube.com/embed/NMoBBr39jtI',
    'LeetCode 396 Rotate Function O(n) solution. Java and JavaScript implementations with time complexity O(n) and space complexity O(1).',
    0,
    1,
    '2026-05-01 00:00:00'
  );

-- ── 4. Insert Code Blocks for Problem 396 ────────────────────

-- Java solution (primary)
INSERT IGNORE INTO `solution_code_blocks`
  (`solution_id`, `language`, `language_slug`, `code`, `is_primary`, `sort_order`)
VALUES
  (
    (SELECT id FROM `leetcode_solutions` WHERE slug = '396-rotate-function' LIMIT 1),
    'Java',
    'java',
    'class Solution {
    public int maxRotateFunction(int[] nums) {
        int n = nums.length;

        long sum = 0;   // total sum of array
        long f = 0;     // F(0)

        // Step 1: calculate total sum and F(0)
        for (int i = 0; i < n; i++) {
            sum += nums[i];
            f += (long) i * nums[i];
        }

        long max = f;

        // Step 2: use formula to calculate next rotations
        for (int k = 1; k < n; k++) {
            f = f + sum - (long) n * nums[n - k];
            max = Math.max(max, f);
        }

        return (int) max;
    }
}',
    1,
    0
  );

-- JavaScript solution
INSERT IGNORE INTO `solution_code_blocks`
  (`solution_id`, `language`, `language_slug`, `code`, `is_primary`, `sort_order`)
VALUES
  (
    (SELECT id FROM `leetcode_solutions` WHERE slug = '396-rotate-function' LIMIT 1),
    'JavaScript',
    'javascript',
    'var maxRotateFunction = function(nums) {
    let n = nums.length;
    let totalSum = 0;
    let F = 0;

    // Step 1: total sum & F(0)
    for (let i = 0; i < n; i++) {
        totalSum += nums[i];
        F += i * nums[i];
    }

    let maxVal = F;

    // Step 2: use formula
    for (let k = 1; k < n; k++) {
        F = F + totalSum - n * nums[n - k];
        maxVal = Math.max(maxVal, F);
    }

    return maxVal;
};',
    0,
    1
  );

-- ── 5. Update month published_solutions count ─────────────────
UPDATE `leetcode_months` m
SET published_solutions = (
    SELECT COUNT(*) FROM `leetcode_solutions`
    WHERE month_id = m.id AND is_published = 1
)
WHERE year_id = (SELECT id FROM `leetcode_years` WHERE year = 2026 LIMIT 1)
  AND month_num = 5;

-- ── 6. Update year total_solutions count ─────────────────────
UPDATE `leetcode_years`
SET total_solutions = (
    SELECT COUNT(*) FROM `leetcode_solutions` s
    JOIN `leetcode_months` m ON m.id = s.month_id
    WHERE m.year_id = (SELECT id FROM `leetcode_years` WHERE year = 2026 LIMIT 1)
      AND s.is_published = 1
)
WHERE year = 2026;

-- ── 7. Verification ───────────────────────────────────────────
SELECT
  s.id,
  s.slug,
  s.problem_number,
  s.problem_title,
  s.solution_date,
  s.difficulty,
  s.time_complexity,
  s.space_complexity,
  s.is_published,
  COUNT(cb.id) AS code_block_count
FROM `leetcode_solutions` s
LEFT JOIN `solution_code_blocks` cb ON cb.solution_id = s.id
WHERE s.slug = '396-rotate-function'
GROUP BY s.id;
