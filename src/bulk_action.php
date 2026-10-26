<?php
require_once 'config/config.php';
session_start();
require_once BASE_PATH . '/includes/auth_validate.php';
require_once BASE_PATH . '/lib/DynamicQrcode/DynamicQrcode.php';
require_once BASE_PATH . '/lib/StaticQrcode/StaticQrcode.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDbInstance();
    $json = json_decode(file_get_contents('php://input'), true);

    if (!is_array($json) || !isset($json["action"])) {
        http_response_code(400);
        echo json_encode([
            'data' => 'Invalid request payload.',
            'status' => 400,
            'csrf_token' => getCsrfToken()
        ]);
        exit();
    }

    $csrfToken = $json['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validateCsrfToken($csrfToken, false)) {
        http_response_code(403);
        echo json_encode([
            'data' => 'Invalid or missing CSRF token.',
            'status' => 403
        ]);
        exit();
    }

    if (!isset($json['type']) || !in_array($json['type'], ['dynamic', 'static'], true)) {
        http_response_code(400);
        echo json_encode([
            'data' => 'Invalid or missing type field in the request.',
            'status' => 400,
            'csrf_token' => getCsrfToken()
        ]);
        exit();
    }

    $type = $json['type'];
    $params = isset($json['params']) && is_array($json['params']) ? $json['params'] : [];

    if (count($params) === 0) {
        http_response_code(400);
        echo json_encode([
            'data' => 'No qrcodes were selected.',
            'status' => 400,
            'csrf_token' => getCsrfToken()
        ]);
        exit();
    }

    if ($json["action"] === "download") {
        $files = [];

        foreach ($params as $param) {
            $paramId = filter_var($param, FILTER_VALIDATE_INT);
            if (!$paramId) {
                continue;
            }

            $db->where('id', $paramId);
            if ($_SESSION['type'] !== 'super') {
                $db->where('id_owner', (int)$_SESSION['user_id']);
            }

            $row = $db->getOne("{$type}_qrcodes");
            if ($row && !empty($row['qrcode'])) {
                $safeName = basename($row['qrcode']);
                $filePath = SAVED_QRCODE_DIRECTORY . $safeName;
                if (file_exists($filePath)) {
                    $files[] = $filePath;
                }
            }
        }

        if (count($files) === 0) {
            http_response_code(400);
            echo json_encode([
                'data' => 'No downloadable files found.',
                'status' => 400,
                'csrf_token' => getCsrfToken()
            ]);
            exit();
        }

        $zip = new ZipArchive();
        $uniqid = bin2hex(random_bytes(16));
        $zipDir = SAVED_QRCODE_DIRECTORY . 'zip';
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        // Clean up archives older than 1 hour to prevent storage exhaustion (SEC-03)
        $now = time();
        $existingZips = scandir($zipDir);
        if ($existingZips !== false) {
            foreach ($existingZips as $ez) {
                if ($ez === '.' || $ez === '..' || $ez === '.htaccess') continue;
                $targetFile = $zipDir . '/' . $ez;
                if (is_file($targetFile) && ($now - filemtime($targetFile) > 3600)) {
                    @unlink($targetFile);
                }
            }
        }

        $relative_dir = $zipDir . '/qrcodes_' . $uniqid . '.zip';
        @unlink($relative_dir);
        $url_path = 'download.php?zip=' . $uniqid;

        if ($zip->open($relative_dir, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $download_file = file_get_contents($file);
                $zip->addFromString(basename($file), $download_file);
            }
            $zip->close();

            regenerateCsrfToken();
            echo json_encode([
                'data' => $url_path,
                'status' => 200,
                'csrf_token' => getCsrfToken()
            ]);
            exit();
        } else {
            http_response_code(500);
            echo json_encode([
                'data' => 'Failed to create zip archive.',
                'status' => 500,
                'csrf_token' => getCsrfToken()
            ]);
            exit();
        }
    } else if ($json["action"] === "delete") {
        if ($type === "dynamic") {
            $instance = new DynamicQrcode();
        } else {
            $instance = new StaticQrcode();
        }

        foreach ($params as $param) {
            $paramId = filter_var($param, FILTER_VALIDATE_INT);
            if ($paramId) {
                $instance->deleteQrcode($paramId, true);
            }
        }

        regenerateCsrfToken();
        echo json_encode([
            'action' => "delete",
            'data' => "Qrcode deleted",
            'status' => 200,
            'csrf_token' => getCsrfToken()
        ]);
        exit();
    } else {
        http_response_code(400);
        echo json_encode([
            'data' => 'Action not allowed.',
            'status' => 400,
            'csrf_token' => getCsrfToken()
        ]);
        exit();
    }
} else {
    http_response_code(405);
    exit('Method not allowed.');
}
