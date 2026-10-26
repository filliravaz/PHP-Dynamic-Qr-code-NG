<form class="form" action="static_qrcode.php?type=medical" method="post" id="static_form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
    <!-- Input forms -->
    <div class="col-sm-12 mb-2">
        <div class="alert alert-danger py-2 px-3 small">
            <i class="fas fa-heartbeat mr-1"></i> <strong>Medical ID</strong> encodes crucial emergency medical details to help first responders and caregivers in an emergency.
        </div>
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label>Full Patient / Person Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Jane Doe" required="required">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type" class="form-control">
                        <option value="">Unknown / Not Specified</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-5">
                <div class="form-group">
                    <label>Emergency Contact (Name & Phone) *</label>
                    <input type="text" name="emergency_contact" class="form-control" placeholder="John Doe (Spouse) +1 555-0199" required="required">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Known Allergies (Food, Meds, Insects)</label>
                    <input type="text" name="allergies" class="form-control" placeholder="Penicillin, Peanuts, Bee stings">
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label>Current Medications</label>
                    <input type="text" name="medications" class="form-control" placeholder="Insulin, Aspirin 81mg">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-8">
                <div class="form-group">
                    <label>Critical Medical Notes / Conditions</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Type 1 Diabetic, wears pacemaker, organ donor"></textarea>
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
