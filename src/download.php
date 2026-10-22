<?php
require_once 'config/config.php';
session_start();
require_once BASE_PATH . '/includes/auth_validate.php';

// Cleanup zip files older than 1 hour in saved_qrcode/zip/
$zipDir = SAVED_QRCODE_DIRECTORY . 'zip';
if (is_dir($zipDir)) {
    $now = time();
    $files = scandir($zipDir);
    if ($files !== false) {
        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || $f === '.htaccess') {
                continue;
            }
            $target = $zipDir . '/' . $f;
            if (is_file($target) && ($now - filemtime($target) > 3600)) {
                @unlink($target);
            }
        }
    }
}

// 1. Bulk zip archive download
if (isset($_GET['zip'])) {
    $zipId = (string)$_GET['zip'];
    if (!preg_match('/^[a-f0-9]{32}$/i', $zipId)) {
        http_response_code(400);
        exit('Invalid zip identifier format.');
    }

    $zipPath = $zipDir . '/qrcodes_' . $zipId . '.zip';
    if (!file_exists($zipPath) || !is_file($zipPath)) {
        http_response_code(404);
        exit('Zip archive not found or expired.');
    }

    $realZipDir = realpath($zipDir);
    $realZipPath = realpath($zipPath);
    if ($realZipDir === false || $realZipPath === false || strpos($realZipPath, $realZipDir) !== 0) {
        http_response_code(403);
        exit('Access denied.');
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="qrcodes_' . $zipId . '.zip"');
    header('Content-Length: ' . filesize($realZipPath));
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: no-store, private');

    // Clean output buffer before streaming
    while (ob_get_level()) {
        ob_end_clean();
    }
    readfile($realZipPath);
    // Delete temporary zip file immediately after streaming (SEC-03)
    @unlink($realZipPath);
    exit;
}

// 2. Individual QR code viewing or downloading
$type = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : '';
$id   = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!in_array($type, ['dynamic', 'static'], true) || !$id) {
    http_response_code(400);
    exit('Invalid or missing request parameters.');
}

$db = getDbInstance();
$db->where('id', $id);
if ($_SESSION['type'] !== 'super') {
    $db->where('id_owner', (int)$_SESSION['user_id']);
}

$row = $db->getOne("{$type}_qrcodes");
if (!$row || empty($row['qrcode'])) {
    http_response_code(404);
    exit('QR code not found or access denied.');
}

$safeFilename = basename($row['qrcode']);
$filePath = SAVED_QRCODE_DIRECTORY . $safeFilename;

if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    exit('QR code file not found.');
}

$realStorageDir = realpath(SAVED_QRCODE_DIRECTORY);
$realFilePath   = realpath($filePath);
if ($realStorageDir === false || $realFilePath === false || strpos($realFilePath, $realStorageDir) !== 0) {
    http_response_code(403);
    exit('Access denied.');
}

$ext = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));
$mimes = [
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'svg'  => 'image/svg+xml',
    'webp' => 'image/webp',
    'eps'  => 'application/postscript'
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

$isView = isset($_GET['view']);
$disposition = $isView ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
if ($ext === 'svg') {
    // Prevent script execution if SVG is loaded directly in browser (RES-03)
    header("Content-Security-Policy: default-src 'none'");
}
header('Content-Disposition: ' . $disposition . '; filename="' . $safeFilename . '"');
header('Content-Length: ' . filesize($realFilePath));
header('Cache-Control: no-store, private');

while (ob_get_level()) {
    ob_end_clean();
}
readfile($realFilePath);
exit;
