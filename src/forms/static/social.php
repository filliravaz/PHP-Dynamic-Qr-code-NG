<form class="form" action="static_qrcode.php?type=social" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
    <!-- Input forms -->
    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-12 col-md-3">
                <div class="form-group">
                    <label>Platform *</label>
                    <select name="platform" class="form-control" id="social-platform-select">
                        <option value="youtube">YouTube</option>
                        <option value="instagram">Instagram</option>
                        <option value="twitter">X / Twitter</option>
                        <option value="linkedin">LinkedIn</option>
                        <option value="tiktok">TikTok</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Handle / Username *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">@</span>
                        </div>
                        <input type="text" name="username" class="form-control" placeholder="username">
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-5">
                <div class="form-group">
                    <label>Or Full Profile URL (Optional)</label>
                    <input type="url" name="custom_url" class="form-control" placeholder="https://instagram.com/myprofile">
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
