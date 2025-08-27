<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Set current page for sidebar highlighting
$currentPage = "communication";

// Get communication templates
$stmt = $conn->prepare("SELECT * FROM communication_templates ORDER BY created_at DESC");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent communication logs
$stmt = $conn->prepare("SELECT * FROM communication_logs ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once '../includes/header.php';
?>

<!-- Page content -->
<div class="container-fluid px-4">
    <h1 class="mt-4">Communication Center</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Communication</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Email Templates</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM communication_templates WHERE type = 'email'");
                            $stmt->execute();
                            $email_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $email_count; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-envelope fa-3x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Templates</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">SMS Templates</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM communication_templates WHERE type = 'sms'");
                            $stmt->execute();
                            $sms_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $sms_count; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-sms fa-3x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Templates</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Sent Today</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM communication_logs WHERE DATE(sent_at) = CURDATE()");
                            $stmt->execute();
                            $sent_today = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $sent_today; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-paper-plane fa-3x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Failed Messages</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM communication_logs WHERE status = 'failed'");
                            $stmt->execute();
                            $failed = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $failed; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Failed</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-envelope me-1"></i>
                    Communication Templates
                    <button class="btn btn-sm btn-primary float-end">Add New Template</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($template['name']); ?></td>
                                    <td>
                                        <?php if ($template['type'] == 'email'): ?>
                                            <span class="badge bg-primary">Email</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">SMS</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($template['created_at'])); ?></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($templates) == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No templates found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Recent Communication Logs
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <?php if ($log['type'] == 'email'): ?>
                                            <span class="badge bg-primary">Email</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">SMS</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['recipient_contact']); ?></td>
                                    <td>
                                        <?php if ($log['status'] == 'sent'): ?>
                                            <span class="badge bg-success">Sent</span>
                                        <?php elseif ($log['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $log['sent_at'] ? date('M d, Y H:i', strtotime($log['sent_at'])) : 'N/A'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($logs) == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No logs found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-paper-plane me-1"></i>
                    Send New Message
                </div>
                <div class="card-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="messageType" class="form-label">Message Type</label>
                                <select class="form-select" id="messageType">
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="template" class="form-label">Use Template</label>
                                <select class="form-select" id="template">
                                    <option value="">Select Template</option>
                                    <?php foreach ($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="recipientType" class="form-label">Recipient Type</label>
                                <select class="form-select" id="recipientType">
                                    <option value="student">Students</option>
                                    <option value="guardian">Guardians</option>
                                    <option value="staff">Staff</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="recipients" class="form-label">Recipients</label>
                                <select class="form-select" id="recipients" multiple>
                                    <option value="all">All</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject">
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="5"></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>