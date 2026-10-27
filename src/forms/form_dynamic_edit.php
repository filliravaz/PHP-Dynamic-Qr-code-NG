<fieldset>
    <div class="col-sm-4">
        <div class="form-group">
            <label for="identifier">Redirect identifier</label>
            <input type="text" name="identifier" value="<?php echo htmlspecialchars($edit ? $dynamic_qrcode['identifier'] : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Identifier" class="form-control" id="identifier" readonly>
        </div>
    </div>
    

    <div class="col-sm-4">
        <div class="form-group">
            <label for="filename">Filename *</label>
            <p>N.B. You can change the name of the file visible in the table, however a new qr code will NOT be generated</p>
            <input type="text" name="filename" value="<?php echo htmlspecialchars($edit ? $dynamic_qrcode['filename'] : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Filename" class="form-control" required="required" id = "filename">
        </div> 
    </div>
    
    <div class="col-sm-4">
        <div class="form-group">
            <label for="link">URL *</label>
            <input type="url" pattern="https?://.*" name="link" value="<?php echo htmlspecialchars($edit ? $dynamic_qrcode['link'] : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Link" class="form-control" required="required" id="link">
        </div>
    </div>
    

    <div class="col-sm-4">
        <label for="state">Redirect to url *</label>
        
        <div class="form-group">
            <label class="radio-inline">
            <input type="radio" name="state" value="enable" <?php echo ($edit &&$dynamic_qrcode['state'] =='enable') ? "checked": "" ; ?> required="required" id="enable"/> Enable</label>
            
            <label class="radio-inline">
            <input type="radio" name="state" value="disable" <?php echo ($edit && $dynamic_qrcode['state'] =='disable')? "checked": "" ; ?> required="required" id="disable"/> Disable</label>
        </div>
    </div>

    <?php if($_SESSION['type'] ===  'super') { ?>
    <div class="col-sm-4">
        <div class="form-group">
            <label for="id_owner">Owner *</label>
            <select name="id_owner" class="form-control">
                <option value="" <?php echo empty($dynamic_qrcode['id_owner']) ? 'selected' : ''; ?>>Unassigned (superuser only)</option>
                <?php
                require_once BASE_PATH . '/lib/Users/Users.php';
                $users_instance = new Users();
                $users = $users_instance->getAllUsers();
                $currentOwnerId = !empty($dynamic_qrcode['id_owner']) ? (int)$dynamic_qrcode['id_owner'] : null;
                $ownerFound = false;
                if (is_array($users)) {
                    foreach ($users as $user) {
                        $isMatch = ($currentOwnerId !== null && (int)$user['id'] === $currentOwnerId);
                        if ($isMatch) {
                            $ownerFound = true;
                        }
                        $selected = $isMatch ? 'selected' : '';
                        echo '<option value="' . (int)$user["id"] . '" ' . $selected . '>' . htmlspecialchars($user["username"], ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                }
                if ($currentOwnerId !== null && !$ownerFound) {
                    echo '<option value="' . $currentOwnerId . '" selected>Current Owner (ID: ' . $currentOwnerId . ' - unavailable)</option>';
                }
                ?>
            </select>
        </div>
    </div>
    <?php } else { ?>
        <input type="hidden" name="id_owner" value="<?php echo (int)$_SESSION["user_id"];?>"/>
    <?php } ?>

    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <input type="hidden" name="id" value="<?php echo (int)$dynamic_qrcode['id'];?>"/>
    <input type="hidden" name="edit" value="true"/>
    <input type="hidden" name="old_filename" value="<?php echo htmlspecialchars($dynamic_qrcode['filename'], ENT_QUOTES, 'UTF-8');?>"/>
</fieldset>
