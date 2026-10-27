
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="fas fa-user"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <div class="dropdown-divider"></div>
          <!--<a href="#" class="dropdown-item">
            <i class="fas fa-user"></i> Profile
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fa fa-cog"></i> Settings
          </a>-->
          <a href="./change_password.php" class="dropdown-item">
            <i class="fas fa-key"></i> Change Password
          </a>
          <div class="dropdown-divider"></div>
          <form action="./logout.php" method="POST" class="m-0 p-0">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>"/>
            <button type="submit" class="dropdown-item" style="cursor: pointer;">
              <i class="fas fa-sign-out-alt"></i> Logout
            </button>
          </form>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button"><i
            class="fas fa-th-large"></i></a>
      </li>
    </ul>
  </nav>
