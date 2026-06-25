-- ==============================================================================
-- CodeByTushu Migration: 008_analytics_performance.sql
-- Description: Adds missing performance indexes and `browser` column.
-- Safety: 100% Idempotent. Safe to run multiple times. Does NOT modify data.
-- Rollback: See documentation report for rollback statements.
-- Compatibility: MySQL 5.7+, MySQL 8.0+, MariaDB 10.2+
-- ==============================================================================

DELIMITER //

-- Procedure to safely add an index only if it does not already exist
DROP PROCEDURE IF EXISTS AddIndexIfNotExists //
CREATE PROCEDURE AddIndexIfNotExists(
    IN tableName VARCHAR(128),
    IN indexName VARCHAR(128),
    IN indexColumns VARCHAR(255)
)
BEGIN
    DECLARE indexCount INT;
    SELECT COUNT(1) INTO indexCount
    FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name = tableName
      AND index_name = indexName;

    IF indexCount = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD INDEX `', indexName, '` (', indexColumns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

-- Procedure to safely add a column only if it does not already exist
DROP PROCEDURE IF EXISTS AddColumnIfNotExists //
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(128),
    IN columnName VARCHAR(128),
    IN columnDef VARCHAR(255)
)
BEGIN
    DECLARE columnCount INT;
    SELECT COUNT(1) INTO columnCount
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
      AND table_name = tableName
      AND column_name = columnName;

    IF columnCount = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDef);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

-- ==============================================================================
-- 1. ADD NEW COLUMN
-- ==============================================================================

-- Adds `browser` column to page_visits to offload user-agent parsing from PHP (ARCH-09)
CALL AddColumnIfNotExists('page_visits', 'browser', 'VARCHAR(100) DEFAULT NULL AFTER user_agent');

-- ==============================================================================
-- 2. ADD MISSING PERFORMANCE INDEXES
-- ==============================================================================

-- PERF-01: Composite index for daily/monthly total visitor queries
-- Targets queries like: `WHERE visited_at >= ? AND device_type != 'bot'`
CALL AddIndexIfNotExists('page_visits', 'idx_pv_device_time', 'device_type, visited_at');

-- PERF-02: Composite index for unique IP visitor queries
-- Targets queries like: `SELECT COUNT(DISTINCT ip_address) ... WHERE visited_at >= ?`
CALL AddIndexIfNotExists('page_visits', 'idx_pv_time_ip', 'visited_at, ip_address');

-- PERF-03: Index for the new browser column
CALL AddIndexIfNotExists('page_visits', 'idx_pv_browser', 'browser');

-- PERF-04: Ensure users table has created_at index for the "New Users (7d)" queries
CALL AddIndexIfNotExists('users', 'idx_users_created_at', 'created_at');


-- ==============================================================================
-- 3. CLEANUP
-- ==============================================================================

DROP PROCEDURE IF EXISTS AddIndexIfNotExists;
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;
