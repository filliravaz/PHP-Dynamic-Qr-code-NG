<form class="form" action="static_qrcode.php?type=telegram" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
    <!-- Input forms -->
    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Telegram Username or Channel *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">@</span>
                        </div>
                        <input type="text" name="username" class="form-control" placeholder="username" required="required" pattern="^@?[A-Za-z0-9_]+$" title="Letters, numbers, and underscores only">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Pre-filled Message (Optional)</label>
                    <textarea class="form-control" name="message" rows="2" placeholder="Hi! I got your contact from your QR code."></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-6 col-md-3">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</form>
