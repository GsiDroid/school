<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Set current page for sidebar highlighting
$currentPage = "users";

// Process user actions
$message = '';

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Don't allow deleting your own account
    if ($user_id == $_SESSION['user_id']) {
        $message = '<div class="alert alert-danger">You cannot delete your own account!</div>';
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = '<div class="alert alert-success">User deleted successfully!</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error deleting user: ' . $e->getMessage() . '</div>';
        }
    }
}

// Add/Edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    try {
        // Update existing user
        if ($user_id) {
            if (!empty($password)) {
                // Update with password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $email, $role, $hashed_password, $user_id]);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $email, $role, $user_id]);
            }
            $message = '<div class="alert alert-success">User updated successfully!</div>';
        } else {
            // Add new user
            if (empty($password)) {
                $message = '<div class="alert alert-danger">Password is required for new users!</div>';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$name, $email, $hashed_password, $role]);
                $message = '<div class="alert alert-success">User added successfully!</div>';
            }
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Get all users
$stmt = $conn->prepare("SELECT id, name, email, role, created_at, last_login FROM users ORDER BY name");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once '../includes/header.php';
?>

<!-- Page content -->
<div class="container-fluid px-4">
    <h1 class="mt-4">User Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Users</li>
    </ol>
    
    <?php echo $message; ?>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-users me-1"></i>
                Manage Users
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="fas fa-plus"></i> Add New User
            </button>
        </div>
        <div class="card-body">
            <table id="usersTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info edit-user" 
                                    data-id="<?php echo $user['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                    data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                    data-bs-toggle="modal" data-bs-target="#userModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this user?');">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <span id="password-label">Password</span>
                            <small id="password-help" class="text-muted">(Leave blank to keep current password)</small>
                        </label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        $('#usersTable').DataTable();
        
        // Handle edit user button clicks
        const editButtons = document.querySelectorAll('.edit-user');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const userName = this.getAttribute('data-name');
                const userEmail = this.getAttribute('data-email');
                const userRole = this.getAttribute('data-role');
                
                document.getElementById('userModalLabel').textContent = 'Edit User';
                document.getElementById('user_id').value = userId;
                document.getElementById('name').value = userName;
                document.getElementById('email').value = userEmail;
                document.getElementById('role').value = userRole;
                document.getElementById('password-label').textContent = 'Password (Change)';
                document.getElementById('password-help').style.display = 'inline';
                document.getElementById('password').required = false;
            });
        });
        
        // Handle add new user button
        const addUserButton = document.querySelector('[data-bs-target="#userModal"]:not(.edit-user)');
        addUserButton.addEventListener('click', function() {
            document.getElementById('userModalLabel').textContent = 'Add New User';
            document.getElementById('user_id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('role').value = 'staff';
            document.getElementById('password-label').textContent = 'Password';
            document.getElementById('password-help').style.display = 'none';
            document.getElementById('password').required = true;
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>