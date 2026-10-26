<form class="form" action="static_qrcode.php?type=crypto" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
    <!-- Input forms -->
    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-12 col-md-3">
                <div class="form-group">
                    <label>Cryptocurrency *</label>
                    <select name="currency" class="form-control" id="crypto-currency-select">
                        <option value="ethereum">Ethereum (ETH)</option>
                        <option value="solana">Solana (SOL)</option>
                        <option value="monero">Monero (XMR)</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Wallet Address *</label>
                    <input type="text" name="address" class="form-control" placeholder="0x... or Solana/Monero public key" required="required">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="form-group">
                    <label>Amount (Optional)</label>
                    <input type="text" name="amount" class="form-control" placeholder="0.05">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Payment Message / Note (Optional)</label>
                    <input type="text" name="message" class="form-control" placeholder="Invoice #1042">
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
