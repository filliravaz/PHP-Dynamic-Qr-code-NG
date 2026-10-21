<?php
require_once './config/config.php';
session_start();

$userId = $_SESSION['user_id'] ?? null;
$seriesId = $_COOKIE['series_id'] ?? null;

// SEC-04: Revoke persistent remember-me session in the database
if ($userId || $seriesId) {
    try {
        $db = getDbInstance();
        if ($userId) {
            $db->where('id', (int)$userId);
        } else {
            $db->where('series_id', filter_var($seriesId));
        }
        $db->update('users', [
            'series_id' => null,
            'remember_token' => null,
            'expires' => null
        ]);
    } catch (\Throwable $e) {
        // Continue logout cleanup
    }
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

if (isset($_COOKIE['series_id']) || isset($_COOKIE['remember_token'])) {
	clearAuthCookie();
}

header('Location: login.php');
exit;
