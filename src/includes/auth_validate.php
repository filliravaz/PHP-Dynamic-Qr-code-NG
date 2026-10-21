<?php

//If User is logged in the session['user_logged_in'] will be set to true

//if user is Not Logged in, redirect to login.php page.
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
	header('Location: login.php');
	exit;
}

// If account is marked to change password, restrict access until password is changed
if (!empty($_SESSION['must_change_password'])) {
	$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
	if ($currentScript !== 'change_password.php' && $currentScript !== 'logout.php') {
		header('Location: change_password.php');
		exit;
	}
}

 ?>
