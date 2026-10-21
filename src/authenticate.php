<?php

require_once 'config/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$username = filter_input(INPUT_POST, 'username');
	$password = filter_input(INPUT_POST, 'password');
	$remember = filter_input(INPUT_POST, 'remember');

	// Get DB instance.
	$db = getDbInstance();

	$db->where('username', $username);
	$row = $db->getOne('users');

	if ($db->count >= 1)
    {
		$db_password = $row['password'];
		$user_id = $row['id'];

		if (password_verify($password, $db_password))
        {
			session_regenerate_id(true);
			$_SESSION['user_logged_in'] = TRUE;
			$_SESSION['type'] = $row['type'];
			$_SESSION['user_id'] = (int)$row['id'];

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
		}
        else
        {
			$_SESSION['login_failure'] = 'Invalid username or password';
			header('Location: login.php');
		}
		exit;
	}
    else
    {
		$_SESSION['login_failure'] = 'Invalid username or password';
		header('Location: login.php');
		exit;
	}
}
else
{
	die('Method Not allowed');
}
