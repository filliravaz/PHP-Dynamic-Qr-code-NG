<form class="form" action="static_qrcode.php?type=paypal" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
<!-- Input forms -->
<div class="col-sm-12 mb-2">
    <div class="row">

    <div class="col-6 col-md-4">
        <div class="form-group">
            <label>Payment type *</label>
            <select name="payment_type" class="form-control">
                  <option value="_click" selected>Buy now</option>
                  <option value="_cart">Add to cart</option>
                  <option value="_donations">Donations</option>
            </select>
        </div>
    </div>
    
    <div class="col-6 col-md-4">
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="" placeholder="" class="form-control">
        </div>
    </div>
    </div>
</div>

<div class="col-sm-12 mb-2">
    <div class="row">
        
        <div class="col-6 col-md-4">
            <div class="form-group">
                <label>Item name *</label>
                <input type="text" name="item_name" value="" placeholder="" class="form-control">
            </div>
        </div>
        
        <div class="col-6 col-md-4">
            <div class="form-group">
                <label>Item ID</label>
                <input type="text" name="item_id" value="" placeholder="" class="form-control">
            </div>
        </div>
        
    </div>
</div>

<div class="col-sm-12 mb-2">
    <div class="row">
        
        <div class="col-6 col-md-2">
            <div class="form-group">
                <label>Amount *</label>
                <input type="number" step="0.01" min="0" name="amount" id="paypal_amount" value="" placeholder="" class="form-control">
            </div>
        </div>
        
        <div class="col-6 col-md-2">
        <div class="form-group">
            <label>Currency *</label>
            <select name="currency" id="paypal_currency" class="form-control">
                  <option value="USD" selected>USD</option>
                  <option value="EUR">EUR</option>
                  <option value="AUD">AUD</option>
                  <option value="CAD">CAD</option>
                  <option value="CZK">CZK</option>
                  <option value="DKK">DKK</option>
                  <option value="HKD">HKD</option>
                  <option value="HUF">HUF</option>
                  <option value="JPY">JPY</option>
                  <option value="NOK">NOK</option>
                  <option value="NZD">NZD</option>
                  <option value="PLN">PLN</option>
                  <option value="GBP">GBP</option>
                  <option value="SGD">SGD</option>
                  <option value="SEK">SEK</option>
            </select>
        </div>
        </div>
    
        <div class="col-6 col-md-2">
            <div class="form-group">
                <label>Shipping </label>
                <input type="number" step="0.01" min="0" name="shipping" id="paypal_shipping" value="" placeholder="" class="form-control">
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="form-group">
                <label>Tax rate </label>
                <div class="input-group">
                    <input type="text" name="tax_rate" value="" placeholder="" class="form-control">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-percent"></i></span>
                    </div>
                </div>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    var currencySelect = document.getElementById('paypal_currency');
    var amountInput = document.getElementById('paypal_amount');
    var shippingInput = document.getElementById('paypal_shipping');
    if (!currencySelect || !amountInput || !shippingInput) return;

    var zeroDecimalCurrencies = ['HUF', 'JPY'];
    function updatePrecision() {
        var curr = currencySelect.value;
        var step = zeroDecimalCurrencies.indexOf(curr) !== -1 ? '1' : '0.01';
        amountInput.setAttribute('step', step);
        shippingInput.setAttribute('step', step);
    }
    currencySelect.addEventListener('change', updatePrecision);
    updatePrecision();
});
</script>
