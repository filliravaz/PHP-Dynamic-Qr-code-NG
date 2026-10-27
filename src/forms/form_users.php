<fieldset>
    <div class="col-sm-4">
        <div class="form-group">
            <label for="username">Username *</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                </div>
                
                <input type="text" name="username" placeholder="Username" class="form-control" required="required" value="<?php echo ($edit) ? htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') : ''; ?>" autocomplete="off">
            </div>
        </div>
    </div>
    
    <div class="col-sm-4">
        <div class="form-group">
            <label for="password">Password <?php echo ($edit) ? '' : '*'; ?></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                </div>
                
                <input type="password" name="password" placeholder="<?php echo ($edit) ? 'Leave blank to keep current password' : 'Password'; ?>" class="form-control" <?php echo ($edit) ? '' : 'required="required"'; ?> autocomplete="off">
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <label for="user-type">User type *</label>
        
        <div class="form-group">
            <div class="radio">
                <label class="radio">
                <input type="radio" name="type" value="super" required="required" <?php echo ($edit && $user['type'] =='super') ? "checked": "" ; ?>/> Super admin</label>
            </div>
            
            <div class="radio">
                <label class="radio">
                <input type="radio" name="type" value="admin" required="required" <?php echo ($edit && $user['type'] =='admin') ? "checked": "" ; ?>/> Admin</label>
            </div>
        </div>
    </div>
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <?php if($edit) { ?>
        <input type="hidden" name="id" value="<?php echo (int)$user['id'];?>"/>
        <input type="hidden" name="edit" value="true"/>
    <?php } ?>
</fieldset>
