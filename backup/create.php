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
$currentPage = "backup";

// Process backup creation
$message = '';
$backup_file = '';

try {
    // Get database credentials
    $db_host = DB_HOST;
    $db_name = DB_NAME;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
    
    // Create backup directory if it doesn't exist
    $backup_dir = '../backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Generate backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . $db_name . '_' . $timestamp . '.sql';
    
    // Create backup command
    $backup_command = "mysqldump -h {$db_host} -u {$db_user} -p{$db_pass} {$db_name} > {$backup_file}";
    
    // Execute backup command
    $output = [];
    $return_var = 0;
    exec($backup_command, $output, $return_var);
    
    if ($return_var === 0) {
        $message = '<div class="alert alert-success">Database backup created successfully!</div>';
        
        // Log the backup action
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], 'create_backup', 'Created database backup: ' . basename($backup_file)]);
    } else {
        $message = '<div class="alert alert-danger">Error creating backup: ' . implode("\n", $output) . '</div>';
    }
} catch (Exception $e) {
    $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}

// Include header
include_once '../includes/header.php';
?>

<!-- Page content -->
<div class="container-fluid px-4">
    <h1 class="mt-4">Create Backup</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Backup & Restore</a></li>
        <li class="breadcrumb-item active">Create Backup</li>
    </ol>
    
    <?php echo $message; ?>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-check-circle me-1"></i>
                    Backup Status
                </div>
                <div class="card-body">
                    <?php if ($return_var === 0): ?>
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success fa-5x"></i>
                        </div>
                        <h4>Backup Created Successfully</h4>
                        <p>Your database has been backed up successfully. You can download the backup file or return to the backup list.</p>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="download.php?file=<?php echo urlencode(basename($backup_file)); ?>" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i> Download Backup
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-list me-1"></i> View All Backups
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-exclamation-triangle text-danger fa-5x"></i>
                        </div>
                        <h4>Backup Failed</h4>
                        <p>There was an error creating the database backup. Please check the error message and try again.</p>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-redo me-1"></i> Try Again
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-list me-1"></i> View All Backups
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Backup Information
                </div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th>Database Name</th>
                                <td><?php echo DB_NAME; ?></td>
                            </tr>
                            <tr>
                                <th>Backup Date</th>
                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                            </tr>
                            <tr>
                                <th>Backup File</th>
                                <td>
                                    <?php if ($return_var === 0): ?>
                                    <?php echo basename($backup_file); ?>
                                    <?php else: ?>
                                    <span class="text-danger">Failed to create</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($return_var === 0): ?>
                            <tr>
                                <th>File Size</th>
                                <td><?php echo formatFileSize(filesize($backup_file)); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Created By</th>
                                <td><?php echo htmlspecialchars($_SESSION['user_name']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>

<?php include_once '../includes/footer.php'; ?>