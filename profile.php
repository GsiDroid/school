<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Set current page for sidebar highlighting
$currentPage = "profile";

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: login.php');
    exit;
}

// Process form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        
        try {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $user_id]);
            
            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            $message = '<div class="alert alert-success">Profile updated successfully!</div>';
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating profile: ' . $e->getMessage() . '</div>';
        }
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $message = '<div class="alert alert-danger">Current password is incorrect!</div>';
        } elseif ($new_password !== $confirm_password) {
            $message = '<div class="alert alert-danger">New passwords do not match!</div>';
        } elseif (strlen($new_password) < 6) {
            $message = '<div class="alert alert-danger">New password must be at least 6 characters long!</div>';
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $message = '<div class="alert alert-success">Password changed successfully!</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Error changing password: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Get user activity logs
$stmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once 'includes/header.php';
?>

<!-- Page content -->
<div class="container-fluid px-4">
    <h1 class="mt-4">My Profile</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Profile</li>
    </ol>
    
    <?php echo $message; ?>
    
    <div class="row">
        <div class="col-xl-4">
            <!-- Profile Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    User Information
                </div>
                <div class="card-body text-center">
                    <img class="img-account-profile rounded-circle mb-2" src="assets/img/user-avatar.png" alt="User Avatar" width="150">
                    <h4 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <div class="small font-italic text-muted mb-2"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></div>
                    <div class="mb-3">
                        <span class="badge bg-primary">Active</span>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Email
                        <span class="text-muted"><?php echo htmlspecialchars($user['email']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Member Since
                        <span class="text-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Last Login
                        <span class="text-muted">
                            <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-xl-8">
            <!-- Update Profile -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-edit me-1"></i>
                    Edit Profile
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" readonly>
                            <div class="form-text">Your role cannot be changed. Contact an administrator for role changes.</div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-key me-1"></i>
                    Change Password
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Recent Activity
                </div>
                <div class="card-body">
                    <?php if (empty($activity_logs)): ?>
                    <div class="alert alert-info">No recent activity found.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activity_logs as $log): ?>
                                <tr>
                                    <td><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($log['action']))); ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>