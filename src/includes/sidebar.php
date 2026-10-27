
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="./index.php" class="brand-link">
      <img src="dist/img/Symbol_WhiteBlue.png" alt="Logo" class="brand-image"
           style="opacity: .8">
      <span class="brand-text font-weight-light">Qrcode Generator</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
       <!--Sidebar user panel (optional) 
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="dist/img/avatar5.png" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">Superadmin</a>
        </div>
      </div>

      -->
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column nav-legacy" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
         <li class="nav-item">
           <a href="./index.php" <?php echo (CURRENT_PAGE === 'index.php') ? ' class="nav-link active"' : ' class="nav-link"'; ?>>
             <i class="nav-icon fas fa-tachometer-alt"></i>
             <p>
               Dashboard
             </p>
           </a>
         </li>
          <li <?php echo (in_array(CURRENT_PAGE, ['dynamic_qrcodes.php', 'dynamic_qrcode.php'], true)) ? ' class="nav-item has-treeview menu-open"' : ' class="nav-item has-treeview"'; ?>>
            <a href="#" <?php echo (in_array(CURRENT_PAGE, ['dynamic_qrcodes.php', 'dynamic_qrcode.php'], true)) ? ' class="nav-link active"' : ' class="nav-link"'; ?>>
              <i class="nav-icon fa fa-qrcode"></i>
              <p>
                Dynamic Qr codes
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="./dynamic_qrcodes.php" <?php echo (CURRENT_PAGE === 'dynamic_qrcodes.php') ? ' class="nav-link active"' : ' class="nav-link"'; ?>>
                  <i class="far fa-circle nav-icon"></i>
                  <p>List all</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./dynamic_qrcode.php" <?php echo (CURRENT_PAGE === 'dynamic_qrcode.php') ? ' class="nav-link active"' : ' class="nav-link"'; ?>>
                  <i class="far fa-circle nav-icon"></i>
                  <p>Add new</p>
                </a>
              </li>
            </ul>
          </li>
            <li <?php echo (in_array(CURRENT_PAGE, ['static_qrcodes.php', 'static_qrcode.php'], true)) ? ' class="nav-item has-treeview menu-open"' : ' class="nav-item has-treeview"'; ?>>
                <a href="#" <?php echo (in_array(CURRENT_PAGE, ['static_qrcodes.php', 'static_qrcode.php'], true)) ? ' class="nav-link active"' : ' class="nav-link"'; ?>>
                    <i class="nav-icon fa fa-qrcode"></i>
                    <p>
                        Static Qr codes
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="./static_qrcodes.php" <?php echo (CURRENT_PAGE === 'static_qrcodes.php') ? ' class="nav-link active"' : ' class="nav-link"'; ?>>
                            <i class="far fa-circle nav-icon"></i>
                            <p>List all</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="./static_qrcode.php" <?php echo (CURRENT_PAGE === 'static_qrcode.php') ? ' class="nav-link active"' : ' class="nav-link"'; ?>>
                            <i class="far fa-circle nav-icon"></i>
                            <p>Add new</p>
                        </a>
                    </li>
                </ul>
            </li>
          <?php if (isset($_SESSION['type']) && $_SESSION['type'] === 'super'): ?>
          <li class="nav-item">
            <a href="./users.php" <?php echo (in_array(CURRENT_PAGE, ['users.php', 'user.php'], true)) ? ' class="nav-link active"' : ' class="nav-link"'; ?>>
                <i class="fas fa-users nav-icon"></i>
              <p>Users</p>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>
