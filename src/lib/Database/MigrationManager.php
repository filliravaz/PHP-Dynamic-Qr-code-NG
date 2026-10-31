<?php
/**
 * Database Migration and Auto-Seeding Manager
 *
 * Automatically handles:
 * 1. Initial database seeding on first startup (if database is empty).
 * 2. Incremental schema migrations on subsequent versions.
 * 3. Concurrency protection using MySQL advisory locks.
 * 4. Zero-overhead fast-path checks for up-to-date databases.
 */

class MigrationManager {
    /**
     * Cache flag to avoid repeating the check within the same PHP process.
     */
    private static bool $checkedInThisProcess = false;

    /**
     * Retrieve list of registered migrations in chronological execution order.
     *
     * @return array<string, callable>
     */
    public static function getMigrations(): array {
        return [
            '2.0.0' => [self::class, 'migration200'],
            '2.0.1' => [self::class, 'migration201'],
        ];
    }

    /**
     * Get the latest target schema version.
     */
    public static function getLatestVersion(): string {
        $migrations = self::getMigrations();
        $keys = array_keys($migrations);
        return (string)end($keys);
    }

    /**
     * Acquire database advisory lock.
     */
    public static function acquireLock(\mysqli $conn, string $prefix, int $timeout = 30): bool {
        $lockName = 'migration_lock_' . md5(DATABASE_NAME . '_' . $prefix);
        $res = $conn->query("SELECT GET_LOCK('{$lockName}', {$timeout}) AS `acquired`");
        if ($res) {
            $row = $res->fetch_assoc();
            $acquired = ((int)($row['acquired'] ?? 0) === 1);
            $res->close();
            return $acquired;
        }
        return false;
    }

    /**
     * Release database advisory lock.
     */
    public static function releaseLock(\mysqli $conn, string $prefix): void {
        $lockName = 'migration_lock_' . md5(DATABASE_NAME . '_' . $prefix);
        $conn->query("SELECT RELEASE_LOCK('{$lockName}')");
    }

    /**
     * Ensure the database schema is initialized and all migrations are applied.
     * Safe to call on every request; uses an indexed query and in-process caching.
     *
     * @param \mysqli|null $conn Optional active database connection (e.g. from MysqliDb).
     * @param callable|null $logger Optional logging callback: function(string $message): void
     * @param int|null $operatorId Optional default operator ID for unassigned records during v1 migration
     * @return void
     */
    public static function ensureMigrated(?\mysqli $conn = null, ?callable $logger = null, ?int $operatorId = null): void {
        if (self::$checkedInThisProcess) {
            return;
        }

        $prefix = defined('DATABASE_PREFIX') ? (string)DATABASE_PREFIX : '';
        $latestVersion = self::getLatestVersion();

        $shouldClose = false;
        if ($conn === null) {
            $conn = self::createConnection();
            $shouldClose = true;
        }

        if (!$conn) {
            $msg = "Unable to establish database connection for migration check.";
            self::log($logger, "Warning: " . $msg);
            throw new \RuntimeException($msg);
        }

        try {
            $verTable = $prefix . 'schema_version';

            // Fast path: check if schema_version exists and has the latest version recorded
            try {
                $fastCheck = $conn->query("SELECT 1 FROM `{$verTable}` WHERE `version` = '" . $conn->real_escape_string($latestVersion) . "' LIMIT 1");
                if ($fastCheck && $fastCheck->num_rows > 0) {
                    $fastCheck->close();
                    self::$checkedInThisProcess = true;
                    return;
                }
                if ($fastCheck) {
                    $fastCheck->close();
                }
            } catch (\Throwable $e) {
                // Table does not exist yet; proceed to migration/seed
            }

            // Lock to avoid race conditions when multiple workers start simultaneously
            $lockAcquired = self::acquireLock($conn, $prefix, 30);
            if (!$lockAcquired) {
                $msg = "Unable to acquire database migration lock (timed out or query failed).";
                self::log($logger, "Warning: " . $msg);
                throw new \RuntimeException($msg);
            }

            try {
                // Double-checked verification inside lock
                try {
                    $verCheck = $conn->query("SELECT 1 FROM `{$verTable}` WHERE `version` = '" . $conn->real_escape_string($latestVersion) . "' LIMIT 1");
                    if ($verCheck && $verCheck->num_rows > 0) {
                        $verCheck->close();
                        self::$checkedInThisProcess = true;
                        return;
                    }
                    if ($verCheck) {
                        $verCheck->close();
                    }
                } catch (\Throwable $e) {
                    // Table does not exist yet
                }

                // Check if database is empty
                if (self::isDatabaseEmpty($conn, $prefix)) {
                    self::seedDatabase($conn, $prefix, $logger);
                    self::$checkedInThisProcess = true;
                } else {
                    // Check if legacy v1 database (needs 2.0.0 migration)
                    if (self::isLegacyV1Database($conn, $prefix)) {
                        self::log($logger, "Notice: Legacy v1 database detected. Automatic migration to 2.0.0 skipped; please run 'php upgrade/upgrade.php' (or 'docker compose run --rm php-dynamic-qrcode php upgrade/upgrade.php') to complete upgrade and assign QR code ownership.");
                        error_log("Notice: Legacy v1 database detected. Automatic migration to 2.0.0 skipped; please run 'php upgrade/upgrade.php' to complete upgrade and assign QR code ownership.");
                        return;
                    }

                    // Run incremental migrations (2.0.1+, etc.)
                    self::executePendingMigrations($conn, $prefix, $logger, $operatorId);
                    self::$checkedInThisProcess = true;
                }
            } finally {
                self::releaseLock($conn, $prefix);
            }
        } catch (\Throwable $e) {
            self::log($logger, "Database migration/seed error: " . $e->getMessage());
            error_log("Database migration/seed error: " . $e->getMessage());
            throw $e;
        } finally {
            if ($shouldClose && $conn) {
                $conn->close();
            }
        }
    }

    /**
     * Check if an existing database is a legacy v1 database (migration 2.0.0 not applied).
     */
    public static function isLegacyV1Database(\mysqli $conn, string $prefix): bool {
        $verTable = $prefix . 'schema_version';
        try {
            $res = $conn->query("SELECT 1 FROM `{$verTable}` WHERE `version` = '2.0.0' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $res->close();
                return false;
            }
            if ($res) {
                $res->close();
            }
        } catch (\Throwable $e) {
            // Table does not exist
        }
        return true;
    }

    /**
     * Establish a database connection with retry logic for container startup.
     */
    public static function createConnection(int $maxRetries = 3, int $retryDelaySeconds = 1): ?\mysqli {
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }

        $host = defined('DATABASE_HOST') ? DATABASE_HOST : 'localhost';
        $user = defined('DATABASE_USER') ? DATABASE_USER : 'root';
        $pass = defined('DATABASE_PASSWORD') ? DATABASE_PASSWORD : '';
        $name = defined('DATABASE_NAME') ? DATABASE_NAME : 'qrcode';
        $port = defined('DATABASE_PORT') ? (int)DATABASE_PORT : 3306;
        $charset = defined('DATABASE_CHARSET') ? DATABASE_CHARSET : 'utf8mb4';

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $conn = @new \mysqli($host, $user, $pass, $name, $port);
                if (!$conn->connect_errno) {
                    $conn->set_charset($charset);
                    return $conn;
                }
            } catch (\Throwable $e) {
                // Wait and retry for transient startup delay
            }

            if ($attempt < $maxRetries) {
                sleep($retryDelaySeconds);
            }
        }

        return null;
    }

    /**
     * Check if the database contains no application tables.
     */
    public static function isDatabaseEmpty(\mysqli $conn, string $prefix): bool {
        $candidates = [
            $prefix . 'users',
            $prefix . 'admin_accounts',
            $prefix . 'dynamic_qrcodes',
            $prefix . 'static_qrcodes',
            $prefix . 'schema_version'
        ];

        // 1. Try information_schema lookup
        $escaped = array_map(function($t) use ($conn) {
            return "'" . $conn->real_escape_string($t) . "'";
        }, $candidates);

        $sql = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (" . implode(',', $escaped) . ") LIMIT 1";
        $res = $conn->query($sql);
        if ($res !== false) {
            $hasTable = ($res->num_rows > 0);
            $res->close();
            return !$hasTable;
        }

        // 2. Fallback using SHOW TABLES with properly escaped wildcards
        foreach ($candidates as $table) {
            $escapedTable = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $table);
            $check = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($escapedTable) . "'");
            if ($check && $check->num_rows > 0) {
                $check->close();
                return false;
            }
            if ($check) {
                $check->close();
            }
        }

        return true;
    }

    /**
     * Seed initial database schema and default superadmin user.
     */
    public static function seedDatabase(\mysqli $conn, string $prefix, ?callable $logger = null): void {
        self::log($logger, "Database is empty. Seeding initial schema and default data...");

        // 1. Users table
        $usersTable = $prefix . 'users';
        self::executeQuery($conn, "CREATE TABLE IF NOT EXISTS `{$usersTable}` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Failed creating users table");

        // Seed default superadmin account (password: superadmin, must change password)
        $superadminHash = '$2y$10$xpZc5KC.aU2XHkcqhuZGFuAnqmtL4Unt8MysOyylceq.19XIyoZpG';
        $stmt = $conn->prepare("INSERT INTO `{$usersTable}` (`id`, `username`, `password`, `series_id`, `remember_token`, `expires`, `type`, `must_change_password`, `session_version`) VALUES (1, 'superadmin', ?, NULL, NULL, NULL, 'super', 1, 1) ON DUPLICATE KEY UPDATE `id` = `id`");
        if (!$stmt) {
            throw new \RuntimeException("Failed preparing superadmin seed query: " . $conn->error);
        }
        $stmt->bind_param('s', $superadminHash);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new \RuntimeException("Failed executing superadmin seed: " . $err);
        }
        $stmt->close();

        // 2. Dynamic QR table
        $dynTable = $prefix . 'dynamic_qrcodes';
        $fkDyn = 'fk_' . ($prefix !== '' ? $prefix : '') . 'dynamic_owner';
        self::executeQuery($conn, "CREATE TABLE IF NOT EXISTS `{$dynTable}` (
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
          CONSTRAINT `{$fkDyn}` FOREIGN KEY (`id_owner`) REFERENCES `{$usersTable}` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Failed creating dynamic_qrcodes table");

        // 3. Static QR table
        $statTable = $prefix . 'static_qrcodes';
        $fkStat = 'fk_' . ($prefix !== '' ? $prefix : '') . 'static_owner';
        self::executeQuery($conn, "CREATE TABLE IF NOT EXISTS `{$statTable}` (
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
          CONSTRAINT `{$fkStat}` FOREIGN KEY (`id_owner`) REFERENCES `{$usersTable}` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Failed creating static_qrcodes table");

        // 4. Login attempts table
        $laTable = $prefix . 'login_attempts';
        self::executeQuery($conn, "CREATE TABLE IF NOT EXISTS `{$laTable}` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ip_address` varchar(45) NOT NULL,
          `username` varchar(50) NOT NULL,
          `attempted_at` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_ip_time` (`ip_address`, `attempted_at`),
          KEY `idx_user_time` (`username`, `attempted_at`),
          KEY `idx_user_ip_time` (`username`, `ip_address`, `attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Failed creating login_attempts table");

        // 5. Schema version table
        $verTable = $prefix . 'schema_version';
        self::executeQuery($conn, "CREATE TABLE IF NOT EXISTS `{$verTable}` (
          `version` varchar(20) NOT NULL,
          `applied_at` datetime NOT NULL,
          PRIMARY KEY (`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Failed creating schema_version table");

        // Mark all registered migrations as applied
        $migrations = self::getMigrations();
        $insStmt = $conn->prepare("INSERT INTO `{$verTable}` (`version`, `applied_at`) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE `applied_at` = NOW()");
        if (!$insStmt) {
            throw new \RuntimeException("Failed preparing schema_version insert: " . $conn->error);
        }
        $vName = '';
        $insStmt->bind_param('s', $vName);
        foreach (array_keys($migrations) as $vName) {
            if (!$insStmt->execute()) {
                $err = $insStmt->error;
                $insStmt->close();
                throw new \RuntimeException("Failed recording schema version {$vName}: " . $err);
            }
        }
        $insStmt->close();

        self::log($logger, "Database seeded successfully. Superadmin account provisioned.");
    }

    /**
     * Run all unapplied migrations under database-wide advisory lock.
     */
    public static function runMigrations(\mysqli $conn, string $prefix, ?callable $logger = null, ?int $operatorId = null): void {
        $lockAcquired = self::acquireLock($conn, $prefix, 30);
        if (!$lockAcquired) {
            throw new \RuntimeException("Unable to acquire database migration lock (timed out or query failed).");
        }

        try {
            self::executePendingMigrations($conn, $prefix, $logger, $operatorId);
        } finally {
            self::releaseLock($conn, $prefix);
        }
    }

    /**
     * Execute unapplied migrations in chronological sequence.
     */
    private static function executePendingMigrations(\mysqli $conn, string $prefix, ?callable $logger = null, ?int $operatorId = null): void {
        $verTable = $prefix . 'schema_version';
        self::executeQuery($conn, "CREATE TABLE IF NOT EXISTS `{$verTable}` (
          `version` varchar(20) NOT NULL,
          `applied_at` datetime NOT NULL,
          PRIMARY KEY (`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Failed verifying schema_version table");

        $applied = [];
        $res = $conn->query("SELECT `version` FROM `{$verTable}`");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $applied[] = $row['version'];
            }
            $res->close();
        }

        $migrations = self::getMigrations();
        $executed = 0;

        foreach ($migrations as $version => $callback) {
            if (!in_array($version, $applied, true)) {
                self::log($logger, "Running migration {$version}...");
                call_user_func($callback, $conn, $prefix, $logger, $operatorId);

                $insStmt = $conn->prepare("INSERT INTO `{$verTable}` (`version`, `applied_at`) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE `applied_at` = NOW()");
                if (!$insStmt) {
                    throw new \RuntimeException("Failed preparing schema_version record query: " . $conn->error);
                }
                $insStmt->bind_param('s', $version);
                if (!$insStmt->execute()) {
                    $err = $insStmt->error;
                    $insStmt->close();
                    throw new \RuntimeException("Failed recording schema version {$version}: " . $err);
                }
                $insStmt->close();
                self::log($logger, "Migration {$version} applied successfully.");
                $executed++;
            }
        }

        if ($executed === 0) {
            self::log($logger, "Database schema is up to date (version " . self::getLatestVersion() . ").");
        }
    }

    /**
     * Migration 2.0.0: Rename admin_accounts to users, add auth/ownership columns, backfill ownership.
     */
    public static function migration200(\mysqli $conn, string $prefix, ?callable $logger, ?int $operatorId = null): void {
        $usersTable = $prefix . 'users';
        $adminTable = $prefix . 'admin_accounts';
        $dynTable = $prefix . 'dynamic_qrcodes';
        $statTable = $prefix . 'static_qrcodes';

        // 1. Create table snapshots for safety before modifying schema
        $snapshotTables = [$adminTable, $usersTable, $dynTable, $statTable];
        foreach ($snapshotTables as $tName) {
            $tCheck = $conn->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $conn->real_escape_string($tName) . "' LIMIT 1");
            if ($tCheck && $tCheck->num_rows > 0) {
                $tCheck->close();
                $backupName = $tName . '_backup_v1';
                $bCheck = $conn->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $conn->real_escape_string($backupName) . "' LIMIT 1");
                if (!$bCheck || $bCheck->num_rows === 0) {
                    if ($conn->query("CREATE TABLE `{$backupName}` LIKE `{$tName}`") === TRUE) {
                        $copyRes = $conn->query("INSERT INTO `{$backupName}` SELECT * FROM `{$tName}`");
                        if ($copyRes === TRUE) {
                            self::log($logger, "Created backup snapshot: {$backupName}");
                        } else {
                            $copyErr = $conn->error;
                            try {
                                $conn->query("DROP TABLE IF EXISTS `{$backupName}`");
                            } catch (\Throwable $dropEx) {}
                            throw new \RuntimeException("Failed to copy rows into snapshot table `{$backupName}`: " . $copyErr);
                        }
                    } else {
                        throw new \RuntimeException("Failed to create snapshot table `{$backupName}`: " . $conn->error);
                    }
                }
                if ($bCheck) $bCheck->close();
            } elseif ($tCheck) {
                $tCheck->close();
            }
        }

        // 2. Rename admin_accounts to users if needed
        $uCheck = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($usersTable) . "'");
        $hasUsers = ($uCheck && $uCheck->num_rows > 0);
        if ($uCheck) $uCheck->close();

        $aCheck = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($adminTable) . "'");
        $hasAdmin = ($aCheck && $aCheck->num_rows > 0);
        if ($aCheck) $aCheck->close();

        if (!$hasUsers && $hasAdmin) {
            self::executeQuery($conn, "ALTER TABLE `{$adminTable}` RENAME TO `{$usersTable}`", "Failed renaming {$adminTable} to {$usersTable}");
            self::log($logger, "Renamed {$adminTable} to {$usersTable}.");
            $hasUsers = true;
        }

        // 3. User table columns update
        if ($hasUsers) {
            $colCheck = $conn->query("SHOW COLUMNS FROM `{$usersTable}` LIKE 'user_name'");
            if ($colCheck && $colCheck->num_rows > 0) {
                self::executeQuery($conn, "ALTER TABLE `{$usersTable}` CHANGE `user_name` `username` VARCHAR(50) NOT NULL", "Failed renaming user_name to username in {$usersTable}");
                self::log($logger, "Renamed column user_name to username in {$usersTable}.");
            }
            if ($colCheck) $colCheck->close();

            $typeCheck = $conn->query("SHOW COLUMNS FROM `{$usersTable}` LIKE 'admin_type'");
            if ($typeCheck && $typeCheck->num_rows > 0) {
                self::executeQuery($conn, "ALTER TABLE `{$usersTable}` CHANGE `admin_type` `type` VARCHAR(10) NOT NULL", "Failed renaming admin_type to type in {$usersTable}");
                self::log($logger, "Renamed column admin_type to type in {$usersTable}.");
            }
            if ($typeCheck) $typeCheck->close();

            $mcpCheck = $conn->query("SHOW COLUMNS FROM `{$usersTable}` LIKE 'must_change_password'");
            if ($mcpCheck && $mcpCheck->num_rows === 0) {
                self::executeQuery($conn, "ALTER TABLE `{$usersTable}` ADD `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`", "Failed adding must_change_password to {$usersTable}");
                self::log($logger, "Added must_change_password column to {$usersTable}.");
            }
            if ($mcpCheck) $mcpCheck->close();

            $svCheck = $conn->query("SHOW COLUMNS FROM `{$usersTable}` LIKE 'session_version'");
            if ($svCheck && $svCheck->num_rows === 0) {
                self::executeQuery($conn, "ALTER TABLE `{$usersTable}` ADD `session_version` INT(11) NOT NULL DEFAULT 1 AFTER `must_change_password`", "Failed adding session_version to {$usersTable}");
                self::log($logger, "Added session_version column to {$usersTable}.");
            }
            if ($svCheck) $svCheck->close();
        }

        // 4. Add id_owner column to dynamic_qrcodes
        $dynCheck = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($dynTable) . "'");
        if ($dynCheck && $dynCheck->num_rows > 0) {
            $ownerCheck = $conn->query("SHOW COLUMNS FROM `{$dynTable}` LIKE 'id_owner'");
            if ($ownerCheck && $ownerCheck->num_rows === 0) {
                self::executeQuery($conn, "ALTER TABLE `{$dynTable}` ADD `id_owner` INT(25) NULL DEFAULT NULL AFTER `id`", "Failed adding id_owner to {$dynTable}");
                self::log($logger, "Added id_owner column to {$dynTable}.");
            }
            if ($ownerCheck) $ownerCheck->close();
        }
        if ($dynCheck) $dynCheck->close();

        // 5. Add id_owner column to static_qrcodes
        $statCheck = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($statTable) . "'");
        if ($statCheck && $statCheck->num_rows > 0) {
            $ownerCheck = $conn->query("SHOW COLUMNS FROM `{$statTable}` LIKE 'id_owner'");
            if ($ownerCheck && $ownerCheck->num_rows === 0) {
                self::executeQuery($conn, "ALTER TABLE `{$statTable}` ADD `id_owner` INT(25) NULL DEFAULT NULL AFTER `id`", "Failed adding id_owner to {$statTable}");
                self::log($logger, "Added id_owner column to {$statTable}.");
            }
            if ($ownerCheck) $ownerCheck->close();
        }
        if ($statCheck) $statCheck->close();

        // 6. Create login_attempts table
        $laTable = $prefix . 'login_attempts';
        self::executeQuery($conn, "CREATE TABLE IF NOT EXISTS `{$laTable}` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ip_address` varchar(45) NOT NULL,
          `username` varchar(50) NOT NULL,
          `attempted_at` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_ip_time` (`ip_address`, `attempted_at`),
          KEY `idx_user_time` (`username`, `attempted_at`),
          KEY `idx_user_ip_time` (`username`, `ip_address`, `attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Failed creating login_attempts table");

        // 7. Backfill id_owner for dynamic and static QR codes from creator records
        if ($hasUsers) {
            foreach ([$dynTable, $statTable] as $tName) {
                $tCheck = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tName) . "'");
                if ($tCheck && $tCheck->num_rows > 0) {
                    $conn->query("UPDATE `{$tName}` t INNER JOIN `{$usersTable}` u ON t.`created_by` = u.`id` SET t.`id_owner` = t.`created_by` WHERE (t.`id_owner` IS NULL OR t.`id_owner` = 0) AND t.`created_by` > 0");

                    if ($operatorId !== null) {
                        $assignStmt = $conn->prepare("UPDATE `{$tName}` SET `id_owner` = ? WHERE `id_owner` IS NULL OR `id_owner` = 0");
                        if ($assignStmt) {
                            $assignStmt->bind_param('i', $operatorId);
                            $assignStmt->execute();
                            $assignStmt->close();
                        }
                    }

                    // Normalize any remaining non-positive IDs to NULL
                    $conn->query("UPDATE `{$tName}` SET `id_owner` = NULL WHERE `id_owner` <= 0");
                }
                if ($tCheck) $tCheck->close();
            }

            // 8. Safely add foreign key constraints if not present
            $fkDyn = 'fk_' . ($prefix !== '' ? $prefix : '') . 'dynamic_owner';
            $fkDynCheck = $conn->query("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = '{$fkDyn}' LIMIT 1");
            if ($fkDynCheck && $fkDynCheck->num_rows === 0) {
                $dynExists = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($dynTable) . "'");
                if ($dynExists && $dynExists->num_rows > 0) {
                    $conn->query("UPDATE `{$dynTable}` SET `id_owner` = NULL WHERE `id_owner` IS NOT NULL AND `id_owner` NOT IN (SELECT `id` FROM `{$usersTable}`)");
                    @$conn->query("ALTER TABLE `{$dynTable}` ADD KEY `id_owner` (`id_owner`), ADD CONSTRAINT `{$fkDyn}` FOREIGN KEY (`id_owner`) REFERENCES `{$usersTable}` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
                }
                if ($dynExists) $dynExists->close();
            }
            if ($fkDynCheck) $fkDynCheck->close();

            $fkStat = 'fk_' . ($prefix !== '' ? $prefix : '') . 'static_owner';
            $fkStatCheck = $conn->query("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = '{$fkStat}' LIMIT 1");
            if ($fkStatCheck && $fkStatCheck->num_rows === 0) {
                $statExists = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($statTable) . "'");
                if ($statExists && $statExists->num_rows > 0) {
                    $conn->query("UPDATE `{$statTable}` SET `id_owner` = NULL WHERE `id_owner` IS NOT NULL AND `id_owner` NOT IN (SELECT `id` FROM `{$usersTable}`)");
                    @$conn->query("ALTER TABLE `{$statTable}` ADD KEY `id_owner` (`id_owner`), ADD CONSTRAINT `{$fkStat}` FOREIGN KEY (`id_owner`) REFERENCES `{$usersTable}` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
                }
                if ($statExists) $statExists->close();
            }
            if ($fkStatCheck) $fkStatCheck->close();
        }

        // 9. Normalize legacy static_qrcodes content representation
        $sCheck = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($statTable) . "'");
        if ($sCheck && $sCheck->num_rows > 0) {
            $legacyRows = $conn->query("SELECT `id`, `content` FROM `{$statTable}` WHERE `content` LIKE '%&lt;strong&gt;%' OR `content` LIKE '%&lt;div%' OR `content` LIKE '%&lt;b&gt;%'");
            if ($legacyRows && $legacyRows->num_rows > 0) {
                $upStmt = $conn->prepare("UPDATE `{$statTable}` SET `content` = ? WHERE `id` = ?");
                if ($upStmt) {
                    while ($r = $legacyRows->fetch_assoc()) {
                        $decoded = htmlspecialchars_decode($r['content'], ENT_QUOTES);
                        $upStmt->bind_param('si', $decoded, $r['id']);
                        $upStmt->execute();
                    }
                    $upStmt->close();
                }
            }
            if ($legacyRows) $legacyRows->close();
        }
        if ($sCheck) $sCheck->close();
    }

    /**
     * Migration 2.0.1: Dynamic QR identifier collation, collision audit, narrowing to VARCHAR(32) NOT NULL, unique index.
     */
    public static function migration201(\mysqli $conn, string $prefix, ?callable $logger, ?int $operatorId = null): void {
        $dynTable = $prefix . 'dynamic_qrcodes';
        $dynTableCheck = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($dynTable) . "'");
        if ($dynTableCheck && $dynTableCheck->num_rows > 0) {
            $idColCheck = $conn->query("SHOW COLUMNS FROM `{$dynTable}` LIKE 'identifier'");
            if ($idColCheck && $idColCheck->num_rows > 0) {
                $colRow = $idColCheck->fetch_assoc();
                $isVarchar32 = (strtolower(trim($colRow['Type'] ?? '')) === 'varchar(32)');
                $isNotNull = (strtoupper(trim($colRow['Null'] ?? '')) === 'NO');

                if (!$isVarchar32 || !$isNotNull) {
                    $rowsRes = $conn->query("SELECT `id`, `identifier` FROM `{$dynTable}` ORDER BY `id` ASC");
                    $usedIdentifiers = [];
                    $recordsToFix = [];

                    if ($rowsRes) {
                        while ($row = $rowsRes->fetch_assoc()) {
                            $rawId = $row['identifier'];
                            $isValid = ($rawId !== null && $rawId !== '' && strlen($rawId) <= 32);
                            $normRawId = ($rawId !== null) ? strtolower(rtrim((string)$rawId)) : '';

                            if ($isValid && $normRawId !== '' && !isset($usedIdentifiers[$normRawId])) {
                                $usedIdentifiers[$normRawId] = true;
                            } else {
                                $recordsToFix[] = $row;
                            }
                        }
                        $rowsRes->close();
                    }

                    if (!empty($recordsToFix)) {
                        $upStmt = $conn->prepare("UPDATE `{$dynTable}` SET `identifier` = ? WHERE `id` = ?");
                        if ($upStmt) {
                            foreach ($recordsToFix as $rec) {
                                $origId = $rec['identifier'];
                                $recId = (int)$rec['id'];
                                $newId = null;
                                $normNewId = null;

                                if ($origId !== null && $origId !== '' && strlen($origId) > 32) {
                                    $candidate = substr($origId, 0, 32);
                                    $normCandidate = strtolower(rtrim($candidate));
                                    if ($normCandidate !== '' && !isset($usedIdentifiers[$normCandidate])) {
                                        $newId = $candidate;
                                        $normNewId = $normCandidate;
                                    }
                                }

                                while ($newId === null || isset($usedIdentifiers[$normNewId])) {
                                    $newId = bin2hex(random_bytes(8));
                                    $normNewId = strtolower(rtrim($newId));
                                }

                                $usedIdentifiers[$normNewId] = true;
                                $upStmt->bind_param('si', $newId, $recId);
                                $upStmt->execute();
                            }
                            $upStmt->close();
                            self::log($logger, "Resolved " . count($recordsToFix) . " identifier collisions/invalids in {$dynTable}.");
                        }
                    }

                    self::executeQuery($conn, "ALTER TABLE `{$dynTable}` MODIFY `identifier` VARCHAR(32) NOT NULL", "Failed modifying identifier column to VARCHAR(32) NOT NULL");
                    self::log($logger, "Modified identifier column in {$dynTable} to VARCHAR(32) NOT NULL.");
                }
            }
            if ($idColCheck) $idColCheck->close();

            $idxCheck = $conn->query("SHOW INDEX FROM `{$dynTable}` WHERE Key_name = 'idx_identifier'");
            if ($idxCheck && $idxCheck->num_rows === 0) {
                self::executeQuery($conn, "ALTER TABLE `{$dynTable}` ADD UNIQUE KEY `idx_identifier` (`identifier`)", "Failed adding idx_identifier index to {$dynTable}");
                self::log($logger, "Added unique index idx_identifier to {$dynTable}.");
            }
            if ($idxCheck) $idxCheck->close();
        }
        if ($dynTableCheck) $dynTableCheck->close();
    }

    /**
     * Helper to execute query with detailed exception on failure.
     */
    private static function executeQuery(\mysqli $conn, string $sql, string $errorMessage): void {
        if ($conn->query($sql) === false) {
            throw new \RuntimeException($errorMessage . ': ' . $conn->error);
        }
    }

    /**
     * Helper to log messages.
     */
    private static function log(?callable $logger, string $message): void {
        if ($logger !== null) {
            $logger($message);
        }
    }
}
