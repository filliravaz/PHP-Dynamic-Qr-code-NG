<?php
require_once 'config/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "GET" || !isset($_GET['id'])) {
    http_response_code(400);
    exit("Method not allowed. Check id parameter");
}

// Validation and sanitization of the input
$rawId = filter_input(INPUT_GET, 'id', FILTER_UNSAFE_RAW);
$id = is_string($rawId) ? trim(strip_tags($rawId)) : '';

// SEC-07: Validate identifier format (max 32 alphanumeric/dash/underscore chars)
if ($id === '' || !preg_match('/^[a-zA-Z0-9_\-]{1,32}$/', $id)) {
    http_response_code(400);
    exit("Invalid ID parameter");
}

$db = getDbInstance();

// Prepared statement lookup using indexed identifier column (SEC-07)
$db->where("identifier", $id);
$qrcode = $db->getOne("dynamic_qrcodes");

if (!$qrcode) {
    http_response_code(404);
    exit("QR code not found");
}

// BUG-05: Check state before incrementing scan counter
if (($qrcode['state'] ?? '') === 'enable') {
    $rawLink = trim($qrcode['link'] ?? '');
    // Enforce explicit scheme allowlisting (http/https only) and issue 302 HTTP redirect
    if (preg_match('/^https?:\/\//i', $rawLink) && filter_var($rawLink, FILTER_VALIDATE_URL)) {
        // Increment scan count on enabled and valid link using primary key (SEC-07 & BUG-05)
        $db->where("id", (int)$qrcode['id']);
        $db->update('dynamic_qrcodes', [
            'scan' => $db->inc(1)
        ]);

        header("Location: " . $rawLink, true, 302);
        exit;
    } else {
        http_response_code(400);
        echo 'Invalid URL';
        exit;
    }
} else {
    http_response_code(403);
    echo 'Disabled link';
    exit;
}
