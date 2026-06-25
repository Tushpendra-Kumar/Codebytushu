-- ============================================================
-- CodeByTushu — Auth System Supplement Schema
-- Run this AFTER the main schema.sql to add missing auth tables.
-- Safe to run multiple times (uses IF NOT EXISTS).
-- ============================================================

USE `codebytushu_db`;

-- ------------------------------------------------------------
-- login_attempts — DB-backed brute-force protection
-- Records every login attempt (success and failure) per IP.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `email`        VARCHAR(255)  NOT NULL,
  `ip_address`   VARCHAR(45)   NOT NULL,
  `user_agent`   VARCHAR(500)  DEFAULT NULL,
  `was_success`  TINYINT(1)    NOT NULL DEFAULT 0,
  `failure_reason` VARCHAR(100) DEFAULT NULL COMMENT 'invalid_password, banned, not_found, etc.',
  `attempted_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time`    (`ip_address`, `attempted_at`),
  KEY `idx_email_time` (`email`,      `attempted_at`),
  KEY `idx_success`    (`was_success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- user_sessions — persistent session store (for audit trail)
-- Optional: lets admins see active sessions and revoke them.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id`           VARCHAR(128)  NOT NULL COMMENT 'PHP session_id()',
  `user_id`      INT UNSIGNED  NOT NULL,
  `ip_address`   VARCHAR(45)   DEFAULT NULL,
  `user_agent`   VARCHAR(500)  DEFAULT NULL,
  `last_active`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_revoked`   TINYINT(1)    NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_last_active`(`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- oauth_states — CSRF tokens for OAuth flows
-- Short-lived random states to prevent CSRF during OAuth.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `oauth_states` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `state`      VARCHAR(128)  NOT NULL COMMENT 'random_bytes hex',
  `ip_address` VARCHAR(45)   DEFAULT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used`       TINYINT(1)    NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_state` (`state`),
  KEY `idx_created`    (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-cleanup event: purge stale login_attempts older than 24h
-- (only works if MySQL event scheduler is enabled)
DROP EVENT IF EXISTS `evt_cleanup_login_attempts`;
CREATE EVENT IF NOT EXISTS `evt_cleanup_login_attempts`
  ON SCHEDULE EVERY 1 HOUR
  DO
    DELETE FROM `login_attempts`
    WHERE `attempted_at` < DATE_SUB(NOW(), INTERVAL 24 HOUR);

DROP EVENT IF EXISTS `evt_cleanup_oauth_states`;
CREATE EVENT IF NOT EXISTS `evt_cleanup_oauth_states`
  ON SCHEDULE EVERY 1 HOUR
  DO
    DELETE FROM `oauth_states`
    WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 MINUTE);

-- ── Default super_admin user ───────────────────────────────────────────────
-- Password: Admin@12345  (change immediately after first login!)
-- Hash generated with: password_hash('Admin@12345', PASSWORD_BCRYPT, ['cost'=>12])
-- You MUST change this password immediately after setup.
INSERT IGNORE INTO `users`
  (full_name, username, email, password_hash, role, status, email_verified)
VALUES (
  'Admin',
  'admin',
  'admin@codebytushu.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'super_admin',
  'active',
  1
);
