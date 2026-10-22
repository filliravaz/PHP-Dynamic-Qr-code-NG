<form class="form" action="static_qrcode.php?type=pix" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
    <!-- Input forms -->
    <div class="col-sm-12 mb-2">
        <div class="alert alert-info py-2 px-3 small">
            <i class="fas fa-info-circle mr-1"></i> Standard Brazilian Pix QR Code (EMV BR Code compliant with Banco Central do Brasil).
        </div>
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Pix Key (CPF, CNPJ, Email, Phone, EVP) *</label>
                    <input type="text" name="pix_key" class="form-control" placeholder="user@domain.com or +5511999999999" required="required">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Receiver / Merchant Name *</label>
                    <input type="text" name="merchant_name" class="form-control" placeholder="Maria Silva" maxlength="25" required="required">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="merchant_city" class="form-control" placeholder="SAO PAULO" maxlength="15" required="required">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Amount (BRL, Optional)</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">R$</span>
                        </div>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="25.00">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Transaction ID / Identifier (TxID, Optional)</label>
                    <input type="text" name="txid" class="form-control" placeholder="REF12345" maxlength="25">
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
