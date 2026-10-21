<?php

require_once 'config/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	// Validate CSRF token (SEC-07)
	if (!validateCsrfToken()) {
		$_SESSION['login_failure'] = 'Invalid or expired session token. Please try again.';
		header('Location: login.php');
		exit;
	}

	$username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
	$username = is_string($username) ? trim($username) : '';
	$password = (string)($_POST['password'] ?? '');
	$remember = filter_input(INPUT_POST, 'remember');

	$clientIp = getClientIp();

	// Check rate limit: maximum 5 attempts per 15 minutes (SEC-07)
	if (!checkLoginRateLimit($username, $clientIp, 5, 15)) {
		usleep(250000);
		$_SESSION['login_failure'] = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
		header('Location: login.php');
		exit;
	}

	// Get DB instance.
	$db = getDbInstance();

	$db->where('username', $username);
	$row = $db->getOne('users');

	if ($row && !empty($row['password']))
    {
		$db_password = $row['password'];
		$user_id = $row['id'];

		if (password_verify($password, $db_password))
        {
			// Authentication successful: clear failed attempts
			clearLoginAttempts($username, $clientIp);

			session_regenerate_id(true);
			$_SESSION['user_logged_in'] = TRUE;
			$_SESSION['type'] = $row['type'];
			$_SESSION['user_id'] = (int)$row['id'];
			$_SESSION['session_version'] = (int)($row['session_version'] ?? 1);

			if ($remember)
            {
				$series_id = bin2hex(random_bytes(16));
				$remember_token = bin2hex(random_bytes(32));
				$encryted_remember_token = password_hash($remember_token, PASSWORD_DEFAULT);

				$expiry_time = date('Y-m-d H:i:s', strtotime(' + 30 days'));
				$expires = strtotime($expiry_time);

				$isSecure = isHttpsRequest();
				$cookieOptions = [
					'expires' => $expires,
					'path' => '/',
					'secure' => $isSecure,
					'httponly' => true,
					'samesite' => 'Lax'
				];

				setcookie('series_id', $series_id, $cookieOptions);
				setcookie('remember_token', $remember_token, $cookieOptions);

				$db = getDbInstance();
				$db->where ('id', $user_id);

				$update_remember = array(
					'series_id' => $series_id,
					'remember_token' => $encryted_remember_token,
					'expires' => $expiry_time
				);
				$db->update('users', $update_remember);
			}

			if (!empty($row['must_change_password'])) {
				$_SESSION['must_change_password'] = true;
				header('Location: change_password.php');
				exit;
			}

			// Authentication successful redirect user
			header('Location: index.php');
			exit;
		}
        else
        {
			recordLoginAttempt($username, $clientIp);
			usleep(250000);
			$_SESSION['login_failure'] = 'Invalid username or password';
			header('Location: login.php');
			exit;
		}
	}
    else
    {
		recordLoginAttempt($username, $clientIp);
		usleep(250000);
		$_SESSION['login_failure'] = 'Invalid username or password';
		header('Location: login.php');
		exit;
	}
}
else
{
	die('Method Not allowed');
}
