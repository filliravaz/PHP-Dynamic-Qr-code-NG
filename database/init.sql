SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(25) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `series_id` varchar(60) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `type` varchar(10) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `session_version` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial default superadmin account:
-- Initial password is set to 'superadmin', but marked with must_change_password = 1.
-- The user is forced to change the password immediately upon first login before access is granted.
INSERT INTO `users` (`id`, `username`, `password`, `series_id`, `remember_token`, `expires`, `type`, `must_change_password`, `session_version`) VALUES
(1, 'superadmin', '$2y$10$xpZc5KC.aU2XHkcqhuZGFuAnqmtL4Unt8MysOyylceq.19XIyoZpG', NULL, NULL, NULL, 'super', 1, 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

CREATE TABLE IF NOT EXISTS `dynamic_qrcodes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `id_owner` int(25) NULL DEFAULT NULL,
  `filename` varchar(45) NOT NULL,
  `format` varchar(45) DEFAULT NULL,
  `identifier` varchar(32) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `qrcode` varchar(60) DEFAULT NULL,
  `scan` int(11) NOT NULL DEFAULT '0',
  `state` varchar(20) NOT NULL DEFAULT 'enable',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_identifier` (`identifier`),
  KEY `id_owner` (`id_owner`),
  CONSTRAINT `fk_dynamic_owner` FOREIGN KEY (`id_owner`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `static_qrcodes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `id_owner` int(25) NULL DEFAULT NULL,
  `filename` varchar(45) NOT NULL,
  `format` varchar(45) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `content` mediumtext,
  `qrcode` varchar(60) DEFAULT NULL,
  `state` varchar(20) NOT NULL DEFAULT 'enable',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_owner` (`id_owner`),
  CONSTRAINT `fk_static_owner` FOREIGN KEY (`id_owner`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) NOT NULL,
  `attempted_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`, `attempted_at`),
  KEY `idx_user_time` (`username`, `attempted_at`),
  KEY `idx_user_ip_time` (`username`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `schema_version` (
  `version` varchar(20) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `schema_version` (`version`, `applied_at`) VALUES
('2.0.0', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();
