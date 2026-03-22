CREATE TABLE IF NOT EXISTS `PREFIX_fenixtrace_sync` (
  `id_sync` INT AUTO_INCREMENT PRIMARY KEY,
  `id_product` INT NOT NULL,
  `state` VARCHAR(20) DEFAULT 'draft',
  `tx_hash` VARCHAR(255) DEFAULT NULL,
  `notarization_tx_hash` VARCHAR(255) DEFAULT NULL,
  `ipfs_hash` VARCHAR(255) DEFAULT NULL,
  `last_sync_at` DATETIME DEFAULT NULL,
  `last_error` TEXT DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_id_product` (`id_product`),
  KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
