<fieldset>
    <div class="col-sm-12 mb-3">
        <div class="row">
            <div class="col-12 col-md-8">
                <div class="form-group">
                    <label for="link">Destination Target URL *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-link"></i></span>
                        </div>
                        <input type="url" pattern="https?://.*" name="link" value="" placeholder="https://example.com" class="form-control" required="required" id="link">
                    </div>
                    <small class="form-text text-muted">The dynamic QR code will redirect visitors to this URL (can be changed anytime later without re-printing).</small>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="identifier">Redirect Identifier</label>
                    <input type="text" class="form-control" placeholder="Auto-generated unique slug" disabled>
                    <small class="form-text text-muted">A random redirect slug is assigned automatically upon creation. <em>Disclaimer: This preview may differ from the final QR code (the final code encodes an auto-generated slug for the integrated shortener).</em></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Color, Size & Precision Options -->
    <div class="col-sm-12 mb-3">
        <div class="row">
            <div class="col-6 col-md-3">
                <label>Foreground Color *</label>
                <div class="input-group my-colorpicker2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-square" style="color: #000000;"></i></span>
                    </div>
                    <input type="text" class="form-control input-foreground" id="foreground" name="foreground" value="#000000" required="required">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label>Background Color *</label>
                <div class="input-group my-colorpicker2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-square" style="color: #ffffff;"></i></span>
                    </div>
                    <input type="text" class="form-control input-background" id="background" name="background" value="#ffffff" required="required">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label for="level">Precision / ECC</label>
                <select name="level" id="level" class="form-control select-level">
                    <option value="L">L - Low (7% recovery)</option>
                    <option value="M" selected>M - Medium (15% recovery)</option>
                    <option value="Q">Q - High (25% recovery)</option>
                    <option value="H">H - Best (30% recovery)</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label for="size">Size (px)</label>
                <select name="size" id="size" class="form-control select-size">
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

    <!-- Color Styling & Gradient Section -->
    <div class="col-sm-12 mb-3">
        <div class="card card-secondary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title text-sm"><i class="fas fa-fill-drip mr-1"></i> Color Styling, Gradients & Eye Markers</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-12 col-md-3 mb-2">
                        <label class="text-sm">Color Mode</label>
                        <select name="gradient_type" class="form-control select-gradient-type" onchange="window.updateQrGradientVisibility && window.updateQrGradientVisibility()">
                            <option value="none" selected>Solid Single Color</option>
                            <option value="linear">Linear Gradient (Dual Color)</option>
                            <option value="radial">Radial Gradient (Center Outward)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 mb-2 gradient-color-group" style="display: none;">
                        <label class="text-sm">Gradient Secondary Color</label>
                        <div class="input-group my-colorpicker2">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-square" style="color: #007bff;"></i></span>
                            </div>
                            <input type="text" class="form-control input-gradient-color" name="gradient_color" value="#007bff">
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-2 gradient-rotation-group" style="display: none;">
                        <div class="d-flex justify-content-between">
                            <label class="text-sm">Rotation Angle</label>
                            <span class="badge badge-info gradient-angle-val text-xs align-self-center">45°</span>
                        </div>
                        <input type="range" class="custom-range input-gradient-angle" name="gradient_angle" min="0" max="360" step="5" value="45">
                        <div class="btn-group btn-group-toggle w-100 mt-1">
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-quick-angle" data-angle="0">0°</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-quick-angle" data-angle="45">45°</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-quick-angle" data-angle="90">90°</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-quick-angle" data-angle="135">135°</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-quick-angle" data-angle="180">180°</button>
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-2 gradient-balance-group" style="display: none;">
                        <div class="d-flex justify-content-between">
                            <label class="text-sm">Gradient Balance</label>
                            <span class="badge badge-secondary gradient-balance-val text-xs align-self-center">50%</span>
                        </div>
                        <input type="range" class="custom-range input-gradient-balance" name="gradient_balance" min="10" max="90" step="1" value="50">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 10px;">
                            <span>Color 1</span>
                            <span>Equal (50%)</span>
                            <span>Color 2</span>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-2 mt-2">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input toggle-custom-eyes" id="toggle_eyes_dyn" name="custom_eyes_enabled" value="1" onchange="window.updateQrGradientVisibility && window.updateQrGradientVisibility()">
                        <label class="custom-control-label text-sm font-weight-normal toggle-custom-eyes-label" for="toggle_eyes_dyn" style="cursor: pointer;">
                            Customize Eye / Corner Marker Colors independently
                        </label>
                    </div>
                    <div class="row custom-eyes-group" style="display: none;">
                        <div class="col-12 col-md-6 mb-2">
                            <label class="text-sm">Outer Eye / Corner Frame Color</label>
                            <div class="input-group my-colorpicker2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-square" style="color: #000000;"></i></span>
                                </div>
                                <input type="text" class="form-control input-eye-outer" name="eye_outer" value="#000000">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-2">
                            <label class="text-sm">Inner Eye / Pupil Dot Color</label>
                            <div class="input-group my-colorpicker2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-square" style="color: #000000;"></i></span>
                                </div>
                                <input type="text" class="form-control input-eye-inner" name="eye_inner" value="#000000">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Customization & Logo -->
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
                    <div class="col-12 col-md-3 mb-2">
                        <label class="text-sm">Block / Module Style</label>
                        <select name="round_block" id="round_block" class="form-control select-round-block">
                            <option value="margin" selected>Square (Standard)</option>
                            <option value="shrink">Dots / Circles</option>
                            <option value="rounded">Rounded Modules</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 mb-2">
                        <label class="text-sm">Emblem Shape</label>
                        <select name="logo_shape" id="logo_shape" class="form-control select-logo-shape">
                            <option value="circle" selected>Circle / Round</option>
                            <option value="rounded">Rounded Square</option>
                            <option value="square">Square</option>
                            <option value="none">None (Transparent)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 mb-2">
                        <label class="text-sm">Custom Logo Upload</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input input-logo-file" name="logo_file" id="logo_file" accept="image/png,image/jpeg,image/webp">
                            <label class="custom-file-label text-truncate" for="logo_file">Choose image...</label>
                        </div>
                        <small class="text-muted">PNG, JPG or WebP (max 5MB)</small>
                    </div>
                    <div class="col-12 col-md-3 mb-2">
                        <label class="text-sm">Logo Scale: <strong class="logo-scale-value">20%</strong></label>
                        <input type="range" class="custom-range input-logo-scale" name="logo_scale" id="logo_scale" min="15" max="35" value="20" step="1">
                        <small class="text-muted">Auto-elevates ECC to High for scanability</small>
                    </div>
                </div>

                <div class="mt-2">
                    <label class="text-sm d-block mb-1">Or Pick Preset Emblem:</label>
                    <input type="hidden" name="preset_logo" id="preset_logo" class="input-preset-logo" value="">
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

    <!-- Filename, Format & Owner -->
    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="filename">Filename *</label>
                    <input type="text" name="filename" value="" placeholder="My Dynamic QR Code" class="form-control error input-filename" required="required" id="filename">
                </div>
            </div>

            <div class="col-sm-3 col-6">
                <div class="form-group">
                    <label for="format">Format *</label>
                    <select name="format" id="format" class="form-control select-format" required="required">
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
                    <label for="id_owner">Owner *</label>
                    <select name="id_owner" id="id_owner" class="form-control">
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

    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
</fieldset>
