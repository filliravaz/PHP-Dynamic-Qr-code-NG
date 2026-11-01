<?php
require_once dirname(__DIR__) . '/config/environment.php';
require_once dirname(__DIR__) . '/lib/Database/MigrationManager.php';

$isCli = (php_sapi_name() === 'cli');
define('TARGET_VERSION', MigrationManager::getLatestVersion());

/**
 * Outputs a message formatted appropriately for CLI or web browser contexts.
 *
 * @param string $message The message text (may contain HTML for browser context).
 * @param bool $isCli True if running in CLI environment, false for web server.
 * @return void
 */
function outputMessage($message, $isCli) {
    if ($isCli) {
        echo strip_tags($message) . "\n";
    } else {
        echo $message . "<br>\n";
    }
}

/**
 * Halts migration execution, outputs an error message, and closes database connection.
 *
 * @param mysqli|null $conn Database connection to close if active.
 * @param string $message Error message description.
 * @param bool $isCli True if running in CLI environment, false for web server.
 * @return never
 */
function abortMigration($conn, $message, $isCli) {
    outputMessage("Error: " . $message, $isCli);
    if ($conn instanceof mysqli) {
        $conn->close();
    }
    exit(1);
}

// Browser access requires authenticated super administrator before establishing DB connection or querying state (SEC-09)
if (!$isCli) {
    require_once dirname(__DIR__) . '/config/config.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /**
     * Legacy installation note:
     * Installations running an older database schema where the user table is still named `admin_accounts`
     * (with `user_name` / `admin_type` columns) cannot authenticate via the 2.0 web login flow and must
     * execute the upgrade via the CLI (`php src/upgrade/upgrade.php` on host, or `docker compose run --rm php-dynamic-qrcode php upgrade/upgrade.php` in Docker).
     */
    if (empty($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || empty($_SESSION['user_id']) || empty($_SESSION['type']) || $_SESSION['type'] !== 'super') {
        http_response_code(403);
        outputMessage("Forbidden: Database upgrades via browser require an authenticated super administrator session. <a href='../login.php'>Log In</a><br><br><em>Note: If upgrading a legacy installation still using <code>admin_accounts</code>, please run the upgrade via CLI (<code>php src/upgrade/upgrade.php</code> or in Docker: <code>docker compose run --rm php-dynamic-qrcode php upgrade/upgrade.php</code>).</em>", false);
        exit(1);
    }

    // Schema-compatible session validation (SEC-09):
    // Validate session against database without requiring 2.0.0 columns (must_change_password, session_version)
    // so legacy installations can authenticate and run the migration in the browser.
    $authConn = MigrationManager::createConnection();
    if (!$authConn) {
        http_response_code(500);
        outputMessage("Database connection failed during authentication check.", false);
        exit(1);
    }

    $usersTable = DATABASE_PREFIX . 'users';
    $uCheck = $authConn->query("SHOW TABLES LIKE '{$usersTable}'");
    if ($uCheck && $uCheck->num_rows > 0) {
        $uStmt = $authConn->prepare("SELECT `id`, `type` FROM `{$usersTable}` WHERE `id` = ?");
        if ($uStmt) {
            $sessUid = (int)$_SESSION['user_id'];
            $uStmt->bind_param('i', $sessUid);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            $uRow = $uRes ? $uRes->fetch_assoc() : null;
            $uStmt->close();

            if (!$uRow || ($uRow['type'] ?? '') !== 'super') {
                $authConn->close();
                http_response_code(403);
                outputMessage("Forbidden: Your session is no longer an active super administrator.", false);
                exit(1);
            }

            // If session_version column exists, validate it
            $svColCheck = $authConn->query("SHOW COLUMNS FROM `{$usersTable}` LIKE 'session_version'");
            if ($svColCheck && $svColCheck->num_rows > 0) {
                $svStmt = $authConn->prepare("SELECT `session_version` FROM `{$usersTable}` WHERE `id` = ?");
                if ($svStmt) {
                    $svStmt->bind_param('i', $sessUid);
                    $svStmt->execute();
                    $svRes = $svStmt->get_result();
                    $svRow = $svRes ? $svRes->fetch_assoc() : null;
                    $svStmt->close();
                    if ($svRow && isset($_SESSION['session_version']) && (int)$svRow['session_version'] !== (int)$_SESSION['session_version']) {
                        $authConn->close();
                        http_response_code(403);
                        outputMessage("Forbidden: Session expired. Please log in again.", false);
                        exit(1);
                    }
                }
            }
        }
    }
    $authConn->close();
}

$conn = MigrationManager::createConnection();

if (!$conn) {
    die("Connection failed: Unable to connect to database at " . DATABASE_HOST . ":" . DATABASE_PORT . ($isCli ? "\n" : "<br>"));
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

// 3. If accessed via browser, present upgrade confirmation form or require CSRF-protected POST
if (!$isCli) {
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

$logger = function($message) use ($isCli) {
    outputMessage($message, $isCli);
};

// Execute all pending migrations via MigrationManager
try {
    MigrationManager::runMigrations($conn, (string)DATABASE_PREFIX, $logger, $operatorId);
} catch (\Throwable $e) {
    abortMigration($conn, $e->getMessage(), $isCli);
}

outputMessage("<strong>Upgrade completed successfully! Database is now at schema version " . TARGET_VERSION . ".</strong>", $isCli);

$conn->close();
?>
