<?php
/**
 * Fees Management Module - Bulk Invoice Generation
 * Handles generating fee invoices for multiple students at once
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
$pageTitle = "Bulk Invoice Generation";
$currentPage = "fees";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success_message = '';
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$term_id = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;
$academic_year = isset($_GET['academic_year']) ? trim($_GET['academic_year']) : date('Y');

// Get all classes for filter
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all terms
$term_query = "SELECT * FROM terms ORDER BY name";
$stmt = $conn->prepare($term_query);
$stmt->execute();
$terms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get academic years (current year and 5 years before and after)
$current_year = (int)date('Y');
$academic_years = [];
for ($i = $current_year - 5; $i <= $current_year + 5; $i++) {
    $academic_years[] = $i;
}

// Get students based on class filter
$students = [];
if ($class_id > 0) {
    $stmt = $conn->prepare("SELECT s.id, s.first_name, s.last_name, s.admission_number, 
                           c.name as class_name, c.section 
                           FROM students s 
                           JOIN classes c ON s.class_id = c.id 
                           WHERE s.class_id = ? AND s.status = 'Active' 
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get fee structure if term and class are selected
$fee_structure = [];
$total_fee_amount = 0;
if ($class_id > 0 && $term_id > 0 && !empty($academic_year)) {
    $stmt = $conn->prepare("SELECT fs.*, fc.name as category_name 
                           FROM fee_structures fs 
                           JOIN fee_categories fc ON fs.fee_category_id = fc.id 
                           WHERE fs.class_id = ? AND fs.term_id = ? AND fs.academic_year = ? 
                           ORDER BY fc.name");
    $stmt->execute([$class_id, $term_id, $academic_year]);
    $fee_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total fee amount
    foreach ($fee_structure as $fee) {
        $total_fee_amount += $fee['amount'];
    }
}

// Process form submission for generating bulk invoices
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoices'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
        $term_id = isset($_POST['term_id']) ? (int)$_POST['term_id'] : 0;
        $academic_year = isset($_POST['academic_year']) ? trim($_POST['academic_year']) : '';
        $issue_date = isset($_POST['issue_date']) ? $_POST['issue_date'] : date('Y-m-d');
        $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // Validate required fields
        if (empty($student_ids)) {
            $errors[] = "Please select at least one student";
        }
        
        if ($term_id <= 0) {
            $errors[] = "Please select a term";
        }
        
        if (empty($academic_year)) {
            $errors[] = "Please select an academic year";
        }
        
        if (empty($issue_date)) {
            $errors[] = "Issue date is required";
        }
        
        if (empty($due_date)) {
            $errors[] = "Due date is required";
        }
        
        if (strtotime($due_date) < strtotime($issue_date)) {
            $errors[] = "Due date cannot be earlier than issue date";
        }
        
        // Check if fee structure exists
        if (empty($fee_structure)) {
            $errors[] = "No fee structure found for the selected class, term, and academic year";
        }
        
        // If no errors, generate invoices
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                $success_count = 0;
                $skipped_count = 0;
                
                foreach ($student_ids as $student_id) {
                    // Check if invoice already exists for this student, term, and academic year
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM fee_invoices 
                                           WHERE student_id = ? AND term_id = ? AND academic_year = ?");
                    $stmt->execute([(int)$student_id, $term_id, $academic_year]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        // Skip if invoice already exists
                        $skipped_count++;
                        continue;
                    }
                    
                    // Get student details for concession calculation
                    $stmt = $conn->prepare("SELECT id, class_id FROM students WHERE id = ?");
                    $stmt->execute([(int)$student_id]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$student) {
                        continue; // Skip if student not found
                    }
                    
                    // Generate invoice number
                    $invoice_number = 'INV-' . date('Ymd') . '-' . sprintf('%04d', $student_id) . '-' . sprintf('%03d', $term_id);
                    
                    // Calculate total amount and create invoice
                    $total_amount = 0;
                    $total_concession = 0;
                    $net_amount = 0;
                    
                    // Insert invoice
                    $stmt = $conn->prepare("INSERT INTO fee_invoices 
                                           (invoice_number, student_id, class_id, term_id, 
                                            academic_year, issue_date, due_date, 
                                            total_amount, concession_amount, net_amount, 
                                            paid_amount, balance_amount, status, notes, created_by) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $invoice_number,
                        (int)$student_id,
                        $student['class_id'],
                        $term_id,
                        $academic_year,
                        $issue_date,
                        $due_date,
                        0, // Placeholder for total_amount
                        0, // Placeholder for concession_amount
                        0, // Placeholder for net_amount
                        0, // paid_amount
                        0, // balance_amount
                        'Unpaid',
                        $notes,
                        $_SESSION['user_id']
                    ]);
                    
                    $invoice_id = $conn->lastInsertId();
                    
                    // Add invoice items and calculate totals
                    foreach ($fee_structure as $fee) {
                        $fee_amount = $fee['amount'];
                        $concession_amount = 0;
                        
                        // Check for concessions
                        $stmt = $conn->prepare("SELECT * FROM fee_concessions 
                                               WHERE student_id = ? AND fee_category_id = ? AND 
                                               valid_from <= ? AND 
                                               (valid_until IS NULL OR valid_until >= ?)");
                        $stmt->execute([
                            (int)$student_id, 
                            $fee['fee_category_id'], 
                            $issue_date, 
                            $issue_date
                        ]);
                        $concession = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($concession) {
                            if ($concession['concession_type'] == 'Percentage') {
                                $concession_amount = ($fee_amount * $concession['concession_value']) / 100;
                            } else { // Fixed Amount
                                $concession_amount = min($concession['concession_value'], $fee_amount);
                            }
                        }
                        
                        $net_fee_amount = $fee_amount - $concession_amount;
                        
                        // Add invoice item
                        $stmt = $conn->prepare("INSERT INTO fee_invoice_items 
                                               (invoice_id, fee_category_id, amount, 
                                                concession_amount, net_amount) 
                                               VALUES (?, ?, ?, ?, ?)");
                        
                        $stmt->execute([
                            $invoice_id,
                            $fee['fee_category_id'],
                            $fee_amount,
                            $concession_amount,
                            $net_fee_amount
                        ]);
                        
                        $total_amount += $fee_amount;
                        $total_concession += $concession_amount;
                    }
                    
                    $net_amount = $total_amount - $total_concession;
                    
                    // Update invoice with calculated totals
                    $stmt = $conn->prepare("UPDATE fee_invoices 
                                           SET total_amount = ?, concession_amount = ?, 
                                               net_amount = ?, balance_amount = ? 
                                           WHERE id = ?");
                    
                    $stmt->execute([
                        $total_amount,
                        $total_concession,
                        $net_amount,
                        $net_amount, // balance_amount = net_amount initially
                        $invoice_id
                    ]);
                    
                    $success_count++;
                }
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'bulk_invoices_generated',
                    "Generated {$success_count} fee invoices in bulk",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                
                if ($success_count > 0) {
                    $success_message = "Successfully generated {$success_count} fee invoices";
                    if ($skipped_count > 0) {
                        $success_message .= ". Skipped {$skipped_count} students who already have invoices for the selected term and academic year.";
                    }
                } else {
                    $errors[] = "No invoices were generated. All selected students already have invoices for the selected term and academic year.";
                }
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Fees Management</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i>
                    Select Class, Term and Academic Year
                </div>
                <div class="card-body">
                    <form action="" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class</label>
                                    <select class="form-select" id="class_id" name="class_id" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="term_id" class="form-label">Term</label>
                                    <select class="form-select" id="term_id" name="term_id" required>
                                        <option value="">Select Term</option>
                                        <?php foreach ($terms as $term): ?>
                                            <option value="<?php echo $term['id']; ?>" <?php echo ($term_id == $term['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($term['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="academic_year" class="form-label">Academic Year</label>
                                    <select class="form-select" id="academic_year" name="academic_year" required>
                                        <option value="">Select Year</option>
                                        <?php foreach ($academic_years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo ($academic_year == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i> Load Students & Fee Structure
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($class_id > 0 && $term_id > 0 && !empty($academic_year)): ?>
        <?php if (empty($fee_structure)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-1"></i>
                No fee structure found for the selected class, term, and academic year. Please define a fee structure first.
            </div>
        <?php else: ?>
            <div class="row mb-4">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-money-bill me-1"></i>
                            Fee Structure Details
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fee Category</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fee_structure as $fee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fee['category_name']); ?></td>
                                                <td>$<?php echo number_format($fee['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-primary">
                                            <th>Total</th>
                                            <th>$<?php echo number_format($total_fee_amount, 2); ?></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                Note: Individual student concessions will be automatically applied when generating invoices.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <form action="" method="POST" id="bulkInvoiceForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="term_id" value="<?php echo $term_id; ?>">
                <input type="hidden" name="academic_year" value="<?php echo $academic_year; ?>">
                
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-users me-1"></i>
                                Select Students
                            </div>
                            <div class="card-body">
                                <?php if (empty($students)): ?>
                                    <div class="alert alert-info">
                                        No students found in the selected class.
                                    </div>
                                <?php else: ?>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label" for="selectAll">
                                                Select All Students
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="studentsTable">
                                            <thead>
                                                <tr>
                                                    <th width="50">Select</th>
                                                    <th>Admission No</th>
                                                    <th>Student Name</th>
                                                    <th>Class</th>
                                                    <th>Existing Invoices</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $student): 
                                                    // Check for existing invoices
                                                    $stmt = $conn->prepare("SELECT i.invoice_number, i.issue_date, i.status 
                                                                           FROM fee_invoices i 
                                                                           WHERE i.student_id = ? AND i.term_id = ? AND i.academic_year = ?
                                                                           ORDER BY i.issue_date DESC");
                                                    $stmt->execute([$student['id'], $term_id, $academic_year]);
                                                    $existing_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                ?>
                                                    <tr>
                                                        <td class="text-center">
                                                            <div class="form-check">
                                                                <input class="form-check-input student-checkbox" type="checkbox" 
                                                                       name="student_ids[]" value="<?php echo $student['id']; ?>" 
                                                                       id="student_<?php echo $student['id']; ?>" 
                                                                       <?php echo !empty($existing_invoices) ? 'disabled' : ''; ?>>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                                                        <td>
                                                            <?php if (empty($existing_invoices)): ?>
                                                                <span class="text-success">No invoice yet</span>
                                                            <?php else: ?>
                                                                <?php foreach ($existing_invoices as $invoice): ?>
                                                                    <div>
                                                                        <span class="badge bg-info"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                                                                        <span class="badge bg-<?php echo $invoice['status'] == 'Paid' ? 'success' : ($invoice['status'] == 'Partially Paid' ? 'warning' : 'danger'); ?>">
                                                                            <?php echo $invoice['status']; ?>
                                                                        </span>
                                                                        <small class="text-muted">(<?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?>)</small>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
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
                
                <?php if (!empty($students)): ?>
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-file-invoice-dollar me-1"></i>
                                    Invoice Details
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="issue_date" class="form-label">Issue Date</label>
                                                <input type="date" class="form-control" id="issue_date" name="issue_date" 
                                                       required value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="due_date" class="form-label">Due Date</label>
                                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                                       required value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <button type="submit" name="generate_invoices" class="btn btn-primary" id="submitBtn" disabled>
                                            <i class="fas fa-file-invoice me-1"></i> Generate Invoices
                                        </button>
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        if (document.getElementById('studentsTable')) {
            new DataTable('#studentsTable', {
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                columnDefs: [
                    { orderable: false, targets: 0 }
                ]
            });
        }
        
        // Handle select all checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox:not([disabled])');
        const submitBtn = document.getElementById('submitBtn');
        
        if (selectAllCheckbox && studentCheckboxes.length > 0) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                
                studentCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                
                updateSubmitButtonState();
            });
            
            studentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSubmitButtonState();
                    
                    // Update select all checkbox state
                    const allChecked = Array.from(studentCheckboxes).every(cb => cb.checked);
                    const noneChecked = Array.from(studentCheckboxes).every(cb => !cb.checked);
                    
                    if (allChecked) {
                        selectAllCheckbox.checked = true;
                        selectAllCheckbox.indeterminate = false;
                    } else if (noneChecked) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    } else {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = true;
                    }
                });
            });
        }
        
        function updateSubmitButtonState() {
            if (submitBtn) {
                const anyChecked = Array.from(studentCheckboxes).some(cb => cb.checked);
                submitBtn.disabled = !anyChecked;
            }
        }
        
        // Form validation
        const bulkInvoiceForm = document.getElementById('bulkInvoiceForm');
        
        if (bulkInvoiceForm) {
            bulkInvoiceForm.addEventListener('submit', function(e) {
                const anyChecked = Array.from(studentCheckboxes).some(cb => cb.checked);
                
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Please select at least one student');
                    return false;
                }
                
                const issueDate = document.getElementById('issue_date').value;
                const dueDate = document.getElementById('due_date').value;
                
                if (!issueDate || !dueDate) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return false;
                }
                
                if (new Date(dueDate) < new Date(issueDate)) {
                    e.preventDefault();
                    alert('Due date cannot be earlier than issue date');
                    return false;
                }
                
                return true;
            });
        }
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>