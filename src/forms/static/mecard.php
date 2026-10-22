<form class="form" action="static_qrcode.php?type=mecard" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
    <!-- Input forms -->
    <div class="col-sm-12 mb-2">
        <div class="alert alert-info py-2 px-3 small">
            <i class="fas fa-id-card mr-1"></i> <strong>MeCard</strong> is a compact mobile address book standard, widely supported across iOS and Android camera scanners.
        </div>
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Doe, John" required="required">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Phonetic / Reading (Optional)</label>
                    <input type="text" name="reading" class="form-control" placeholder="Kana / phonetic reading">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="tel" class="form-control" placeholder="+1234567890">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john.doe@example.com">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Website URL</label>
                    <input type="url" name="url" class="form-control" placeholder="https://example.com">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" placeholder="123 Main St, Springfield">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Memo / Note</label>
                    <input type="text" name="memo" class="form-control" placeholder="Sales Director at Acme Corp">
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
