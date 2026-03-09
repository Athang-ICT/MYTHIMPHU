-- Payment Transaction Table for RMA Domestic Payment Integration
CREATE TABLE IF NOT EXISTS `payment_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` varchar(50) NOT NULL,
  `payment_desc` varchar(255) NOT NULL,
  `txn_amount` decimal(10,2) NOT NULL,
  `remitter_acc_no` varchar(50) DEFAULT '',
  `remitter_bank_id` varchar(50) DEFAULT '',
  `bfs_txn_id` varchar(100) NOT NULL,
  `status` enum('pending','authorized','inquired','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `remitter_otp` varchar(10) DEFAULT '',
  `response_code` varchar(20) DEFAULT '',
  `response_message` varchar(500) DEFAULT '',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bfs_txn_id` (`bfs_txn_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
