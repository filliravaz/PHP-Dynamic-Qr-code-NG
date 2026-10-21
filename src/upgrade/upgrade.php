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

$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME, DATABASE_PORT);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . ($isCli ? "\n" : "<br>"));
}

// 1. Ensure schema_version table exists to track migrations across container restarts
$versionTable = DATABASE_PREFIX . 'schema_version';
$conn->query("CREATE TABLE IF NOT EXISTS `{$versionTable}` (
    `version` VARCHAR(20) NOT NULL,
    `applied_at` DATETIME NOT NULL,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2. Check if TARGET_VERSION has already been applied
$versionStmt = $conn->prepare("SELECT `applied_at` FROM `{$versionTable}` WHERE `version` = ?");
$targetVer = TARGET_VERSION;
$versionStmt->bind_param('s', $targetVer);
$versionStmt->execute();
$versionRes = $versionStmt->get_result();

if ($versionRes && $versionRes->num_rows > 0) {
    $row = $versionRes->fetch_assoc();
    outputMessage("Database schema is already at version " . TARGET_VERSION . " (applied on " . $row['applied_at'] . "). No changes needed.", $isCli);
    $conn->close();
    exit;
}

// 3. If accessed via browser, verify superadmin authentication and require CSRF-protected POST
if (!$isCli) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once dirname(__DIR__) . '/helpers/helpers.php';

    if (empty($_SESSION['user_logged_in']) || empty($_SESSION['type']) || $_SESSION['type'] !== 'super') {
        http_response_code(403);
        outputMessage("Forbidden: Database upgrades via browser require an authenticated super administrator session. <a href='../login.php'>Log In</a>", false);
        $conn->close();
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
        exit;
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
            $conn->query("CREATE TABLE `{$backupName}` AS SELECT * FROM `{$tName}`");
            outputMessage("Snapshot backup created: <code>{$backupName}</code>", $isCli);
        }
    }
}

// 5. Apply migrations idempotently
// Rename admin_accounts to users if needed
$tableCheck = $conn->query("SHOW TABLES LIKE '" . DATABASE_PREFIX . "users'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    outputMessage("Notice: Table '" . DATABASE_PREFIX . "users' already exists.", $isCli);
} else {
    $oldCheck = $conn->query("SHOW TABLES LIKE '" . DATABASE_PREFIX . "admin_accounts'");
    if ($oldCheck && $oldCheck->num_rows > 0) {
        $sql = "ALTER TABLE " . DATABASE_PREFIX . "admin_accounts RENAME TO " . DATABASE_PREFIX . "users";
        if ($conn->query($sql) === TRUE) {
            outputMessage("The admin_accounts table has been renamed to users successfully.", $isCli);
        } else {
            outputMessage("Error renaming table: " . htmlspecialchars($conn->error), $isCli);
        }
    }
}

// Rename column user_name to username if present
$colCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "users LIKE 'user_name'");
if ($colCheck && $colCheck->num_rows > 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "users CHANGE `user_name` `username` VARCHAR(50) NOT NULL";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The user_name column has been renamed to username successfully.", $isCli);
    } else {
        outputMessage("Error renaming column: " . htmlspecialchars($conn->error), $isCli);
    }
}

// Rename column admin_type to type if present
$typeCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "users LIKE 'admin_type'");
if ($typeCheck && $typeCheck->num_rows > 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "users CHANGE `admin_type` `type` VARCHAR(10) NOT NULL";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The admin_type column was renamed to type successfully.", $isCli);
    } else {
        outputMessage("Error renaming column: " . htmlspecialchars($conn->error), $isCli);
    }
}

// Add id_owner to dynamic_qrcodes if not present
$dynCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "dynamic_qrcodes LIKE 'id_owner'");
if ($dynCheck && $dynCheck->num_rows === 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "dynamic_qrcodes ADD `id_owner` INT(25) NULL DEFAULT NULL AFTER `id`";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The id_owner column has been added to dynamic_qrcodes successfully.", $isCli);
    } else {
        outputMessage("Error adding column: " . htmlspecialchars($conn->error), $isCli);
    }
}

// Add id_owner to static_qrcodes if not present
$statCheck = $conn->query("SHOW COLUMNS FROM " . DATABASE_PREFIX . "static_qrcodes LIKE 'id_owner'");
if ($statCheck && $statCheck->num_rows === 0) {
    $sql = "ALTER TABLE " . DATABASE_PREFIX . "static_qrcodes ADD `id_owner` INT(25) NULL DEFAULT NULL AFTER `id`";
    if ($conn->query($sql) === TRUE) {
        outputMessage("The id_owner column has been added to static_qrcodes successfully.", $isCli);
    } else {
        outputMessage("Error adding column: " . htmlspecialchars($conn->error), $isCli);
    }
}

// Normalize legacy static_qrcodes content representation
$staticTable = DATABASE_PREFIX . 'static_qrcodes';
$legacyRows = $conn->query("SELECT `id`, `content` FROM `{$staticTable}` WHERE `content` LIKE '%&lt;strong&gt;%' OR `content` LIKE '%&lt;div%' OR `content` LIKE '%&lt;b&gt;%'");
if ($legacyRows && $legacyRows->num_rows > 0) {
    $upStmt = $conn->prepare("UPDATE `{$staticTable}` SET `content` = ? WHERE `id` = ?");
    $migrated = 0;
    while ($r = $legacyRows->fetch_assoc()) {
        $decoded = htmlspecialchars_decode($r['content'], ENT_QUOTES);
        $upStmt->bind_param('si', $decoded, $r['id']);
        $upStmt->execute();
        $migrated++;
    }
    outputMessage("Migrated {$migrated} legacy static QR code content records to standard format.", $isCli);
}

// 6. Record successful upgrade in schema_version table
$insertStmt = $conn->prepare("INSERT INTO `{$versionTable}` (`version`, `applied_at`) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE `applied_at` = NOW()");
$insertStmt->bind_param('s', $targetVer);
$insertStmt->execute();

outputMessage("<strong>Upgrade completed successfully! Database is now at schema version " . TARGET_VERSION . ".</strong>", $isCli);

$conn->close();
?>
