-- ============================================================
-- Migration 012: Store Products + Enhanced Orders for Store
-- CodeByTushu E-Commerce Phase 1
-- Run this in phpMyAdmin or via MySQL CLI on Hostinger
-- ============================================================

-- 1. Store Products Table
CREATE TABLE IF NOT EXISTS `store_products` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`           VARCHAR(255) NOT NULL,
  `description`     TEXT,
  `price`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `category`        VARCHAR(100) NOT NULL DEFAULT 'Merchandise',
  `images`          JSON DEFAULT NULL COMMENT 'Array of image paths',
  `thumbnail`       VARCHAR(500) DEFAULT NULL COMMENT 'Primary thumbnail path',
  `features`        JSON DEFAULT NULL COMMENT 'Array of feature strings',
  `stock_status`    ENUM('in-stock','out-of-stock') NOT NULL DEFAULT 'in-stock',
  `rating`          DECIMAL(2,1) NOT NULL DEFAULT 5.0,
  `reviews_count`   INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `is_new_arrival`  TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order`      INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`),
  KEY `idx_stock` (`stock_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Alter orders table: add order_type, shipping, fulfillment
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `order_type`           ENUM('course','store','mixed') NOT NULL DEFAULT 'course' AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `shipping_name`         VARCHAR(255) DEFAULT NULL AFTER `payment_status`,
  ADD COLUMN IF NOT EXISTS `shipping_phone`        VARCHAR(20) DEFAULT NULL AFTER `shipping_name`,
  ADD COLUMN IF NOT EXISTS `shipping_address`      TEXT DEFAULT NULL AFTER `shipping_phone`,
  ADD COLUMN IF NOT EXISTS `shipping_city`         VARCHAR(100) DEFAULT NULL AFTER `shipping_address`,
  ADD COLUMN IF NOT EXISTS `shipping_state`        VARCHAR(100) DEFAULT NULL AFTER `shipping_city`,
  ADD COLUMN IF NOT EXISTS `shipping_pincode`      VARCHAR(10) DEFAULT NULL AFTER `shipping_state`,
  ADD COLUMN IF NOT EXISTS `fulfillment_status`    ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending' AFTER `shipping_pincode`,
  ADD COLUMN IF NOT EXISTS `tracking_number`       VARCHAR(255) DEFAULT NULL AFTER `fulfillment_status`,
  ADD COLUMN IF NOT EXISTS `payment_reference`     VARCHAR(255) DEFAULT NULL AFTER `tracking_number`,
  ADD COLUMN IF NOT EXISTS `admin_notes`           TEXT DEFAULT NULL AFTER `payment_reference`;

-- 3. Alter payment_method ENUM to include 'cod'
ALTER TABLE `orders`
  MODIFY COLUMN `payment_method` ENUM('upi','razorpay','free','cod') NOT NULL DEFAULT 'upi';

-- 4. Alter order_items: add product_id for store products
ALTER TABLE `order_items`
  ADD COLUMN IF NOT EXISTS `product_id`    INT UNSIGNED DEFAULT NULL AFTER `course_id`,
  ADD COLUMN IF NOT EXISTS `product_name`  VARCHAR(255) DEFAULT NULL AFTER `product_id`,
  ADD COLUMN IF NOT EXISTS `quantity`      INT UNSIGNED NOT NULL DEFAULT 1 AFTER `product_name`;

-- 5. Add index on order_type for faster admin filtering
ALTER TABLE `orders`
  ADD INDEX IF NOT EXISTS `idx_order_type` (`order_type`),
  ADD INDEX IF NOT EXISTS `idx_fulfillment` (`fulfillment_status`);

-- Verify migration
SELECT 'Migration 012 complete: store_products table created, orders enhanced.' AS status;
