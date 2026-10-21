<?php
require_once 'config/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "GET" || !isset($_GET['id'])) {
    die("Method not allowed. Check id parameter");
}

// Validation and sanitization of the input UPDATE to php 8.3
$id = filter_input(INPUT_GET, 'id', FILTER_UNSAFE_RAW);
$id = trim(strip_tags($id));

if (!$id) {
    die("Invalid ID parameter");
}

$db = getDbInstance();

// Using prepared statements to avoid SQL injections
$db->where("identifier", $id);
$qrcode = $db->getOne("dynamic_qrcodes");

if (!$qrcode) {
    die("QR code not found");
}

$data = array(
    'scan' => $db->inc(1)
);

$db->where("identifier", $id);
if (!$db->update('dynamic_qrcodes', $data)) {
    die("Failed to update scan count");
}

if ($qrcode['state'] === 'enable') {
    $rawLink = trim($qrcode['link'] ?? '');
    // SEC-08: Enforce explicit scheme allowlisting (http/https only) and issue 302 HTTP redirect
    if (preg_match('/^https?:\/\//i', $rawLink) && filter_var($rawLink, FILTER_VALIDATE_URL)) {
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
?>
