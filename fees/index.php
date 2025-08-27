<?php
/**
 * Fees Management Module - Main Page
 * Lists all fee invoices with search, filter and pagination
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include common functions
require_once '../includes/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Set page title
$pageTitle = "Fees Management";
$currentPage = "fees_management";

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$term_filter = isset($_GET['term']) ? $_GET['term'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$params = [];
$where_clauses = [];

if (!empty($search)) {
    $where_clauses[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ? OR fi.invoice_no LIKE ?)"; 
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($class_filter > 0) {
    $where_clauses[] = "fi.class_id = ?";
    $params[] = $class_filter;
}

if (!empty($status_filter)) {
    $where_clauses[] = "fi.status = ?";
    $params[] = $status_filter;
}

if (!empty($term_filter)) {
    $where_clauses[] = "fi.term = ?";
    $params[] = $term_filter;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM fee_invoices fi 
               LEFT JOIN students s ON fi.student_id = s.id 
               $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get fee invoices with student and class information
$query = "SELECT fi.*, s.first_name, s.last_name, s.admission_no, c.name as class_name, c.section 
          FROM fee_invoices fi 
          LEFT JOIN students s ON fi.student_id = s.id 
          LEFT JOIN classes c ON fi.class_id = c.id 
          $where_clause 
          ORDER BY fi.created_at DESC 
          LIMIT $offset, $per_page";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all classes for filter dropdown
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get terms for filter dropdown
$term_query = "SELECT DISTINCT term FROM fee_invoices ORDER BY term";
$stmt = $conn->prepare($term_query);
$stmt->execute();
$terms = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get fee statistics
$stats_query = "SELECT 
                SUM(total_amount) as total_fees,
                SUM(paid_amount) as total_collected,
                SUM(balance) as total_pending,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count
              FROM fee_invoices";
$stmt = $conn->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <!-- Fee Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Total Fees</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['total_fees'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="fee_structure.php">View Fee Structure</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Collected</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['total_collected'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-hand-holding-usd fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="payments.php">View Payments</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Pending</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['total_pending'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-hourglass-half fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php?status=unpaid">View Pending</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Overdue</h5>
                            <h3 class="mb-0"><?php echo $stats['overdue_count'] ?? 0; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php?status=overdue">View Overdue</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-file-invoice-dollar me-1"></i>
                Fee Invoices
            </div>
            <div>
                <div class="btn-group" role="group">
                    <a href="create_invoice.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Create Invoice
                    </a>
                    <a href="bulk_invoice.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-file-invoice"></i> Bulk Invoice
                    </a>
                    <a href="fee_structure.php" class="btn btn-info btn-sm">
                        <i class="fas fa-cog"></i> Fee Structure
                    </a>
                    <a href="concessions.php" class="btn btn-success btn-sm">
                        <i class="fas fa-percentage"></i> Concessions
                    </a>
                    <a href="bulk_concessions.php" class="btn btn-success btn-sm">
                        <i class="fas fa-users"></i> Bulk Concessions
                    </a>
                    <a href="reports.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by name or invoice no" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="class" class="form-select" onchange="this.form.submit()">
                                <option value="0">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($class_filter == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="term" class="form-select" onchange="this.form.submit()">
                                <option value="">All Terms</option>
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?php echo $term; ?>" <?php echo ($term_filter == $term) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($term); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="unpaid" <?php echo ($status_filter == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="partially_paid" <?php echo ($status_filter == 'partially_paid') ? 'selected' : ''; ?>>Partially Paid</option>
                                <option value="paid" <?php echo ($status_filter == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="overdue" <?php echo ($status_filter == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="btn-group" role="group">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="index.php" class="btn btn-secondary">Reset</a>
                                <button type="button" class="btn btn-success" id="sendRemindersBtn">
                                    <i class="fas fa-bell"></i> Send Reminders
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Invoices Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="feesTable">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($invoices) > 0): ?>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                                    <td>
                                        <a href="../students/view.php?id=<?php echo $invoice['student_id']; ?>">
                                            <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($invoice['admission_no']); ?></small>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($invoice['class_name'] . ' ' . $invoice['section']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['term'] . ' - ' . $invoice['academic_year']); ?></td>
                                    <td><?php echo number_format($invoice['total_amount'], 2); ?></td>
                                    <td><?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                    <td><?php echo number_format($invoice['balance'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $due_date = new DateTime($invoice['due_date']);
                                        $today = new DateTime();
                                        $is_overdue = ($today > $due_date && $invoice['balance'] > 0);
                                        echo date('d M, Y', strtotime($invoice['due_date']));
                                        if ($is_overdue) {
                                            echo ' <span class="badge bg-danger">Overdue</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($invoice['status']) {
                                            case 'paid':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'partially_paid':
                                                $status_class = 'bg-warning';
                                                break;
                                            case 'unpaid':
                                                $status_class = 'bg-secondary';
                                                break;
                                            case 'overdue':
                                                $status_class = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $invoice['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($invoice['status'] != 'paid'): ?>
                                                <a href="collect_payment.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-success" title="Collect Payment">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="print_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary" title="Print" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-warning send-reminder" 
                                                    data-id="<?php echo $invoice['id']; ?>" 
                                                    data-student="<?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?>" 
                                                    title="Send Reminder">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                            <?php if ($invoice['status'] == 'unpaid'): ?>
                                                <button type="button" class="btn btn-sm btn-danger delete-invoice" 
                                                        data-id="<?php echo $invoice['id']; ?>" 
                                                        data-invoice="<?php echo htmlspecialchars($invoice['invoice_no']); ?>" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No invoices found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>&term=<?php echo urlencode($term_filter); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>&term=<?php echo urlencode($term_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>&term=<?php echo urlencode($term_filter); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
            <!-- Export Options -->
            <div class="mt-3 text-center">
                <div class="btn-group" role="group">
                    <a href="export.php?format=pdf&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>&term=<?php echo urlencode($term_filter); ?>" class="btn btn-danger">
                        <i class="fas fa-file-pdf me-1"></i> Export as PDF
                    </a>
                    <a href="export.php?format=excel&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>&term=<?php echo urlencode($term_filter); ?>" class="btn btn-success">
                        <i class="fas fa-file-excel me-1"></i> Export as Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-labelledby="deleteInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteInvoiceModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the invoice: <span id="invoiceNo"></span>?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteInvoiceForm" action="delete_invoice.php" method="POST">
                    <input type="hidden" name="invoice_id" id="invoiceId">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Send Reminder Modal -->
<div class="modal fade" id="sendReminderModal" tabindex="-1" aria-labelledby="sendReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="sendReminderModalLabel">Send Payment Reminder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reminderForm" action="send_reminder.php" method="POST">
                    <input type="hidden" name="invoice_id" id="reminderInvoiceId">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label for="reminderStudent" class="form-label">Student</label>
                        <input type="text" class="form-control" id="reminderStudent" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reminderMethod" class="form-label">Reminder Method</label>
                        <select class="form-select" id="reminderMethod" name="reminder_method">
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="both">Both Email & SMS</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reminderMessage" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="reminderMessage" name="reminder_message" rows="3" placeholder="Add a custom message to the standard reminder template"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="sendReminderBtn">Send Reminder</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Reminders Modal -->
<div class="modal fade" id="bulkRemindersModal" tabindex="-1" aria-labelledby="bulkRemindersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkRemindersModalLabel">Send Bulk Reminders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkReminderForm" action="send_bulk_reminders.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label for="bulkReminderType" class="form-label">Send Reminders To</label>
                        <select class="form-select" id="bulkReminderType" name="reminder_type">
                            <option value="overdue">All Overdue Invoices</option>
                            <option value="upcoming">Upcoming Due Dates (Next 7 Days)</option>
                            <option value="unpaid">All Unpaid Invoices</option>
                            <option value="partially_paid">All Partially Paid Invoices</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulkReminderMethod" class="form-label">Reminder Method</label>
                        <select class="form-select" id="bulkReminderMethod" name="reminder_method">
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="both">Both Email & SMS</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulkReminderMessage" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="bulkReminderMessage" name="reminder_message" rows="3" placeholder="Add a custom message to the standard reminder template"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="sendBulkRemindersBtn">Send Reminders</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        const table = new DataTable('#feesTable', {
            paging: false,  // Disable DataTables pagination as we're using custom pagination
            info: false,     // Hide DataTables info as we're using custom pagination
            searching: false // Disable DataTables search as we're using custom search
        });
        
        // Handle delete invoice button click
        const deleteButtons = document.querySelectorAll('.delete-invoice');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const invoiceId = this.getAttribute('data-id');
                const invoiceNo = this.getAttribute('data-invoice');
                
                document.getElementById('invoiceId').value = invoiceId;
                document.getElementById('invoiceNo').textContent = invoiceNo;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteInvoiceModal'));
                deleteModal.show();
            });
        });
        
        // Handle send reminder button click
        const reminderButtons = document.querySelectorAll('.send-reminder');
        reminderButtons.forEach(button => {
            button.addEventListener('click', function() {
                const invoiceId = this.getAttribute('data-id');
                const studentName = this.getAttribute('data-student');
                
                document.getElementById('reminderInvoiceId').value = invoiceId;
                document.getElementById('reminderStudent').value = studentName;
                
                const reminderModal = new bootstrap.Modal(document.getElementById('sendReminderModal'));
                reminderModal.show();
            });
        });
        
        // Handle send reminder form submission
        document.getElementById('sendReminderBtn').addEventListener('click', function() {
            document.getElementById('reminderForm').submit();
        });
        
        // Handle bulk reminders button click
        document.getElementById('sendRemindersBtn').addEventListener('click', function() {
            const bulkRemindersModal = new bootstrap.Modal(document.getElementById('bulkRemindersModal'));
            bulkRemindersModal.show();
        });
        
        // Handle bulk reminders form submission
        document.getElementById('sendBulkRemindersBtn').addEventListener('click', function() {
            document.getElementById('bulkReminderForm').submit();
        });
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>