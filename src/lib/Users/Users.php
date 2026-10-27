<?php
require_once 'config/config.php';

/**
 * Class Users
 *
 * Manages user administrative accounts, role authorization, authentication records,
 * and user lifecycle operations.
 */
class Users
{
    /**
     * Initializes a new Users instance.
     */
    public function __construct()
    {
    }

    /**
     * Destructor for clean resource teardown.
     */
    public function __destruct()
    {
    }
    
    /**
     * Returns friendly column display names for table ordering.
     *
     * @return array<string, string> Associative map of column names to display labels.
     */
    public function setOrderingValues(): array
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'type' => 'Type'
        ];
    }

    /**
     * Retrieve all user records from the database.
     *
     * @return array List of user record associative arrays.
     */
    public function getAllUsers(): array
    {
        $db = getDbInstance();
        $users = $db->get('users');
        return is_array($users) ? $users : [];
    }

    /**
     * Retrieve a single user record by primary key ID.
     *
     * @param int|string $id The user database identifier.
     * @return array|null The user record array if found, null otherwise.
     */
    public function getUser($id): ?array
    {
        $db = getDbInstance();
        $db->where('id', (int)$id);
        $user = $db->getOne('users');
        return is_array($user) ? $user : null;
    }
    
    /**
     * Create and insert a new user account with validated credentials and complexity rules.
     *
     * Enforces username regex format, password minimum length (SEC-05), and forces
     * password change on initial login.
     *
     * @param array $input_data Form input parameters including username, password, and type.
     * @return never
     */
    public function addUser(array $input_data): void
    {
        $allowedTypes = ['super', 'admin'];
        if (empty($input_data['type']) || !in_array($input_data['type'], $allowedTypes, true)) {
            $this->failure('Invalid user type.');
        }

        $username = trim((string)($input_data['username'] ?? ''));
        if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,50}$/', $username)) {
            $this->failure('Username must be 3-50 characters and contain only letters, digits, dots, hyphens, and underscores.');
        }

        $password = (string)($input_data['password'] ?? '');
        if (strlen($password) < 8) {
            $this->failure('Password must be at least 8 characters long.');
        }

        $db = getDbInstance();

        $data_to_db["username"] = $username;
        $data_to_db['password'] = password_hash($password, PASSWORD_DEFAULT);
        $data_to_db["type"] = $input_data["type"];
        // Force newly created users to change their initial password on first login
        $data_to_db["must_change_password"] = 1;
        $data_to_db["session_version"] = 1;

        $db->where('username', $data_to_db['username']);
        $db->get('users');

        if ($db->count >= 1) {
            $this->failure('Username already exists');
        }

        $last_id = $db->insert('users', $data_to_db);

        if ($last_id) {
            $this->success('User added successfully');
        } else {
            error_log('Failed to add User: ' . $db->getLastError());
            $this->failure('Failed to add User.');
        }
    }
    
    /**
     * Update an existing user account with role preservation, complexity validation, and session invalidation.
     *
     * Prevents self-demotion, last superadmin demotion (SEC-04), and validates password complexity (SEC-05).
     *
     * @param array $input_data Form input parameters including id, username, type, and optional password.
     * @return never
     */
    public function editUser(array $input_data): void
    {
        $userId = (int)($input_data["id"] ?? 0);
        $queryString = http_build_query([
            'id' => $userId,
            'edit' => 'true',
        ]);
        $location = 'Location: user.php?' . $queryString;

        $allowedTypes = ['super', 'admin'];
        if (empty($input_data['type']) || !in_array($input_data['type'], $allowedTypes, true)) {
            $this->failure('Invalid user type.', $location);
        }

        $username = trim((string)($input_data['username'] ?? ''));
        if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,50}$/', $username)) {
            $this->failure('Username must be 3-50 characters and contain only letters, digits, dots, hyphens, and underscores.', $location);
        }

        $db = getDbInstance();

        $db->where('username', $username);
        $db->where('id', $userId, '!=');
        $row = $db->getOne('users');

        if (!empty($row['username'])) {
            $this->failure('Username already exists', $location);
        }

        $db->where('id', $userId);
        $currentUser = $db->getOne('users');
        if (!$currentUser) {
            $this->failure('User not found.');
        }

        // SEC-04: Prevent self-demotion
        $isDemotingSuper = (($currentUser['type'] ?? '') === 'super' && $input_data['type'] !== 'super');
        if ($isDemotingSuper) {
            if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId) {
                $this->failure('You cannot demote your own account.', $location);
            }
        }

        $data_to_db["username"] = $username;
        $data_to_db["type"] = $input_data["type"];

        $roleChanged = ($currentUser['type'] !== $input_data['type']);
        if ($roleChanged && empty($data_to_db['session_version'])) {
            $data_to_db['session_version'] = (int)($currentUser['session_version'] ?? 1) + 1;
            if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId) {
                $_SESSION['session_version'] = $data_to_db['session_version'];
                $_SESSION['type'] = $input_data['type'];
            }
        }

        if (!empty($input_data['password'])) {
            $newPassword = (string)$input_data['password'];
            if (strlen($newPassword) < 8) {
                $this->failure('Password must be at least 8 characters long.', $location);
            }

            $isSamePassword = (!empty($currentUser['password']) && password_verify($newPassword, $currentUser['password']));
            if (!$isSamePassword) {
                $data_to_db['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $data_to_db['series_id'] = null;
                $data_to_db['remember_token'] = null;
                $data_to_db['expires'] = null;
                $data_to_db['session_version'] = (int)($currentUser['session_version'] ?? 1) + 1;
                // When an administrator changes another user's password, force password change on next login
                if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] !== $userId) {
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

        // SEC-04: Enforce atomic check and update for last superadmin demotion
        if ($isDemotingSuper) {
            $db->startTransaction();
            $db->setQueryOption('FOR UPDATE');
            $db->where('type', 'super');
            $superCount = $db->getValue('users', 'count(*)');
            if ($superCount === null || (int)$superCount <= 1) {
                $db->rollback();
                $this->failure('Cannot demote the last superadmin account.', $location);
            }
        }

        $db->where('id', $userId);
        $stat = $db->update('users', $data_to_db);
        
        if ($stat) {
            if ($isDemotingSuper) {
                $db->commit();
            }
            $this->success('User updated successfully!');
        } else {
            if ($isDemotingSuper) {
                $db->rollback();
            }
            error_log('Failed to update User: ' . $db->getLastError());
            $this->failure('Failed to update User.');
        }
    }
    
    /**
     * Delete user account and reassign ownership of orphaned QR code resources.
     *
     * Prevents self-deletion and deletion of the last remaining superadmin account (SEC-04).
     *
     * @param int|string $id The user database identifier to delete.
     * @return never
     */
    public function deleteUser($id): void
    {
        if (empty($_SESSION['type']) || $_SESSION['type'] !== 'super') {
            header('HTTP/1.1 401 Unauthorized', true, 401);
            exit("401 Unauthorized");
        }

        $targetId = (int)$id;

        // SEC-04: Prevent deleting own account
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $targetId) {
            $this->failure('You cannot delete your own account.');
        }

        $userToDelete = $this->getUser($targetId);
        if (!$userToDelete) {
            $this->failure('User not found.');
        }

        $isDeletingSuper = (($userToDelete['type'] ?? '') === 'super');
        $db = getDbInstance();
        // SEC-04: Enforce atomic check and delete for last superadmin
        if ($isDeletingSuper) {
            $db->startTransaction();
            $db->setQueryOption('FOR UPDATE');
            $db->where('type', 'super');
            $superCount = $db->getValue('users', 'count(*)');
            if ($superCount === null || (int)$superCount <= 1) {
                $db->rollback();
                $this->failure('Cannot delete the last superadmin account.');
            }
        }
        
        // Disassociate orphaned QR codes before deleting user
        $db->where('id_owner', $targetId);
        $db->update('dynamic_qrcodes', ['id_owner' => null]);
        $db->where('id_owner', $targetId);
        $db->update('static_qrcodes', ['id_owner' => null]);

        $db->where('id', $targetId);
        $stat = $db->delete('users');

        if ($stat) {
            if ($isDeletingSuper) {
                $db->commit();
            }
            $this->info('User deleted successfully!');
        } else {
            if ($isDeletingSuper) {
                $db->rollback();
            }
            $this->failure('Unable to delete user');
        }
    }
    
    /**
     * Set failure flash message and redirect.
     *
     * @param string $message Flash error message.
     * @param string $location HTTP redirect header location.
     * @return never
     */
    public function failure(string $message, string $location = 'Location: users.php'): void
    {
        $_SESSION['failure'] = $message;
        header($location);
        exit();
    }
    
    /**
     * Set success flash message and redirect to users listing.
     *
     * @param string $message Flash success message.
     * @return never
     */
    public function success(string $message): void
    {
        $_SESSION['success'] = $message;
        header('Location: users.php');
        exit();
    }
    
    /**
     * Set informational flash message and redirect to users listing.
     *
     * @param string $message Flash informational message.
     * @return never
     */
    public function info(string $message): void
    {
        $_SESSION['info'] = $message;
        header('Location: users.php');
        exit();
    }
}
