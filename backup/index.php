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

// Process backup actions
$message = '';

// Delete backup
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $backup_file = $_GET['delete'];
    $backup_path = '../backups/' . basename($backup_file);
    
    if (file_exists($backup_path) && unlink($backup_path)) {
        $message = '<div class="alert alert-success">Backup deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting backup file!</div>';
    }
}

// Restore backup
if (isset($_GET['restore']) && !empty($_GET['restore'])) {
    $backup_file = $_GET['restore'];
    $backup_path = '../backups/' . basename($backup_file);
    
    if (file_exists($backup_path)) {
        try {
            // Get database credentials
            $db_host = DB_HOST;
            $db_name = DB_NAME;
            $db_user = DB_USER;
            $db_pass = DB_PASS;
            
            // Create restore command
            $restore_command = "mysql -h {$db_host} -u {$db_user} -p{$db_pass} {$db_name} < {$backup_path}";
            
            // Execute restore command
            $output = [];
            $return_var = 0;
            exec($restore_command, $output, $return_var);
            
            if ($return_var === 0) {
                $message = '<div class="alert alert-success">Database restored successfully!</div>';
                
                // Log the restore action
                $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], 'restore_backup', 'Restored database from ' . $backup_file]);
            } else {
                $message = '<div class="alert alert-danger">Error restoring database: ' . implode("\n", $output) . '</div>';
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Backup file not found!</div>';
    }
}

// Get list of backup files
$backup_dir = '../backups/';
$backup_files = [];

// Create backup directory if it doesn't exist
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Scan backup directory
if ($handle = opendir($backup_dir)) {
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($backup_dir . $file),
                'date' => filemtime($backup_dir . $file)
            ];
        }
    }
    closedir($handle);
}

// Sort backup files by date (newest first)
usort($backup_files, function($a, $b) {
    return $b['date'] - $a['date'];
});

// Include header
include_once '../includes/header.php';
?>

<!-- Page content -->
<div class="container-fluid px-4">
    <h1 class="mt-4">Backup & Restore</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Backup & Restore</li>
    </ol>
    
    <?php echo $message; ?>
    
    <div class="row">
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-1"></i>
                    Create Backup
                </div>
                <div class="card-body">
                    <p>Create a new backup of your database. This will save all your data including students, classes, attendance, fees, and more.</p>
                    <div class="d-grid">
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i> Create New Backup
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-cog me-1"></i>
                    Backup Settings
                </div>
                <div class="card-body">
                    <p>Configure automatic backup settings in the system settings page.</p>
                    <div class="d-grid">
                        <a href="../settings/index.php" class="btn btn-secondary">
                            <i class="fas fa-cog me-1"></i> System Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Backup History
                </div>
                <div class="card-body">
                    <?php if (empty($backup_files)): ?>
                    <div class="alert alert-info">No backup files found. Create your first backup now.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Date</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backup_files as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($file['name']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $file['date']); ?></td>
                                    <td><?php echo formatFileSize($file['size']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="download.php?file=<?php echo urlencode($file['name']); ?>" class="btn btn-primary" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="?restore=<?php echo urlencode($file['name']); ?>" class="btn btn-warning" title="Restore" 
                                               onclick="return confirm('Are you sure you want to restore this backup? This will overwrite your current database!')">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                            <a href="?delete=<?php echo urlencode($file['name']); ?>" class="btn btn-danger" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this backup file?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Backup Information
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5>Important Notes:</h5>
                        <ul>
                            <li>Regular backups are essential to prevent data loss.</li>
                            <li>Store backup files in multiple locations for added security.</li>
                            <li>Restoring a backup will overwrite all current data.</li>
                            <li>The system can be configured to create automatic backups.</li>
                        </ul>
                    </div>
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