CREATE TABLE `accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('platform','institution','leader','sales') NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NULL,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `institution_id` BIGINT UNSIGNED NULL,
  `leader_id` BIGINT UNSIGNED NULL,
  `ref_type` ENUM('platform','institution','leader','sales') NULL,
  `ref_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_role` (`role`),
  KEY `idx_institution` (`institution_id`),
  KEY `idx_leader` (`leader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='统一登录账户';

CREATE TABLE `institutions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(128) NOT NULL,
  `short_name` VARCHAR(64) NOT NULL,
  `license_no` VARCHAR(32) NOT NULL,
  `license_path` VARCHAR(255) NULL,
  `contact_name` VARCHAR(32) NULL,
  `contact_phone` VARCHAR(20) NULL,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_license` (`license_no`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机构资料';

CREATE TABLE `leaders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NOT NULL,
  `nickname` VARCHAR(32) NULL,
  `phone` VARCHAR(20) NOT NULL,
  `institution_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `leader_code` VARCHAR(32) NULL,
  `qr_link` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_institution` (`institution_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_leader_institution` FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团队长';

CREATE TABLE `sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `leader_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `bound_customers` INT UNSIGNED NOT NULL DEFAULT 0,
  `monthly_performance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_leader` (`leader_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_sales_leader` FOREIGN KEY (`leader_id`) REFERENCES `leaders`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='业务员';

CREATE TABLE `sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_account` (`account_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_session_account` FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录会话';

CREATE TABLE `sms_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL,
  `code_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信验证码（模拟）';

CREATE TABLE `settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(64) NOT NULL,
  `value` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置（含 share_domain）';

CREATE TABLE `customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NULL,
  `phone` VARCHAR(20) NULL,
  `bound_type` ENUM('leader','sales') NULL,
  `bound_id` BIGINT UNSIGNED NULL,
  `consumption` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bound` (`bound_type`,`bound_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户（本期占位）';
