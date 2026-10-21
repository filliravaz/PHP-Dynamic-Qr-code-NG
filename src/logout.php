<?php
require_once './config/config.php';
session_start();

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
