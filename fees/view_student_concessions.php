<?php
/**
 * Fees Management Module - View Student Concessions
 * Displays all concessions for a specific student
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

// Check if student ID is provided
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    header("Location: concessions.php?error=student_id_required");
    exit();
}

$student_id = (int)$_GET['student_id'];

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get student details
$stmt = $conn->prepare("SELECT s.*, c.name as class_name, c.section 
                       FROM students s 
                       JOIN classes c ON s.class_id = c.id 
                       WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: concessions.php?error=student_not_found");
    exit();
}

// Get all concessions for this student
$stmt = $conn->prepare("SELECT fc.*, fcat.name as category_name 
                       FROM fee_concessions fc 
                       JOIN fee_categories fcat ON fc.fee_category_id = fcat.id 
                       WHERE fc.student_id = ? 
                       ORDER BY fc.valid_from DESC");
$stmt->execute([$student_id]);
$concessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$pageTitle = "Student Concessions";
$currentPage = "fees";

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Fees Management</a></li>
        <li class="breadcrumb-item"><a href="concessions.php">Fee Concessions</a></li>
        <li class="breadcrumb-item active">Student Concessions</li>
    </ol>
    
    <div class="row mb-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-user-graduate me-1"></i>
                        Student Information
                    </div>
                    <div>
                        <a href="concessions.php?student_id=<?php echo $student_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Add New Concession
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center mb-3">
                            <?php if (!empty($student['photo'])): ?>
                                <img src="../uploads/student_photos/<?php echo $student['photo']; ?>" 
                                     alt="Student Photo" class="img-fluid rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 120px; height: 120px; margin: 0 auto;">
                                    <i class="fas fa-user fa-4x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5 mb-3">
                            <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                            <p class="mb-1"><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></p>
                            <p class="mb-1"><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></p>
                            <p class="mb-1"><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
                        </div>
                        <div class="col-md-5 mb-3">
                            <p class="mb-1"><strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?></p>
                            <p class="mb-1"><strong>Admission Date:</strong> <?php echo date('M d, Y', strtotime($student['admission_date'])); ?></p>
                            <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-percent me-1"></i>
                    Concession History
                </div>
                <div class="card-body">
                    <?php if (empty($concessions)): ?>
                        <div class="alert alert-info">
                            No concessions found for this student.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="concessionsTable">
                                <thead>
                                    <tr>
                                        <th>Fee Category</th>
                                        <th>Concession Type</th>
                                        <th>Value</th>
                                        <th>Reason</th>
                                        <th>Valid From</th>
                                        <th>Valid Until</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($concessions as $c): 
                                        $is_active = true;
                                        if ($c['valid_until'] && strtotime($c['valid_until']) < time()) {
                                            $is_active = false;
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($c['category_name']); ?></td>
                                            <td><?php echo $c['concession_type']; ?></td>
                                            <td>
                                                <?php if ($c['concession_type'] == 'Percentage'): ?>
                                                    <?php echo $c['concession_value']; ?>%
                                                <?php else: ?>
                                                    $<?php echo number_format($c['concession_value'], 2); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($c['reason']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($c['valid_from'])); ?></td>
                                            <td>
                                                <?php if ($c['valid_until']): ?>
                                                    <?php echo date('M d, Y', strtotime($c['valid_until'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No end date</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($is_active): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Expired</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="concessions.php?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger delete-btn" data-id="<?php echo $c['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-invoice-dollar me-1"></i>
                    Fee Invoices
                </div>
                <div class="card-body">
                    <?php
                    // Get recent fee invoices for this student
                    $stmt = $conn->prepare("SELECT fi.*, 
                                           SUM(fii.amount) as total_amount,
                                           (SELECT SUM(amount) FROM fee_payments WHERE invoice_id = fi.id) as paid_amount
                                           FROM fee_invoices fi 
                                           JOIN fee_invoice_items fii ON fi.id = fii.invoice_id
                                           WHERE fi.student_id = ? 
                                           GROUP BY fi.id
                                           ORDER BY fi.issue_date DESC
                                           LIMIT 5");
                    $stmt->execute([$student_id]);
                    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (empty($invoices)): ?>
                        <div class="alert alert-info">
                            No recent invoices found for this student.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Term</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): 
                                        $total = $invoice['total_amount'] ?? 0;
                                        $paid = $invoice['paid_amount'] ?? 0;
                                        $balance = $total - $paid;
                                        
                                        // Determine status
                                        $status = 'Unpaid';
                                        $status_class = 'bg-danger';
                                        
                                        if ($balance <= 0) {
                                            $status = 'Paid';
                                            $status_class = 'bg-success';
                                        } elseif ($paid > 0) {
                                            $status = 'Partial';
                                            $status_class = 'bg-warning';
                                        }
                                        
                                        if ($balance > 0 && strtotime($invoice['due_date']) < time()) {
                                            $status = 'Overdue';
                                            $status_class = 'bg-danger';
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>">
                                                    <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($invoice['term'] . ' ' . $invoice['academic_year']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                            <td>$<?php echo number_format($total, 2); ?></td>
                                            <td>$<?php echo number_format($paid, 2); ?></td>
                                            <td>$<?php echo number_format($balance, 2); ?></td>
                                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="../students/view.php?id=<?php echo $student_id; ?>&tab=fees" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-1"></i> View All Invoices
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this fee concession? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form action="delete_concession.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        if (document.getElementById('concessionsTable')) {
            new DataTable('#concessionsTable', {
                order: [[6, 'desc'], [4, 'desc']],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100]
            });
        }
        
        // Handle delete button clicks
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const deleteIdInput = document.getElementById('delete_id');
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                deleteIdInput.value = id;
                deleteModal.show();
            });
        });
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>