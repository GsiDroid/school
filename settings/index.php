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
$currentPage = "settings";

// Get system settings
$stmt = $conn->prepare("SELECT * FROM system_settings ORDER BY setting_key");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Remove 'setting_' prefix
                
                $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->execute([$value, $_SESSION['user_id'], $setting_key]);
            }
        }
        $message = '<div class="alert alert-success">Settings updated successfully!</div>';
        
        // Refresh settings after update
        $stmt = $conn->prepare("SELECT * FROM system_settings ORDER BY setting_key");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error updating settings: ' . $e->getMessage() . '</div>';
    }
}

// Group settings by category
$grouped_settings = [];
foreach ($settings as $setting) {
    $category = 'General';
    
    if (strpos($setting['setting_key'], 'school_') === 0) {
        $category = 'School Information';
    } elseif (strpos($setting['setting_key'], 'smtp_') === 0) {
        $category = 'Email Settings';
    } elseif (strpos($setting['setting_key'], 'sms_') === 0) {
        $category = 'SMS Settings';
    } elseif (in_array($setting['setting_key'], ['backup_frequency', 'session_timeout'])) {
        $category = 'System Settings';
    }
    
    $grouped_settings[$category][] = $setting;
}

// Include header
include_once '../includes/header.php';
?>

<!-- Page content -->
<div class="container-fluid px-4">
    <h1 class="mt-4">System Settings</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Settings</li>
    </ol>
    
    <?php echo $message; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-cog me-1"></i>
            Manage System Settings
        </div>
        <div class="card-body">
            <form method="post" action="">
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <?php $first = true; foreach ($grouped_settings as $category => $settings): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                                id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>" 
                                type="button" 
                                role="tab" 
                                aria-controls="<?php echo strtolower(str_replace(' ', '-', $category)); ?>" 
                                aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
                            <?php echo $category; ?>
                        </button>
                    </li>
                    <?php $first = false; endforeach; ?>
                </ul>
                
                <div class="tab-content p-4" id="settingsTabContent">
                    <?php $first = true; foreach ($grouped_settings as $category => $category_settings): ?>
                    <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
                         id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>" 
                         role="tabpanel" 
                         aria-labelledby="<?php echo strtolower(str_replace(' ', '-', $category)); ?>-tab">
                        
                        <div class="row">
                            <?php foreach ($category_settings as $setting): ?>
                            <div class="col-md-6 mb-3">
                                <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                </label>
                                
                                <?php if ($setting['setting_key'] == 'backup_frequency'): ?>
                                <select class="form-select" id="setting_<?php echo $setting['setting_key']; ?>" name="setting_<?php echo $setting['setting_key']; ?>">
                                    <option value="daily" <?php echo $setting['setting_value'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo $setting['setting_value'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo $setting['setting_value'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                                
                                <?php elseif (strpos($setting['setting_key'], 'password') !== false): ?>
                                <input type="password" class="form-control" id="setting_<?php echo $setting['setting_key']; ?>" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                
                                <?php else: ?>
                                <input type="text" class="form-control" id="setting_<?php echo $setting['setting_key']; ?>" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php endif; ?>
                                
                                <?php if ($setting['setting_description']): ?>
                                <div class="form-text"><?php echo htmlspecialchars($setting['setting_description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>
                
                <div class="text-end mt-3">
                    <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-1"></i>
                    Database Backup
                </div>
                <div class="card-body">
                    <p>Create a backup of your database or restore from a previous backup.</p>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="../backup/create.php" class="btn btn-primary">Create Backup</a>
                        <a href="../backup/index.php" class="btn btn-secondary">View Backups</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-server me-1"></i>
                    System Information
                </div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th>PHP Version</th>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <th>MySQL Version</th>
                                <td>
                                    <?php 
                                    $stmt = $conn->query('SELECT VERSION() as version');
                                    $version = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $version['version'];
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Server Software</th>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                            </tr>
                            <tr>
                                <th>System Time</th>
                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>