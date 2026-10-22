<?php
require_once 'config/config.php';
session_start();

// If User has already logged in, redirect to dashboard page.
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === TRUE)
{
	header('Location: index.php');
	exit;
}

// If user has previously selected "remember me option": 
if (isset($_COOKIE['series_id']) && isset($_COOKIE['remember_token']))
{
	// Get user credentials from cookies.
	$series_id = filter_var($_COOKIE['series_id']);
	$remember_token = filter_var($_COOKIE['remember_token']);
	$db = getDbInstance();
	// Get user By series ID: 
	$db->where('series_id', $series_id);
	$row = $db->getOne('users');

	if ($db->count >= 1 && !empty($row['remember_token']))
	{
		// User found. verify remember token
		if (password_verify($remember_token, $row['remember_token']))
        {
            $expires = strtotime($row['expires']);

            if (time() > $expires) {
                clearAuthCookie();
                header('Location: login.php');
                exit;
            }

            // SEC-04: Rotate remember-me token on each automatic login
            $new_series_id = bin2hex(random_bytes(16));
            $new_remember_token = bin2hex(random_bytes(32));
            $encrypted_remember_token = password_hash($new_remember_token, PASSWORD_DEFAULT);
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

            setcookie('series_id', $new_series_id, $cookieOptions);
            setcookie('remember_token', $new_remember_token, $cookieOptions);

            $db->where('id', (int)$row['id']);
            $db->update('users', [
                'series_id' => $new_series_id,
                'remember_token' => $encrypted_remember_token,
                'expires' => $expiry_time
            ]);

            session_regenerate_id(true);
			$_SESSION['user_logged_in'] = TRUE;
            $_SESSION['user_id'] = (int)$row['id'];
			$_SESSION['type'] = $row['type'];
			$_SESSION['session_version'] = (int)($row['session_version'] ?? 1);

			if (!empty($row['must_change_password'])) {
				$_SESSION['must_change_password'] = true;
				header('Location: change_password.php');
				exit;
			}

			header('Location: index.php');
			exit;
		}
		else
		{
			clearAuthCookie();
			header('Location: login.php');
			exit;
		}
	}
	else
	{
		clearAuthCookie();
		header('Location: login.php');
		exit;
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<title>Login - Qrcode Generator</title>
<?php include './includes/head.php'; ?>

<body class="login-page" style="min-height: 512.391px;">
    <div class="login-box">
  <div class="login-logo">
    <img src="dist/img/DynamicQRCode_Original.png" style="width: 95%; height: 95%">
  </div>
  
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <form method="POST" action="authenticate.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="input-group mb-3">
          <input type="text" name="username" class="form-control" placeholder="Username" required="required">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fa fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required="required">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input name="remember" type="checkbox" id="remember">
              <label for="remember">
                Remember Me
              </label>
            </div>
          </div>
          <!-- /.col -->
          
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          </div>
          <!-- /.col -->
        </div>
        
      </form>
      
      <?php if (isset($_SESSION['login_failure'])): ?>
          <br>
          <div class="text-center mb-3">
              <div class="card-body p-0">
				<div class="alert alert-danger alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
					<?php
					echo htmlspecialchars($_SESSION['login_failure'], ENT_QUOTES, 'UTF-8');
					unset($_SESSION['login_failure']);
					?>
				</div>
			   </div>	
			</div>	
				<?php endif; ?>
      
      
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<!-- jQuery (SEC-18) -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>

</body>
</html>
