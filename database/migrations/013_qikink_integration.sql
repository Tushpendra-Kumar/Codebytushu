-- ============================================================
-- Migration 013: Qikink Integration Fields
-- CodeByTushu E-Commerce Phase 2
-- Run this in phpMyAdmin or via MySQL CLI on Hostinger
-- ============================================================

-- 1. Add fields to store_products for dynamic design injection
ALTER TABLE `store_products`
  ADD COLUMN `print_file_path` VARCHAR(500) DEFAULT NULL COMMENT 'High-res transparent PNG for Qikink' AFTER `images`,
  ADD COLUMN `qikink_base_sku` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. MNS-RN-BLK' AFTER `print_file_path`;

-- 2. Add fields to orders for Qikink tracking
ALTER TABLE `orders`
  ADD COLUMN `qikink_order_id` VARCHAR(100) DEFAULT NULL COMMENT 'Order ID returned by Qikink' AFTER `tracking_number`,
  ADD COLUMN `qikink_status` VARCHAR(100) DEFAULT NULL COMMENT 'Status from Qikink (e.g. In Production, Dispatched)' AFTER `qikink_order_id`,
  ADD COLUMN `awb_number` VARCHAR(100) DEFAULT NULL COMMENT 'Courier AWB from Qikink' AFTER `qikink_status`,
  ADD COLUMN `courier_name` VARCHAR(100) DEFAULT NULL COMMENT 'Courier company name' AFTER `awb_number`;

-- Verify migration
SELECT 'Migration 013 complete: Qikink integration fields added.' AS status;
