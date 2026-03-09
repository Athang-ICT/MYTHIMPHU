-- RMA Domestic Payment Integration Database Schema
-- Create table for tracking payment transactions

CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `merchant_id` VARCHAR(50) NOT NULL,
  `payment_desc` VARCHAR(255) NOT NULL,
  `txn_amount` DECIMAL(15, 2) NOT NULL,
  `remitter_acc_no` VARCHAR(50),
  `remitter_bank_id` VARCHAR(50),
  `bfs_txn_id` VARCHAR(100) UNIQUE NOT NULL,
  `status` ENUM('pending', 'authorized', 'inquired', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `remitter_otp` VARCHAR(10),
  `response_code` VARCHAR(20),
  `response_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` TEXT,
  KEY `idx_bfs_txn_id` (`bfs_txn_id`),
  KEY `idx_merchant_id` (`merchant_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
