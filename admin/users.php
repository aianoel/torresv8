<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'User Management';
require_once '../includes/admin_layout.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        // Create new user
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        $role = $_POST['role'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Error: A user with this email address already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $password, $email, $role, $firstName, $lastName);
            
            if ($stmt->execute()) {
                $success = "User created successfully!";
            } else {
                $error = "Error creating user: " . $conn->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    } elseif (isset($_POST['update_user'])) {
        // Update user
        $userId = $_POST['user_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        
        // Check if email already exists for another user
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $userId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Error: A user with this email address already exists.";
        } else {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, password=?, email=?, role=?, first_name=?, last_name=? WHERE id=?");
                $stmt->bind_param("ssssssi", $username, $password, $email, $role, $firstName, $lastName, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, first_name=?, last_name=? WHERE id=?");
                $stmt->bind_param("sssssi", $username, $email, $role, $firstName, $lastName, $userId);
            }
            
            if ($stmt->execute()) {
                $success = "User updated successfully!";
            } else {
                $error = "Error updating user: " . $conn->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $userId = $_POST['user_id'];
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $success = "User deleted successfully!";
        } else {
            $error = "Error deleting user: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

?>
<?php renderAdminHeader($pageTitle, 'users'); ?>
<?php renderAdminPageHeader('User Management', 'Manage system users and their permissions'); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div></div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-plus-circle"></i> Add User
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-user" 
                                                    data-user='<?php echo json_encode($user); ?>'>
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger delete-user" 
                                                        data-user-id="<?php echo $user['id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="frontdesk">Front Desk</option>
                                <option value="housekeeping">Housekeeping</option>
                                <option value="accounting">Accounting</option>
                                <option value="hr">HR</option>
                                <option value="pos_admin">POS Admin</option>
                                <option value="pos_cashier">POS Cashier</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="create_user">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="frontdesk">Front Desk</option>
                                <option value="housekeeping">Housekeeping</option>
                                <option value="accounting">Accounting</option>
                                <option value="hr">HR</option>
                                <option value="pos_admin">POS Admin</option>
                                <option value="pos_cashier">POS Cashier</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="update_user">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete user <strong id="delete_username"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_user">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit user button clicks
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', function() {
                const user = JSON.parse(this.dataset.user);
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_first_name').value = user.first_name;
                document.getElementById('edit_last_name').value = user.last_name;
                document.getElementById('edit_role').value = user.role;
                
                modal.show();
            });
        });
        
        // Handle delete user button clicks
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
                document.getElementById('delete_user_id').value = this.dataset.userId;
                document.getElementById('delete_username').textContent = this.dataset.username;
                modal.show();
            });
        });
    </script>

<?php renderAdminFooter(); ?>