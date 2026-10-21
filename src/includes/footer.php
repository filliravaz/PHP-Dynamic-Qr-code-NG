<?php
$appVersionInfo = function_exists('getAppVersionInfo') ? getAppVersionInfo() : [
    'is_nightly' => false,
    'channel'    => 'LTS',
    'version'    => '2.3.0',
    'commit'     => '',
    'tag'        => '2.3.0',
];
?>
<!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

  <!-- Main Footer -->
  <footer class="main-footer text-sm">
    <strong><a href="https://github.com/filliravaz/PHP-Dynamic-Qr-code-NG" target="_blank">PHP-Dynamic-Qr-code-NG</a></strong> • Maintained by Filippo Ravazzini (originally by Giandonato Inverso)
    <div class="float-right d-none d-sm-inline-block">
      <?php if (!empty($appVersionInfo['is_nightly'])): ?>
        <span class="badge badge-warning text-dark mr-1">Nightly</span> <b>Build</b> <?php if (!empty($appVersionInfo['commit'])): ?><a href="https://github.com/filliravaz/PHP-Dynamic-Qr-code-NG/commit/<?php echo urlencode($appVersionInfo['commit']); ?>" target="_blank" class="text-reset font-weight-bold"><?php echo htmlspecialchars($appVersionInfo['commit']); ?></a><?php else: ?>nightly<?php endif; ?>
      <?php else: ?>
        <b>Version</b> <?php echo htmlspecialchars($appVersionInfo['version']); ?>
      <?php endif; ?>
    </div>
  </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>
<!-- AdminLTE Custom-->
<script src="dist/js/custom.js?nocache=<?php print rand();?>"></script>
<!-- Color picker -->
<script src="plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<!-- date-range-picker -->
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<!-- Overlay scrollbar -->
<script type="text/javascript" src="plugins/overlayScrollbars/js/OverlayScrollbars.js"></script>
