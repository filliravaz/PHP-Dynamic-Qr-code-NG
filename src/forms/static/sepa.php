<form class="form" action="static_qrcode.php?type=sepa" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
    <!-- Input forms -->
    <div class="col-sm-12 mb-2">
        <div class="alert alert-info py-2 px-3 small">
            <i class="fas fa-info-circle mr-1"></i> Standard European EPC Credit Transfer QR Code (EPC069-08), scannable by European banking applications.
        </div>
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Beneficiary Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="John Doe or Acme Inc." maxlength="70" required="required">
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>IBAN *</label>
                    <input type="text" name="iban" class="form-control" placeholder="DE89 3704 0044 0532 0130 00" required="required">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>BIC / SWIFT (Optional)</label>
                    <input type="text" name="bic" class="form-control" placeholder="DEUTDEDDFXX" maxlength="11">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="form-group">
                    <label>Amount (EUR, Optional)</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">€</span>
                        </div>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="49.99">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-5">
                <div class="form-group">
                    <label>Remittance / Reason for Payment</label>
                    <input type="text" name="remittance" class="form-control" placeholder="Invoice 2026-081" maxlength="140">
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
