<?php
require_once ('../config/environment.php');

$isCli = (php_sapi_name() === 'cli');
define('TARGET_VERSION', '2.0.0');

function outputMessage($message, $isCli) {
    if ($isCli) {
        echo strip_tags($message) . "\n";
    } else {
        echo $message . "<br>\n";
    }
}

function abortMigration($conn, $message, $isCli) {
    outputMessage("Error: " . $message, $isCli);
    if ($conn instanceof mysqli) {
        $conn->close();
    }
    exit(1);
}

$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME, DATABASE_PORT);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . ($isCli ? "\n" : "<br>"));
}

// 1. Ensure schema_version table exists to track migrations across container restarts
$versionTable = DATABASE_PREFIX . 'schema_version';
$verCreate = $conn->query("CREATE TABLE IF NOT EXISTS `{$versionTable}` (
    `version` VARCHAR(20) NOT NULL,
    `applied_at` DATETIME NOT NULL,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($verCreate === false) {
    abortMigration($conn, "Failed to verify or create schema_version table: " . $conn->error, $isCli);
}

// 2. Check if TARGET_VERSION has already been applied
$versionStmt = $conn->prepare("SELECT `applied_at` FROM `{$versionTable}` WHERE `version` = ?");
if (!$versionStmt) {
    abortMigration($conn, "Failed to prepare version check query: " . $conn->error, $isCli);
}

$targetVer = TARGET_VERSION;
$versionStmt->bind_param('s', $targetVer);
$versionStmt->execute();
$versionRes = $versionStmt->get_result();

if ($versionRes && $versionRes->num_rows > 0) {
    $row = $versionRes->fetch_assoc();
    $versionStmt->close();
    outputMessage("Database schema is already at version " . TARGET_VERSION . " (applied on " . $row['applied_at'] . "). No changes needed.", $isCli);
    $conn->close();
    exit;
}
$versionStmt->close();

// 3. If accessed via browser, verify superadmin authentication and require CSRF-protected POST
if (!$isCli) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once dirname(__DIR__) . '/helpers/helpers.php';

    /**
     * Legacy installation note:
     * Installations running an older database schema where the user table is still named `admin_accounts`
     * (with `user_name` / `admin_type` columns) cannot authenticate via the 2.0 web login flow and must
     * execute the upgrade via the CLI (`php src/upgrade/upgrade.php` on host, or `docker compose run --rm php-dynamic-qrcode php upgrade/upgrade.php` in Docker).
     */
    if (empty($_SESSION['user_logged_in']) || empty($_SESSION['type']) || $_SESSION['type'] !== 'super') {
        http_response_code(403);
        outputMessage("Forbidden: Database upgrades via browser require an authenticated super administrator session. <a href='../login.php'>Log In</a><br><br><em>Note: If upgrading a legacy installation still using <code>admin_accounts</code>, please run the upgrade via CLI (<code>php src/upgrade/upgrade.php</code> or in Docker: <code>docker compose run --rm php-dynamic-qrcode php upgrade/upgrade.php</code>).</em>", false);
        $conn->close();
        exit(1);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $userTableCandidates = [DATABASE_PREFIX . 'users', DATABASE_PREFIX . 'admin_accounts'];
        $userOpts = null;
        foreach ($userTableCandidates as $cand) {
            $candCheck = $conn->query("SHOW TABLES LIKE '{$cand}'");
            if ($candCheck && $candCheck->num_rows > 0) {
                $userColCheck = $conn->query("SHOW COLUMNS FROM `{$cand}` LIKE 'username'");
                $hasUsername = ($userColCheck && $userColCheck->num_rows > 0);
                $uCol = $hasUsername ? 'username' : 'user_name';
                $tColCheck = $conn->query("SHOW COLUMNS FROM `{$cand}` LIKE 'type'");
                $hasType = ($tColCheck && $tColCheck->num_rows > 0);
                $tCol = $hasType ? 'type' : 'admin_type';
                $userOpts = $conn->query("SELECT `id`, `{$uCol}` AS username, `{$tCol}` AS `type` FROM `{$cand}` ORDER BY `id` ASC");
                break;
            }
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <title>Database Upgrade</title>
            <style>
                body { font-family: sans-serif; margin: 40px; line-height: 1.6; }
                .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
                .alert { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
                .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
                .form-group { margin-bottom: 20px; }
                select { padding: 8px 12px; border-radius: 4px; border: 1px solid #ccc; font-size: 14px; }
            </style>
        </head>
        <body>
            <h2>PHP-Dynamic-Qr-code-NG Database Upgrade</h2>
            <div class="info">
                Target schema version: <strong><?php echo htmlspecialchars(TARGET_VERSION); ?></strong>
            </div>
            <div class="alert">
                <strong>Automatic Snapshot:</strong> Before applying changes, backup snapshot tables with suffix <code>_backup_v1</code> will be created automatically in your database.
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group">
                    <label for="operator_id"><strong>Assign unowned legacy QR codes to:</strong></label><br>
                    <small>QR codes with verifiable creator records will be restored to their original owners. For any remaining records where no owner can be identified:</small><br>
                    <select name="operator_id" id="operator_id">
                        <option value="">-- Preserve NULL (Leave Unassigned) --</option>
                        <?php if ($userOpts): ?>
                            <?php while ($u = $userOpts->fetch_assoc()): ?>
                                <option value="<?php echo (int)$u['id']; ?>" <?php echo ($u['type'] === 'super' ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($u['type'], ENT_QUOTES, 'UTF-8') . ', ID: ' . (int)$u['id'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" name="confirm_upgrade" value="1" class="btn">Proceed with Upgrade to v<?php echo htmlspecialchars(TARGET_VERSION); ?></button>
            </form>
        </body>
        </html>
        <?php
        $conn->close();
        exit;
    }

    if (!validateCsrfToken()) {
        http_response_code(403);
        outputMessage("Forbidden: Invalid or missing CSRF token.", false);
        $conn->close();
        exit(1);
    }
}

$operatorId = null;
if (!$isCli) {
    if (!empty($_POST['operator_id'])) {
        $operatorId = filter_var($_POST['operator_id'], FILTER_VALIDATE_INT) ?: null;
    }
} else {
    $cliOp = getenv('DEFAULT_OPERATOR_ID');
    if ($cliOp !== false && is_numeric($cliOp)) {
        $operatorId = (int)$cliOp;
    }
}

outputMessage("<strong>Starting database upgrade to version " . TARGET_VERSION . "...</strong>", $isCli);

// 4. Create automatic table backups inside MySQL before modifying schema
$backupTables = [
    'admin_accounts' => DATABASE_PREFIX . 'admin_accounts',
    'users' => DATABASE_PREFIX . 'users',
    'dynamic_qrcodes' => DATABASE_PREFIX . 'dynamic_qrcodes',
    'static_qrcodes' => DATABASE_PREFIX . 'static_qrcodes'
];

foreach ($backupTables as $alias => $tName) {
    $tCheck = $conn->query("SHOW TABLES LIKE '{$tName}'");
    if ($tCheck && $tCheck->num_rows > 0) {
        $backupName = $tName . '_backup_v1';
        $bCheck = $conn->query("SHOW TABLES LIKE '{$backupName}'");
        if (!$bCheck || $bCheck->num_rows === 0) {
            if ($conn->query("CREATE TABLE `{$backupName}` LIKE `{$tName}`") === TRUE) {
                if ($conn->query("INSERT INTO `{$backupName}` SELECT * FROM `{$tName}`") === TRUE) {
                    outputMessage("Snapshot backup created: <code>{$backupName}</code>", $isCli);
                } else {
                    abortMigration($conn, "Failed to copy rows into snapshot table `{$backupName}`: " . $conn->error, $isCli);
                }
            } else {
                abortMigration($conn, "Failed to create snapshot table `{$backupName}`: " . $conn->error, $isCli);
            }
        } else {
            outputMessage("Notice: Snapshot backup `{$backupName}` already exists; preserving existing snapshot.", $isCli);
        }
    }
}

// 5. Apply migrations idempotently
// Rename admin_accounts to users if needed
$tableCheck = $conn->query("SHOW TABLES LIKE '" . DATABASE_PREFIX . "users'");
if ($tableCheck === false) {
    abortMigration($conn, "Failed checking table users: " . $conn->error, $isCli);
}

if ($tableCheck->num_rows > 0) {
    outputMessage("Notice: Table '" . DATABASE_PREFIX . "users' already exists.", $isCli);
} else {
    $oldCheck = $conn->query("SHOW TABLES LIKE '" . DATABASE_PREFIX . "admin_accounts'");
    if ($oldCheck === false) {
        abortMigration($conn, "Failed checking table admin_accounts: " . $conn->error, $isCli);
    }
    if ($oldCheck->num_rows > 0) {
        $sql = "ALTER TABLE " . DATABASE_PREFIX . "admin_accounts RENAME TO " . DATABASE_PREFIX . "users";
        if ($conn->query($sql) === TRUE) {
            outputMessage("The admin_accounts table has been renamed to users successfully.", $isCli);
        } else {
            abortMigration($conn, "Error renaming table admin_accounts: " . $conn->error, $isCli);
        }
    }
}

// Rename column user_name to username if present
$colCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "users LIKE 'user_name'");
if ($colCheck === false) {
    abortMigration($conn, "Failed checking user_name column: " . $conn->error, $isCli);
}
if ($colCheck->num_rows > 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "users CHANGE `user_name` `username` VARCHAR(50) NOT NULL";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The user_name column has been renamed to username successfully.", $isCli);
    } else {
        abortMigration($conn, "Error renaming column user_name: " . $conn->error, $isCli);
    }
}

// Rename column admin_type to type if present
$typeCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "users LIKE 'admin_type'");
if ($typeCheck === false) {
    abortMigration($conn, "Failed checking admin_type column: " . $conn->error, $isCli);
}
if ($typeCheck->num_rows > 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "users CHANGE `admin_type` `type` VARCHAR(10) NOT NULL";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The admin_type column was renamed to type successfully.", $isCli);
    } else {
        abortMigration($conn, "Error renaming column admin_type: " . $conn->error, $isCli);
    }
}

// Add must_change_password column to users if not present
$mcpCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "users LIKE 'must_change_password'");
if ($mcpCheck === false) {
    abortMigration($conn, "Failed checking must_change_password column: " . $conn->error, $isCli);
}
if ($mcpCheck->num_rows === 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "users ADD `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The must_change_password column has been added to users successfully.", $isCli);
    } else {
        abortMigration($conn, "Error adding column must_change_password: " . $conn->error, $isCli);
    }
}

// Add id_owner to dynamic_qrcodes if not present
$dynCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "dynamic_qrcodes LIKE 'id_owner'");
if ($dynCheck === false) {
    abortMigration($conn, "Failed checking id_owner column in dynamic_qrcodes: " . $conn->error, $isCli);
}
if ($dynCheck->num_rows === 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "dynamic_qrcodes ADD `id_owner` INT(25) NULL DEFAULT NULL AFTER `id`";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The id_owner column has been added to dynamic_qrcodes successfully.", $isCli);
    } else {
        abortMigration($conn, "Error adding column id_owner to dynamic_qrcodes: " . $conn->error, $isCli);
    }
}

// Add id_owner to static_qrcodes if not present
$statCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "static_qrcodes LIKE 'id_owner'");
if ($statCheck === false) {
    abortMigration($conn, "Failed checking id_owner column in static_qrcodes: " . $conn->error, $isCli);
}
if ($statCheck->num_rows === 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "static_qrcodes ADD `id_owner` INT(25) NULL DEFAULT NULL AFTER `id`";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The id_owner column has been added to static_qrcodes successfully.", $isCli);
    } else {
        abortMigration($conn, "Error adding column id_owner to static_qrcodes: " . $conn->error, $isCli);
    }
}

// Backfill id_owner for dynamic_qrcodes and static_qrcodes
$usersTable = DATABASE_PREFIX . 'users';
$qrTables = [
    'dynamic_qrcodes' => DATABASE_PREFIX . 'dynamic_qrcodes',
    'static_qrcodes'  => DATABASE_PREFIX . 'static_qrcodes'
];

foreach ($qrTables as $tableKey => $tableName) {
    // 1. Backfill from created_by author where a matching user exists
    $backfillSql = "UPDATE `{$tableName}` t
                    INNER JOIN `{$usersTable}` u ON t.`created_by` = u.`id`
                    SET t.`id_owner` = t.`created_by`
                    WHERE (t.`id_owner` IS NULL OR t.`id_owner` = 0) AND t.`created_by` > 0";
    if ($conn->query($backfillSql) === TRUE) {
        $recovered = $conn->affected_rows;
        if ($recovered > 0) {
            outputMessage("Backfilled {$recovered} records in {$tableKey} from creator ownership data.", $isCli);
        }
    } else {
        abortMigration($conn, "Error backfilling ownership in {$tableKey}: " . $conn->error, $isCli);
    }

    // 2. Explicit operator assignment for remaining ownerless records
    if ($operatorId !== null) {
        $assignStmt = $conn->prepare("UPDATE `{$tableName}` SET `id_owner` = ? WHERE `id_owner` IS NULL OR `id_owner` = 0");
        if (!$assignStmt) {
            abortMigration($conn, "Error preparing operator assignment query for {$tableKey}: " . $conn->error, $isCli);
        }
        if (!$assignStmt->bind_param('i', $operatorId)) {
            $err = $assignStmt->error;
            $assignStmt->close();
            abortMigration($conn, "Error binding parameter for operator assignment in {$tableKey}: " . $err, $isCli);
        }
        if ($assignStmt->execute()) {
            $assigned = $conn->affected_rows;
            if ($assigned > 0) {
                outputMessage("Assigned {$assigned} unowned records in {$tableKey} to operator ID {$operatorId}.", $isCli);
            }
        } else {
            $err = $assignStmt->error;
            $assignStmt->close();
            abortMigration($conn, "Error executing operator assignment in {$tableKey}: " . $err, $isCli);
        }
        $assignStmt->close();
    }

    // 3. Document any remaining ownerless records
    $unownedCheck = $conn->query("SELECT COUNT(*) AS `cnt` FROM `{$tableName}` WHERE `id_owner` IS NULL OR `id_owner` = 0");
    if ($unownedCheck) {
        $cntRow = $unownedCheck->fetch_assoc();
        $unownedCount = (int)$cntRow['cnt'];
        if ($unownedCount > 0) {
            outputMessage("Notice: {$unownedCount} records in {$tableKey} remain unowned (NULL). They can be assigned manually by an administrator.", $isCli);
        }
    }
}

// Normalize legacy static_qrcodes content representation
$staticTable = DATABASE_PREFIX . 'static_qrcodes';
$legacyRows = $conn->query("SELECT `id`, `content` FROM `{$staticTable}` WHERE `content` LIKE '%&lt;strong&gt;%' OR `content` LIKE '%&lt;div%' OR `content` LIKE '%&lt;b&gt;%'");
if ($legacyRows === false) {
    abortMigration($conn, "Error querying legacy static QR records: " . $conn->error, $isCli);
}

if ($legacyRows->num_rows > 0) {
    $upStmt = $conn->prepare("UPDATE `{$staticTable}` SET `content` = ? WHERE `id` = ?");
    if (!$upStmt) {
        abortMigration($conn, "Error preparing static QR content normalization query: " . $conn->error, $isCli);
    }
    $migrated = 0;
    while ($r = $legacyRows->fetch_assoc()) {
        $decoded = htmlspecialchars_decode($r['content'], ENT_QUOTES);
        if (!$upStmt->bind_param('si', $decoded, $r['id'])) {
            $err = $upStmt->error;
            $upStmt->close();
            abortMigration($conn, "Error binding parameters for static QR normalization: " . $err, $isCli);
        }
        if ($upStmt->execute()) {
            $migrated++;
        } else {
            $err = $upStmt->error;
            $upStmt->close();
            abortMigration($conn, "Error executing static QR normalization: " . $err, $isCli);
        }
    }
    $upStmt->close();
    outputMessage("Migrated {$migrated} legacy static QR code content records to standard format.", $isCli);
}

// 6. Record successful upgrade in schema_version table only after all migrations succeed
$insertStmt = $conn->prepare("INSERT INTO `{$versionTable}` (`version`, `applied_at`) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE `applied_at` = NOW()");
if (!$insertStmt) {
    abortMigration($conn, "Error preparing schema version record query: " . $conn->error, $isCli);
}

if (!$insertStmt->bind_param('s', $targetVer)) {
    $err = $insertStmt->error;
    $insertStmt->close();
    abortMigration($conn, "Error binding schema version parameter: " . $err, $isCli);
}

if (!$insertStmt->execute()) {
    $err = $insertStmt->error;
    $insertStmt->close();
    abortMigration($conn, "Error recording schema version: " . $err, $isCli);
}

$insertStmt->close();

outputMessage("<strong>Upgrade completed successfully! Database is now at schema version " . TARGET_VERSION . ".</strong>", $isCli);

$conn->close();
?>
