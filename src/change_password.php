<?php
require_once 'config/config.php';
session_start();
require_once BASE_PATH . '/includes/auth_validate.php';

$errors = [];
$isFirstLogin = !empty($_SESSION['must_change_password']);

$pwAttempts = (int)($_SESSION['pwd_change_attempts'] ?? 0);
$pwStart = (int)($_SESSION['pwd_change_start'] ?? time());

// If the 15-minute window has expired, clear session counters and reinitialize
if ((time() - $pwStart) >= 900) {
    unset($_SESSION['pwd_change_attempts'], $_SESSION['pwd_change_start']);
    $pwAttempts = 0;
    $pwStart = time();
}

$isRateLimited = ($pwAttempts >= 5);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isRateLimited) {
        usleep(250000);
        $errors[] = 'Too many failed password change attempts. Please wait 15 minutes before trying again.';
    } elseif (!validateCsrfToken()) {
        $errors[] = 'Invalid or expired CSRF token. Please try again.';
    } else {
        $current_password = (string)($_POST['current_password'] ?? '');
        $new_password     = (string)($_POST['new_password'] ?? '');
        $confirm_password = (string)($_POST['confirm_password'] ?? '');

        $db = getDbInstance();
        $db->where('id', (int)$_SESSION['user_id']);
        $user = $db->getOne('users');

        if (!$user || !password_verify($current_password, $user['password'])) {
            $_SESSION['pwd_change_attempts'] = $pwAttempts + 1;
            if (empty($_SESSION['pwd_change_start'])) {
                $_SESSION['pwd_change_start'] = time();
            }
            usleep(250000);
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        } elseif (password_verify($new_password, $user['password'])) {
            $errors[] = 'New password cannot be the same as your current or default password.';
        } else {
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            $newSessionVersion = (int)($user['session_version'] ?? 1) + 1;
            $db->where('id', (int)$_SESSION['user_id']);
            $updated = $db->update('users', [
                'password' => $newHash,
                'must_change_password' => 0,
                'series_id' => null,
                'remember_token' => null,
                'expires' => null,
                'session_version' => $newSessionVersion
            ]);

            if ($updated) {
                clearAuthCookie();
                session_regenerate_id(true);
                $_SESSION['session_version'] = $newSessionVersion;
                unset($_SESSION['must_change_password'], $_SESSION['pwd_change_attempts'], $_SESSION['pwd_change_start']);
                $_SESSION['success'] = 'Password changed successfully! You may now access the application.';
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password - Qrcode Generator</title>
    <?php include './includes/head.php'; ?>
</head>
<body class="hold-transition login-page" style="min-height: 512.391px;">
<div class="login-box" style="width: 440px;">
    <div class="login-logo">
        <img src="dist/img/DynamicQRCode_Original.png" style="width: 90%; height: auto;" alt="Logo">
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <h3><strong>Change Password</strong></h3>
        </div>
        <div class="card-body login-card-body">
            <?php if ($isFirstLogin): ?>
                <div class="alert alert-warning alert-dismissible">
                    <i class="icon fas fa-exclamation-triangle"></i>
                    <strong>Action Required:</strong> For security reasons, you must change the default password before proceeding.
                </div>
            <?php else: ?>
                <p class="login-box-msg">Enter your current password and choose a strong new password.</p>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="change_password.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group mb-3">
                    <label for="current_password">Current Password *</label>
                    <div class="input-group">
                        <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Current Password" required autocomplete="current-password">
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="new_password">New Password (min 8 characters) *</label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="New Password" required minlength="8" autocomplete="new-password">
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-key"></span></div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="confirm_password">Confirm New Password *</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm New Password" required minlength="8" autocomplete="new-password">
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-check"></span></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-8">
                        <a href="logout.php?csrf_token=<?php echo htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-default btn-block text-muted"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include './includes/footer.php'; ?>
</body>
</html>
