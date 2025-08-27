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
$currentPage = "activity-log";

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get activity logs
$limit = 50; // Number of logs to display per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter by user if not admin
if (isset($user['role']) && $user['role'] === 'admin' && isset($_GET['all'])) {
    $stmt = $conn->prepare("SELECT al.*, u.name as user_name FROM activity_logs al 
                          LEFT JOIN users u ON al.user_id = u.id 
                          ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM activity_logs");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} else {
    $stmt = $conn->prepare("SELECT al.*, u.name as user_name FROM activity_logs al 
                          LEFT JOIN users u ON al.user_id = u.id 
                          WHERE al.user_id = ? 
                          ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$user_id, $limit, $offset]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM activity_logs WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Calculate pagination
$total_pages = ceil($total / $limit);

// Include header
include_once 'includes/header.php';
?>

<!-- Page content -->
<div class="container-fluid px-4">
    <h1 class="mt-4">Activity Log</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Activity Log</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-history me-1"></i>
                Activity History
            </div>
            <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
            <div>
                <?php if (!isset($_GET['all'])): ?>
                <a href="?all=1" class="btn btn-sm btn-primary">View All Users' Activity</a>
                <?php else: ?>
                <a href="?" class="btn btn-sm btn-secondary">View Only My Activity</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
            <div class="alert alert-info">No activity logs found.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <?php if (isset($_GET['all']) && isset($user['role']) && $user['role'] === 'admin'): ?>
                            <th>User</th>
                            <?php endif; ?>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <?php if (isset($_GET['all']) && isset($user['role']) && $user['role'] === 'admin'): ?>
                            <td><?php echo htmlspecialchars($log['user_name'] ?? 'Unknown'); ?></td>
                            <?php endif; ?>
                            <td>
                                <span class="badge bg-<?php echo getActionBadgeClass($log['action']); ?>">
                                    <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($log['action']))); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Activity log pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['all']) ? '&all=1' : ''; ?>">
                            Previous
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Previous</span>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['all']) ? '&all=1' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['all']) ? '&all=1' : ''; ?>">
                            Next
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Next</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper function to determine badge class based on action
function getActionBadgeClass($action) {
    $action = strtolower($action);
    
    if (strpos($action, 'login') !== false) {
        return 'primary';
    } elseif (strpos($action, 'create') !== false || strpos($action, 'add') !== false) {
        return 'success';
    } elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) {
        return 'info';
    } elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
        return 'danger';
    } elseif (strpos($action, 'backup') !== false || strpos($action, 'restore') !== false) {
        return 'warning';
    } else {
        return 'secondary';
    }
}
?>

<?php include_once 'includes/footer.php'; ?>