<?php
require_once 'config/config.php';
session_start();
require_once BASE_PATH.'/includes/auth_validate.php';
require_once BASE_PATH . '/lib/DynamicQrcode/DynamicQrcode.php';

$dynamic_qrcode_instance = new DynamicQrcode();

$edit = false;
if($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["edit"]) && $_GET["edit"] == "true" && isset($_GET["id"])) {
    $edit = true;
    $dynamic_qrcode = $dynamic_qrcode_instance->getQrcode($_GET["id"]);
    if ($_SESSION['type'] !== 'super' && (empty($dynamic_qrcode['id_owner']) || (int)$dynamic_qrcode['id_owner'] !== (int)$_SESSION['user_id'])) {
        $_SESSION['failure'] = "You cannot edit this qrcode because it belongs to another user";
        header('Location: dynamic_qrcodes.php');
        exit();
    }
}

if($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validateCsrfToken()) {
        $_SESSION['failure'] = "Invalid or missing CSRF token";
        header('Location: dynamic_qrcodes.php');
        exit();
    }

    if(isset($_POST["del_id"])) {
        $dynamic_qrcode_instance->deleteQrcode($_POST["del_id"]);
    } else if(isset($_POST["edit"])) {
        if(
            isset($_POST["identifier"]) &&
            isset($_POST["filename"]) &&
            isset($_POST["link"]) &&
            isset($_POST["state"]) &&
            isset($_POST["id_owner"]) &&
            isset($_POST["id"])
        )
            $dynamic_qrcode_instance->editQrcode($_POST);
    } else {
        if(
            isset($_POST["foreground"]) &&
            isset($_POST["background"]) &&
            isset($_POST["link"]) &&
            isset($_POST["filename"]) &&
            isset($_POST["format"]) &&
            isset($_POST["id_owner"])
        )
            $dynamic_qrcode_instance->addQrcode($_POST);
    }
}
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
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            
          <div class="col-sm-6">
              <h1 class="m-0 text-dark"><?php echo ($edit) ? "Edit" : "Add"; ?> Qr code</h1>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    
    <!-- Flash messages -->
    <?php include BASE_PATH.'/includes/flash_messages.php'; ?>
    <!-- /.Flash messages -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="<?php echo $edit ? 'col-12' : 'col-xl-8 col-lg-7'; ?>">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold"><i class="fas fa-qrcode mr-2"></i><?php echo ($edit) ? "Edit Dynamic QR Code" : "Dynamic QR Code Configuration"; ?></h3>
                        </div>
                        <form class="form" action="" method="post" id="dynamic_form" enctype="multipart/form-data">
                            <div class="card-body">
                                <?php
                                    if($edit)
                                        include BASE_PATH.'/forms/form_dynamic_edit.php';
                                    else
                                        include BASE_PATH . '/forms/form_dynamic_add.php';
                                ?>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> Submit & Generate</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php if (!$edit): ?>
                <div class="col-xl-4 col-lg-5">
                    <?php include BASE_PATH . '/includes/qr_live_preview_card.php'; ?>
                </div>
                <?php endif; ?>
            </div>
        </div><!--/. container-fluid -->
    </section><!-- /.content -->
  </div><!-- /.content-wrapper -->

<!-- Footer and scripts -->
<?php include './includes/footer.php'; ?>
<script src="dist/js/qr-preview.js"></script>

<!-- Page script -->
<script type="text/javascript">
$(document).ready(function(){
   $('#dynamic_form').validate({
       rules: {
            filename: {
                required: true,
            },
            link: {
                required: true,
                minlength: 3
            },   
        }
    });
});
</script>

<script>
    $(function () {

        //Colorpicker
        $('.my-colorpicker1').colorpicker()
        //color picker with addon
        $('.my-colorpicker2').colorpicker()

        $('.my-colorpicker2').on('colorpickerChange', function(event) {
            $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
        });

    })
</script>
</body>
</html>