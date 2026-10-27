<?php
require_once 'config/config.php';
session_start();
require_once BASE_PATH.'/includes/auth_validate.php';
require_once BASE_PATH . '/lib/StaticQrcode/StaticQrcode.php';

$static_qrcode_instance = new StaticQrcode();

$edit = false;
if($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["edit"]) && $_GET["edit"] == "true" && isset($_GET["id"])) {
    $edit = true;
    $static_qrcode = $static_qrcode_instance->getQrcode($_GET["id"]);
    if ($_SESSION['type'] !== 'super' && (empty($static_qrcode['id_owner']) || (int)$static_qrcode['id_owner'] !== (int)$_SESSION['user_id'])) {
        $_SESSION['failure'] = "You cannot edit this qrcode because it belongs to another user";
        header('Location: static_qrcodes.php');
        exit();
    }
}

if($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validateCsrfToken()) {
        $_SESSION['failure'] = "Invalid or missing CSRF token";
        header('Location: static_qrcodes.php');
        exit();
    }

    if(isset($_POST["del_id"])) {
        $static_qrcode_instance->deleteQrcode($_POST["del_id"]);
    } else if(isset($_POST["edit"])) {
        if(
            isset($_POST["filename"]) &&
            isset($_POST["id_owner"]) &&
            isset($_POST["id"])
        )
            $static_qrcode_instance->editQrcode($_POST);
    } else {
        // CSRF token is validated above for all static QR type submissions
        // Each form template under src/forms/static/ provides a hidden csrf_token field
        $type = $_GET['type'] ?? '';
        if (!is_string($type)) {
            $_SESSION['failure'] = 'Invalid input provided.';
            header('Location: static_qrcodes.php');
            exit();
        }
        $typeFields = [
            'text'     => ['text'],
            'email'    => ['email', 'subject', 'message'],
            'phone'    => ['country_code', 'phone_number'],
            'sms'      => ['country_code', 'phone_number', 'message'],
            'whatsapp' => ['country_code', 'phone_number', 'message'],
            'skype'    => ['skype_username'],
            'location' => ['latitude', 'longitude'],
            'vcard'    => ['full_name', 'nickname', 'email', 'website', 'phone', 'home_phone', 'work_phone', 'company', 'role', 'categories', 'note', 'photo', 'address', 'city', 'post_code', 'state'],
            'event'    => ['title', 'start', 'end', 'timezone', 'location', 'description', 'url'],
            'bookmark' => ['url', 'title'],
            'wifi'     => ['encryption', 'ssid', 'password'],
            'paypal'   => ['payment_type', 'email', 'item_name', 'item_id', 'amount', 'currency', 'shipping', 'tax_rate'],
            'bitcoin'  => ['address', 'amount', 'label', 'message'],
            '2fa'      => ['algorithms', 'secret', 'label', 'issuer'],
            'telegram' => ['username', 'message'],
            'social'   => ['platform', 'username', 'custom_url'],
            'crypto'   => ['currency', 'address', 'amount', 'message'],
            'sepa'     => ['name', 'iban', 'bic', 'amount', 'remittance'],
            'pix'      => ['pix_key', 'merchant_name', 'merchant_city', 'amount', 'txid'],
            'meeting'  => ['platform', 'meeting_id', 'password', 'url'],
            'mecard'   => ['name', 'reading', 'tel', 'email', 'memo', 'address', 'url'],
            'medical'  => ['name', 'blood_type', 'allergies', 'medications', 'emergency_contact', 'notes'],
        ];

        if (isset($typeFields[$type])) {
            foreach ($typeFields[$type] as $field) {
                if (isset($_POST[$field]) && !is_scalar($_POST[$field])) {
                    $_SESSION['failure'] = 'Invalid input provided.';
                    header('Location: static_qrcodes.php');
                    exit();
                }
            }
        }

        switch($type){
            case 'text':         $static_qrcode_instance->textQrcode($_POST['text'] ?? '');
                break;

            case 'email':        $static_qrcode_instance->emailQrcode($_POST['email'] ?? '', $_POST['subject'] ?? '', $_POST['message'] ?? '');
                break;

            case 'phone':        $static_qrcode_instance->phoneQrcode($_POST['country_code'] ?? '', $_POST['phone_number'] ?? '');
                break;

            case 'sms':          $static_qrcode_instance->smsQrcode($_POST['country_code'] ?? '', $_POST['phone_number'] ?? '', $_POST['message'] ?? '');
                break;

            case 'whatsapp':     $static_qrcode_instance->whatsappQrcode($_POST['country_code'] ?? '', $_POST['phone_number'] ?? '', $_POST['message'] ?? '');
                break;

            case 'skype':        $static_qrcode_instance->skypeQrcode($_POST['skype_username'] ?? '');
                break;

            case 'location':     $static_qrcode_instance->locationQrcode($_POST['latitude'] ?? '', $_POST['longitude'] ?? '');
                break;

            case 'vcard':        $static_qrcode_instance->vcardQrcode($_POST['full_name'] ?? '', $_POST['nickname'] ?? '', $_POST['email'] ?? '', $_POST['website'] ?? '', $_POST['phone'] ?? '', $_POST['home_phone'] ?? '', $_POST['work_phone'] ?? '', $_POST['company'] ?? '', $_POST['role'] ?? '', $_POST['categories'] ?? '', $_POST['note'] ?? '', $_POST['photo'] ?? '', $_POST['address'] ?? '', $_POST['city'] ?? '', $_POST['post_code'] ?? '', $_POST['state'] ?? '');
                break;

            case 'event':        $static_qrcode_instance->eventQrcode($_POST['title'] ?? '', $_POST['start'] ?? '', $_POST['end'] ?? '', $_POST['timezone'] ?? '', $_POST['location'] ?? '', $_POST['description'] ?? '', $_POST['url'] ?? '');
                break;

            case 'bookmark':     $static_qrcode_instance->bookmarkQrcode($_POST['url'] ?? '', $_POST['title'] ?? '');
                break;

            case 'wifi':         $static_qrcode_instance->wifiQrcode($_POST['encryption'] ?? '', $_POST['ssid'] ?? '', $_POST['password'] ?? '');
                break;

            case 'paypal':       $static_qrcode_instance->paypalQrcode($_POST['payment_type'] ?? '', $_POST['email'] ?? '', $_POST['item_name'] ?? '', $_POST['item_id'] ?? '', $_POST['amount'] ?? '', $_POST['currency'] ?? '', $_POST['shipping'] ?? '', $_POST['tax_rate'] ?? '');
                break;

            case 'bitcoin':      $static_qrcode_instance->bitcoinQrcode($_POST['address'] ?? '', $_POST['amount'] ?? '', $_POST['label'] ?? '', $_POST['message'] ?? '');
                break;

            case '2fa':          $static_qrcode_instance->twofaQrcode($_POST['algorithms'] ?? '', $_POST['secret'] ?? '', rawurlencode($_POST['label'] ?? ''), rawurlencode($_POST['issuer'] ?? ''));
                break;

            case 'telegram':     $static_qrcode_instance->telegramQrcode($_POST['username'] ?? '', $_POST['message'] ?? '');
                break;

            case 'social':       $static_qrcode_instance->socialQrcode($_POST['platform'] ?? '', $_POST['username'] ?? '', $_POST['custom_url'] ?? '');
                break;

            case 'crypto':       $static_qrcode_instance->cryptoQrcode($_POST['currency'] ?? '', $_POST['address'] ?? '', $_POST['amount'] ?? '', $_POST['message'] ?? '');
                break;

            case 'sepa':         $static_qrcode_instance->sepaQrcode($_POST['name'] ?? '', $_POST['iban'] ?? '', $_POST['bic'] ?? '', $_POST['amount'] ?? '', $_POST['remittance'] ?? '');
                break;

            case 'pix':          $static_qrcode_instance->pixQrcode($_POST['pix_key'] ?? '', $_POST['merchant_name'] ?? '', $_POST['merchant_city'] ?? '', $_POST['amount'] ?? '', $_POST['txid'] ?? '');
                break;

            case 'meeting':      $static_qrcode_instance->meetingQrcode($_POST['platform'] ?? '', $_POST['meeting_id'] ?? '', $_POST['password'] ?? '', $_POST['url'] ?? '');
                break;

            case 'mecard':       $static_qrcode_instance->mecardQrcode($_POST['name'] ?? '', $_POST['reading'] ?? '', $_POST['tel'] ?? '', $_POST['email'] ?? '', $_POST['memo'] ?? '', $_POST['address'] ?? '', $_POST['url'] ?? '');
                break;

            case 'medical':      $static_qrcode_instance->medicalQrcode($_POST['name'] ?? '', $_POST['blood_type'] ?? '', $_POST['allergies'] ?? '', $_POST['medications'] ?? '', $_POST['emergency_contact'] ?? '', $_POST['notes'] ?? '');
                break;
        }
    }
}

// PERF-02: Pre-fetch users list once for all static QR sub-form tabs
if (isset($_SESSION['type']) && $_SESSION['type'] === 'super') {
    require_once BASE_PATH . '/lib/Users/Users.php';
    $allUsers = (new Users())->getAllUsers();
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
                            <h3 class="card-title font-weight-bold"><i class="fas fa-qrcode mr-2"></i><?php echo ($edit) ? "Edit Static QR Code" : "Static QR Code Generator"; ?></h3>
                        </div>
                        <?php if($edit) {?>
                            <form class="form" action="" method="post" id="static_form" enctype="multipart/form-data">
                                <div class="card-body">
                                    <?php include BASE_PATH . '/forms/form_static_edit.php';?>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> Submit</button>
                                </div>
                            </form>
                        <?php } else { ?>
                            <div class="card-body p-2 p-md-3">
                                <?php include BASE_PATH . '/forms/form_static_add.php'; ?>
                            </div>
                        <?php } ?>
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
<script src="dist/js/qr-preview.js?v=<?php echo urlencode($appVersionInfo['tag'] ?? '2.3.0'); ?>"></script>

<!-- Page script -->
<script type="text/javascript">
$(document).ready(function(){
   if ($('#static_form').length && $.fn.validate) {
   $('#static_form').validate({
       rules: {
            filename: {
                required: true,
            },
        }
    });
   }
});
</script>

    <script>
        $(function () {

            //Colorpicker
            $('.my-colorpicker1').colorpicker()
            //color picker with addon
            $('.my-colorpicker2').colorpicker()

            $('.my-colorpicker2').on('colorpickerChange', function(event) {
                $(this).find('.fa-square').css('color', event.color.toString());
            });

            if ($('#start').length && typeof moment === 'function') {
                $('#start').daterangepicker({
                timePicker: true,
                timePicker24Hour: true,
                singleDatePicker: true,
                showDropdowns: true,
                minYear: parseInt(moment().subtract(2, 'years').format('YYYY'), 10),
                maxYear: parseInt(moment().add(8, 'years').format('YYYY'), 10),
                locale: {
                    format: 'YYYY-MM-DD HH:mm'
                }
            });
            }

            if ($('#end').length && typeof moment === 'function') {
                $('#end').daterangepicker({
                timePicker: true,
                timePicker24Hour: true,
                singleDatePicker: true,
                showDropdowns: true,
                minYear: parseInt(moment().subtract(2, 'years').format('YYYY'), 10),
                maxYear: parseInt(moment().add(8, 'years').format('YYYY'), 10),
                locale: {
                    format: 'YYYY-MM-DD HH:mm'
                }
            });
            }

        })
    </script>
</body>
</html>