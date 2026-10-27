<?php
require_once 'config/config.php';
session_start();
require_once BASE_PATH . '/includes/auth_validate.php';
require_once BASE_PATH . '/lib/StaticQrcode/StaticQrcode.php';

$db = getDbInstance();
$static_qrcode = new StaticQrcode();

$prefix = defined('DATABASE_PREFIX') ? DATABASE_PREFIX : '';
$order_table = $prefix . 'static_qrcodes';
$allowed_columns = array('id', 'id_owner', 'filename', 'type', 'content', 'qrcode', 'created_at', 'updated_at');
$select = array(
    $order_table . '.id',
    $order_table . '.id_owner',
    $order_table . '.filename',
    $order_table . '.type',
    $order_table . '.content',
    $order_table . '.qrcode',
    $order_table . '.created_at',
    $order_table . '.updated_at',
    'u.username AS owner_username'
);
$search_fields = array('filename', 'type', 'content');
require_once BASE_PATH . '/includes/search_order.php';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$page || $page < 1) {
    $page = 1;
}
$db->pageLimit = 15;

if($_SESSION['type'] !== 'super') {
    $db->where($order_table . '.id_owner', (int)$_SESSION['user_id']);
}

// PERF-01: Join users table to resolve owner username in a single query
$db->join('users u', 'u.id = ' . $order_table . '.id_owner', 'LEFT');
$rows = $db->arraybuilder()->paginate('static_qrcodes', $page, $select);
$total_pages = $db->totalPages;
?>


<!DOCTYPE html>
<html lang="en">
    <title>Qrcode Generator</title>
    <head>
    <?php include './includes/head.php'; ?>
    </head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <!-- Navbar -->
  <?php include './includes/navbar.php'; ?>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <?php include './includes/sidebar.php'; ?>
    <!-- /.Main Sidebar Container -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Static Qr codes</h1>
          </div><!-- /.col -->
          
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item">
                  <a href="static_qrcode.php" class="btn btn-success"><i class="fa fa-plus"></i> Add new</a>
                </li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div><!-- /.content-header -->
    
    <!-- Flash message-->
    <?php include BASE_PATH . '/includes/flash_messages.php'; ?>
    <!-- /.Flash message-->
            
    <!-- Filters -->
    <?php $options = $static_qrcode->setOrderingValues();
          include BASE_PATH . '/forms/filters.php'; ?>  
    <!-- /.Filters-->
    
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
          
        <!-- Table -->
        <?php include BASE_PATH . '/forms/table_static.php'; ?>
        <!-- /.Table -->
    
        </div><!-- /.container-fluid -->
    </section>
  </div><!-- /.content-wrapper -->

    <!-- Footer and scripts -->
    <?php include './includes/footer.php'; ?>
    <!-- /.Footer and scripts -->
</body>
</html>
