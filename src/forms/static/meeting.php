<form class="form" action="static_qrcode.php?type=meeting" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
    <!-- Input forms -->
    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-12 col-md-3">
                <div class="form-group">
                    <label>Platform *</label>
                    <select name="platform" class="form-control" id="meeting-platform-select">
                        <option value="zoom">Zoom</option>
                        <option value="teams">Microsoft Teams</option>
                        <option value="facetime">FaceTime</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Meeting ID / Apple ID / Phone *</label>
                    <input type="text" name="meeting_id" class="form-control" placeholder="123 456 7890 or user@apple.com" required="required">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="form-group">
                    <label>Passcode (Zoom, Optional)</label>
                    <input type="text" name="password" class="form-control" placeholder="123456">
                </div>
            </div>
            <div class="col-12 col-md-8">
                <div class="form-group">
                    <label>Or Full Meeting Invitation URL (Teams / Custom)</label>
                    <input type="url" name="url" class="form-control" placeholder="https://teams.microsoft.com/l/meetup-join/...">
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
