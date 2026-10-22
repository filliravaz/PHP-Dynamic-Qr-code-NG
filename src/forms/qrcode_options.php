<div class="col-sm-12 mb-3">
    <div class="row">
        <div class="col-6 col-md-3">
            <label>Foreground Color</label>
            <div class="input-group my-colorpicker2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-square" style="color: #000000;"></i></span>
                </div>
                <input type="text" class="form-control input-foreground" name="foreground" value="#000000">
            </div>
        </div>

        <div class="col-6 col-md-3">
            <label>Background Color</label>
            <div class="input-group my-colorpicker2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-square" style="color: #ffffff;"></i></span>
                </div>
                <input type="text" class="form-control input-background" name="background" value="#ffffff">
            </div>
        </div>

        <div class="col-6 col-md-3">
            <label>Precision / ECC</label>
            <select name="level" class="form-control select-level">
                <option value="L">L - Low (7% recovery)</option>
                <option value="M" selected>M - Medium (15% recovery)</option>
                <option value="Q">Q - High (25% recovery)</option>
                <option value="H">H - Best (30% recovery)</option>
            </select>
        </div>

        <div class="col-6 col-md-3">
            <label>Size (px)</label>
            <select name="size" class="form-control select-size">
                <option value="150">150</option>
                <option value="200">200</option>
                <option value="300" selected>300</option>
                <option value="400">400</option>
                <option value="500">500</option>
                <option value="600">600</option>
                <option value="800">800</option>
                <option value="1000">1000</option>
            </select>
        </div>
    </div>
</div>

<div class="col-sm-12 mb-3">
    <div class="card card-secondary card-outline">
        <div class="card-header py-2">
            <h3 class="card-title text-sm"><i class="fas fa-palette mr-1"></i> Advanced Customization & Embedded Emblem / Logo</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body py-3">
            <div class="row">
                <div class="col-12 col-md-4 mb-2">
                    <label class="text-sm">Block / Module Style</label>
                    <select name="round_block" class="form-control select-round-block">
                        <option value="margin" selected>Margin (Standard)</option>
                        <option value="none">Crisp / Compact</option>
                        <option value="enlarge">Enlarge Modules</option>
                        <option value="shrink">Shrink Modules</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-2">
                    <label class="text-sm">Custom Logo Upload</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input input-logo-file" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                        <label class="custom-file-label text-truncate">Choose image...</label>
                    </div>
                    <small class="text-muted">PNG, JPG, SVG or WebP (max 5MB)</small>
                </div>
                <div class="col-12 col-md-4 mb-2">
                    <label class="text-sm">Logo Scale: <strong class="logo-scale-value">20%</strong></label>
                    <input type="range" class="custom-range input-logo-scale" name="logo_scale" min="15" max="35" value="20" step="1">
                    <small class="text-muted">Auto-elevates ECC to High for scanability</small>
                </div>
            </div>

            <div class="mt-2">
                <label class="text-sm d-block mb-1">Or Pick Preset Emblem:</label>
                <input type="hidden" name="preset_logo" class="input-preset-logo" value="">
                <div class="btn-group-toggle d-flex flex-wrap preset-logo-group" data-toggle="buttons">
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo active" data-preset="">
                        <i class="fas fa-ban mr-1"></i> None
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="whatsapp">
                        <img src="dist/img/presets/whatsapp.svg" width="14" height="14" class="mr-1" alt="WhatsApp"> WhatsApp
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="telegram">
                        <img src="dist/img/presets/telegram.svg" width="14" height="14" class="mr-1" alt="Telegram"> Telegram
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="youtube">
                        <img src="dist/img/presets/youtube.svg" width="14" height="14" class="mr-1" alt="YouTube"> YouTube
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="instagram">
                        <img src="dist/img/presets/instagram.svg" width="14" height="14" class="mr-1" alt="Instagram"> Instagram
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="x-twitter">
                        <img src="dist/img/presets/x-twitter.svg" width="14" height="14" class="mr-1" alt="X"> X (Twitter)
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="linkedin">
                        <img src="dist/img/presets/linkedin.svg" width="14" height="14" class="mr-1" alt="LinkedIn"> LinkedIn
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="paypal">
                        <img src="dist/img/presets/paypal.svg" width="14" height="14" class="mr-1" alt="PayPal"> PayPal
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="bitcoin">
                        <img src="dist/img/presets/bitcoin.svg" width="14" height="14" class="mr-1" alt="Bitcoin"> Bitcoin
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="ethereum">
                        <img src="dist/img/presets/ethereum.svg" width="14" height="14" class="mr-1" alt="Ethereum"> Ethereum
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="wifi">
                        <img src="dist/img/presets/wifi.svg" width="14" height="14" class="mr-1" alt="Wi-Fi"> Wi-Fi
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="link">
                        <img src="dist/img/presets/link.svg" width="14" height="14" class="mr-1" alt="Link"> Link
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="phone">
                        <img src="dist/img/presets/phone.svg" width="14" height="14" class="mr-1" alt="Phone"> Phone
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 btn-preset-logo" data-preset="email">
                        <img src="dist/img/presets/email.svg" width="14" height="14" class="mr-1" alt="Email"> Email
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-sm-12 mb-2">
    <div class="row">
        <div class="col-sm-6">
            <div class="form-group">
                <label>Filename *</label>
                <input type="text" name="filename" value="" placeholder="My QR Code" class="form-control input-filename" required="required">
            </div>
        </div>

        <div class="col-sm-3 col-6">
            <div class="form-group">
                <label>Format *</label>
                <select name="format" class="form-control select-format" required="required">
                    <option value="png" selected>PNG</option>
                    <option value="svg">SVG</option>
                    <option value="webp">WebP</option>
                    <option value="jpeg">JPEG</option>
                </select>
            </div>
        </div>

        <?php if(isset($_SESSION['type']) && $_SESSION['type'] === 'super'): ?>
        <div class="col-sm-3 col-6">
            <div class="form-group">
                <label>Owner *</label>
                <select name="id_owner" class="form-control">
                    <option value="" selected>All</option>
                    <?php
                    require_once BASE_PATH . '/lib/Users/Users.php';
                    $users_instance = new Users();
                    $users = $users_instance->getAllUsers();
                    foreach ($users as $user) {
                    ?>
                    <option value="<?php echo (int)$user["id"];?>"><?php echo htmlspecialchars($user["username"], ENT_QUOTES, 'UTF-8');?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <?php else: ?>
        <input type="hidden" name="id_owner" value="<?php echo (int)($_SESSION["user_id"] ?? 0);?>"/>
        <?php endif; ?>
    </div>
</div>
