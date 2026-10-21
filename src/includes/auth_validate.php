<?php

// If user is not logged in, redirect to login.php page.
if (empty($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || empty($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

// Validate session_version against database record to reject outdated sessions
$db = getDbInstance();
$db->where('id', (int)$_SESSION['user_id']);
$authUser = $db->getOne('users', ['id', 'session_version', 'must_change_password', 'type']);

if (!$authUser) {
	clearAuthCookie();
	$_SESSION = [];
	if (session_status() === PHP_SESSION_ACTIVE) {
		session_destroy();
	}
	header('Location: login.php');
	exit;
}

// Synchronize session role with current database state
$_SESSION['type'] = $authUser['type'];

$currentSessionVersion = (int)($authUser['session_version'] ?? 1);
$activeSessionVersion  = isset($_SESSION['session_version']) ? (int)$_SESSION['session_version'] : 0;

if ($activeSessionVersion !== $currentSessionVersion) {
	// Session was invalidated (e.g. password changed on another device or by admin)
	clearAuthCookie();
	$_SESSION = [];
	if (session_status() === PHP_SESSION_ACTIVE) {
		session_destroy();
	}
	header('Location: login.php');
	exit;
}

// If account is marked to change password, restrict access until password is changed
if (!empty($authUser['must_change_password'])) {
	$_SESSION['must_change_password'] = true;
	$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
	if ($currentScript !== 'change_password.php' && $currentScript !== 'logout.php') {
		header('Location: change_password.php');
		exit;
	}
} else {
	unset($_SESSION['must_change_password']);
}
