<?php
require_once 'config/config.php';

class Users
{
    /**
     *
     */
    public function __construct()
    {
    }

    /**
     *
     */
    public function __destruct()
    {
    }
    
    /**
     * Set friendly columns\' names to order tables\' entries
     */
    public function setOrderingValues()
    {
        $ordering = [
            'id' => 'ID',
            'username' => 'Username',
            'type' => 'Type'
        ];

        return $ordering;
    }

    public function getAllUsers() {
        $db = getDbInstance();
        return $db->get('users');
    }

    public function getUser($id) {
        $db = getDbInstance();

        $db->where('id', $id);
        return $db->getOne('users');
    }
    
    /**
     * Add user
     */
    public function addUser($input_data) {
        $db = getDbInstance();

        $data_to_db["username"] = $input_data["username"];
        $data_to_db['password'] = password_hash($input_data['password'], PASSWORD_DEFAULT);
        $data_to_db["type"] = $input_data["type"];
        // Force newly created users to change their initial password on first login
        $data_to_db["must_change_password"] = 1;
        $data_to_db["session_version"] = 1;

        $db->where('username', $data_to_db['username']);
        $db->get('users');

        if ($db->count >= 1)
            $this->failure('Username already exists');

	    $last_id = $db->insert('users', $data_to_db);

	    if ($last_id)
		    $this->success('User added successfully');
    }
    
    /**
     * Edit user
     * 
     */
    public function editUser($input_data) {
        $db = getDbInstance();

        $db->where('username', $input_data['username']);
        $db->where('id', $input_data["id"], '!=');
        $row = $db->getOne('users');

        if (!empty($row['username']))  {
            $query_string = http_build_query(array(
                'id' => $input_data["id"],
                'edit' => "true",
            ));
            $this->failure('Username already exists', 'Location: user.php?'.$query_string);
        }

        $db->where('id', $input_data["id"]);
        $currentUser = $db->getOne('users');

        $data_to_db["username"] = $input_data["username"];
        $data_to_db["type"] = $input_data["type"];

        $roleChanged = ($currentUser && isset($currentUser['type']) && $currentUser['type'] !== $input_data['type']);
        if ($roleChanged && empty($data_to_db['session_version'])) {
            $data_to_db['session_version'] = (int)($currentUser['session_version'] ?? 1) + 1;
            if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$input_data['id']) {
                $_SESSION['session_version'] = $data_to_db['session_version'];
                $_SESSION['type'] = $input_data['type'];
            }
        }

        if (!empty($input_data['password'])) {
            $isSamePassword = (!empty($currentUser['password']) && password_verify($input_data['password'], $currentUser['password']));
            if (!$isSamePassword) {
                $data_to_db['password'] = password_hash($input_data['password'], PASSWORD_DEFAULT);
                $data_to_db['series_id'] = null;
                $data_to_db['remember_token'] = null;
                $data_to_db['expires'] = null;
                $data_to_db['session_version'] = (int)($currentUser['session_version'] ?? 1) + 1;
                // When an administrator changes another user's password, force password change on next login
                if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] !== (int)$input_data['id']) {
                    $data_to_db["must_change_password"] = 1;
                } else {
                    $data_to_db["must_change_password"] = 0;
                    $_SESSION['session_version'] = $data_to_db['session_version'];
                }
            } else {
                // Password unchanged: preserve existing flag
                $data_to_db["must_change_password"] = (int)($currentUser['must_change_password'] ?? 0);
            }
        }

	    $db->where('id', $input_data["id"]);
	    $stat = $db->update('users', $data_to_db);
        
        if ($stat)
            $this->success('User updated successfully!');
        else
            $this->failure('Failed to update User: ' . $db->getLastError());
    }
    
    /**
     * Delete user
     * 
     */
    public function deleteUser($id) {
        if($_SESSION['type']!='super'){
            header('HTTP/1.1 401 Unauthorized', true, 401);
            exit("401 Unauthorized");
        }
        
        $db = getDbInstance();
        // Disassociate orphaned QR codes before deleting user
        $db->where('id_owner', $id);
        $db->update('dynamic_qrcodes', ['id_owner' => null]);
        $db->where('id_owner', $id);
        $db->update('static_qrcodes', ['id_owner' => null]);

        $db->where('id', $id);
        $stat = $db->delete('users');

        if ($stat)
            $this->info('User deleted successfully!');
        else
            $this->failure('Unable to delete user');
    }
    
    /**
     * Flash message Failure process
     */
    public function failure($message, $location = 'Location: users.php') {
        $_SESSION['failure'] = $message;
        header($location);
    	exit();
    }
    
    /**
     * Flash message Success process
     */
    public function success($message) {
        $_SESSION['success'] = $message;
        header('Location: users.php');
    	exit();
    }
    
    /**
     * Flash message Info process
     */
    public function info($message) {
        $_SESSION['info'] = $message;
        header('Location: users.php');
    	exit();
    }
}
?>
