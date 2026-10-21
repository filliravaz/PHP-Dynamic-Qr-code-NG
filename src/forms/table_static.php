<div class="row">
    <div class="col-12" id="bulk-action-div" style="display: none;">
        <div id="err-msg"></div>
        <div class="bulk-action-wrapper">
            <form id="bulk-action" action="bulk_action.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                <div class="col-sm-12 mb-2" style="margin-left: 10px">
                    <div class="row">
                        <div class="col-5 col-md-2">
                            <div class="input-group">
                                <select name="action" class="form-control">
                                    <option value="download" selected >Download</option>
                                    <option value="delete">Delete</option>
                                </select>
                                <input type="hidden" name="type" value="static">
                                <button type="submit" class="btn btn-primary">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body table-responsive p-0">
      <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th><input type="checkbox" name="bulk-select" value="1"></th>
                <th>ID</th>
                <th>Owner</th>
                <th>Filename</th>
                <th>Type</th>
                <th>Content</th>
                <th>Qr code</th>
                <th>Operations</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><input type="checkbox" name="action[]" value="<?=(int)$row['id']?>" onchange="updateBulkActionVisibility()"></td>
                <td><?php echo (int)$row['id']; ?></td>
                <td>
                    <?php
                    if(!isset($row['id_owner']))
                        echo "";
                    else {
                        require_once BASE_PATH . '/lib/Users/Users.php';
                        $users = new Users();
                        $user = $users->getUser($row['id_owner']);
                        if($user !== NULL)
                            echo htmlspecialchars($user["username"], ENT_QUOTES, 'UTF-8');
                        else
                            echo "";
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($row['filename'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php
                    $raw_content = $row['content'];
                    if (strpos($raw_content, '&lt;strong&gt;') !== false || strpos($raw_content, '&lt;div') !== false) {
                        $raw_content = htmlspecialchars_decode($raw_content, ENT_QUOTES);
                    }
                    $clean_content = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $raw_content);
                    $clean_content = preg_replace('/<(script|iframe|object|embed|style)\b[^>]*>(.*?)<\/\1>/is', '', $clean_content);
                    echo strip_tags($clean_content, ['strong', 'b', 'br', 'div', 'span', 'p']);
                    ?>
                </td>
                <td>
                    <?php echo '<img src="'.SAVED_QRCODE_FOLDER.htmlspecialchars($row['qrcode'], ENT_QUOTES, 'UTF-8').'" width="100" height="100">'; ?>
                </td>
                <td>
                    
                    <!-- EDIT -->
                    <a href="static_qrcode.php?edit=true&id=<?php echo (int)$row['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                    
                    <!-- DELETE -->
                    <a
                            class="btn btn-danger delete_btn"
                            data-toggle="modal"
                            data-target="#delete-modal"
                            data-del_id="<?php echo (int)$row["id"];?>"
                    ><i class="fas fa-trash"></i></a>

                    <!-- DOWNLOAD -->
                    <a href="<?php echo SAVED_QRCODE_FOLDER.htmlspecialchars($row['qrcode'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" download><i class="fa fa-download"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
   </div><!-- /.Card body -->
   
   <div class="card-footer clearfix">
       <?php echo paginationLinks($page, $total_pages, 'static_qrcodes.php'); ?>
       </div><!-- /.Card footer -->
       
        </div><!-- /.Card -->
    </div><!-- /.col -->
</div><!-- /.row -->

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="delete-modal" role="dialog">
    <div class="modal-dialog">
        <form action="static_qrcode.php" method="POST">
            <!-- Modal content -->

            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirm</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="del_id" id="del_id" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                    <p>Are you sure you want to delete this row?</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- /.Delete Confirmation Modal -->

<script>
    const deleteButtons = document.querySelectorAll('.delete_btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('del_id').value = button.getAttribute('data-del_id');

            const deleteModal = document.querySelector('#delete-modal');
            deleteModal.style.display = 'block';
        });
    });
</script>

<script>
    function updateBulkActionVisibility() {
        const checkboxes = document.querySelectorAll('input[name="action[]"]');
        const bulkActionDiv = document.getElementById('bulk-action-div');

        const selectedCheckboxes = Array.from(checkboxes).filter(checkbox => checkbox.checked);

        if (selectedCheckboxes.length > 0) {
            bulkActionDiv.style.display = 'block';
        } else {
            bulkActionDiv.style.display = 'none';
        }
    }

    updateBulkActionVisibility();
</script>
