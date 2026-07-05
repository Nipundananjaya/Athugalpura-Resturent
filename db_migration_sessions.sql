-- ============================================================
-- SmartServe – Table Session System Migration
-- File: db_migration_sessions.sql
-- Run this in phpMyAdmin or MySQL command line
-- ============================================================

-- 1. Create table_sessions table
CREATE TABLE IF NOT EXISTS `table_sessions` (
    `session_id` INT AUTO_INCREMENT PRIMARY KEY,
    `table_number` INT NOT NULL,
    `status` ENUM('active', 'closed') DEFAULT 'active',
    `opened_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `closed_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_table_status` (`table_number`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add session_id column to orders table (nullable for backward compat)
ALTER TABLE `orders`
    ADD COLUMN `session_id` INT NULL DEFAULT NULL AFTER `table_number`,
    ADD INDEX `idx_session` (`session_id`);

-- 3. Add bill_requested column to orders (for customer "Request Bill" feature)
ALTER TABLE `orders`
    ADD COLUMN `bill_requested` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;
