<?php
/**
 * Fees Management Module - Generate Invoice
 * Handles creation of fee invoices for students
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
$pageTitle = "Generate Fee Invoice";
$currentPage = "fees";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success_message = '';
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$student = null;

// Get all classes
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get terms
$terms = ['Term 1', 'Term 2', 'Term 3', 'Annual'];

// Get academic years (current year and next 5 years)
$current_year = (int)date('Y');
$academic_years = [];
for ($i = 0; $i < 6; $i++) {
    $year = $current_year + $i;
    $academic_years[] = $year . '-' . ($year + 1);
}

// If student ID is provided, get student details
if ($student_id > 0) {
    $stmt = $conn->prepare("SELECT s.*, c.name as class_name, c.section 
                           FROM students s 
                           JOIN classes c ON s.class_id = c.id 
                           WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        $class_id = $student['class_id'];
    }
}

// Get students based on class filter
$students = [];
if ($class_id > 0) {
    $stmt = $conn->prepare("SELECT id, admission_number, first_name, last_name 
                           FROM students 
                           WHERE class_id = ? AND status = 'active' 
                           ORDER BY first_name, last_name");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $invoice_student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $term = isset($_POST['term']) ? trim($_POST['term']) : '';
        $academic_year = isset($_POST['academic_year']) ? trim($_POST['academic_year']) : '';
        $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : '';
        $issue_date = isset($_POST['issue_date']) ? $_POST['issue_date'] : date('Y-m-d');
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        $fee_structure = isset($_POST['use_fee_structure']) && $_POST['use_fee_structure'] == '1';
        $custom_fees = isset($_POST['custom_fees']) && $_POST['custom_fees'] == '1';
        
        // Validate required fields
        if ($invoice_student_id <= 0) {
            $errors[] = "Please select a student";
        }
        
        if (empty($term)) {
            $errors[] = "Please select a term";
        }
        
        if (empty($academic_year)) {
            $errors[] = "Please select an academic year";
        }
        
        if (empty($due_date)) {
            $errors[] = "Due date is required";
        }
        
        if (empty($issue_date)) {
            $errors[] = "Issue date is required";
        }
        
        // Get student class for fee structure
        if ($invoice_student_id > 0) {
            $stmt = $conn->prepare("SELECT class_id FROM students WHERE id = ?");
            $stmt->execute([$invoice_student_id]);
            $student_class_id = $stmt->fetchColumn();
        }
        
        // Check if invoice already exists for this student, term and academic year
        $stmt = $conn->prepare("SELECT COUNT(*) FROM fee_invoices 
                               WHERE student_id = ? AND term = ? AND academic_year = ?");
        $stmt->execute([$invoice_student_id, $term, $academic_year]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "An invoice already exists for this student, term and academic year";
        }
        
        // If using fee structure, check if it exists
        $fee_items = [];
        $total_amount = 0;
        
        if ($fee_structure && empty($errors)) {
            $stmt = $conn->prepare("SELECT fs.*, fc.name as category_name 
                                   FROM fee_structures fs 
                                   JOIN fee_categories fc ON fs.fee_category_id = fc.id 
                                   WHERE fs.class_id = ? AND fs.term = ? AND fs.academic_year = ?");
            $stmt->execute([$student_class_id, $term, $academic_year]);
            $fee_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($fee_items)) {
                $errors[] = "No fee structure found for this class, term and academic year";
            } else {
                foreach ($fee_items as $item) {
                    $total_amount += $item['amount'];
                }
            }
        }
        
        // If using custom fees
        if ($custom_fees && empty($errors)) {
            $fee_categories = isset($_POST['fee_category']) ? $_POST['fee_category'] : [];
            $fee_amounts = isset($_POST['fee_amount']) ? $_POST['fee_amount'] : [];
            
            if (empty($fee_categories) || empty($fee_amounts)) {
                $errors[] = "At least one fee category and amount must be specified";
            } else {
                $fee_items = [];
                for ($i = 0; $i < count($fee_categories); $i++) {
                    if (!empty($fee_categories[$i]) && isset($fee_amounts[$i]) && $fee_amounts[$i] > 0) {
                        // Get category name
                        $stmt = $conn->prepare("SELECT name FROM fee_categories WHERE id = ?");
                        $stmt->execute([(int)$fee_categories[$i]]);
                        $category_name = $stmt->fetchColumn();
                        
                        $fee_items[] = [
                            'fee_category_id' => (int)$fee_categories[$i],
                            'category_name' => $category_name,
                            'amount' => (float)$fee_amounts[$i]
                        ];
                        
                        $total_amount += (float)$fee_amounts[$i];
                    }
                }
                
                if (empty($fee_items)) {
                    $errors[] = "At least one valid fee category and amount must be specified";
                }
            }
        }
        
        // If no errors, generate invoice
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Generate invoice number (format: INV-YEAR-XXXXX)
                $year = date('Y');
                $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(invoice_number, 10) AS UNSIGNED)) 
                                       FROM fee_invoices 
                                       WHERE invoice_number LIKE ?");
                $stmt->execute(["INV-{$year}-%"]);
                $max_number = $stmt->fetchColumn();
                $next_number = $max_number ? $max_number + 1 : 1;
                $invoice_number = "INV-{$year}-" . str_pad($next_number, 5, '0', STR_PAD_LEFT);
                
                // Insert invoice
                $stmt = $conn->prepare("INSERT INTO fee_invoices 
                                       (student_id, invoice_number, term, academic_year, 
                                        issue_date, due_date, total_amount, balance, status, notes, created_by) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $invoice_student_id,
                    $invoice_number,
                    $term,
                    $academic_year,
                    $issue_date,
                    $due_date,
                    $total_amount,
                    $total_amount, // Initial balance equals total amount
                    'pending',     // Initial status is pending
                    $notes,
                    $_SESSION['user_id']
                ]);
                
                $invoice_id = $conn->lastInsertId();
                
                // Insert invoice items
                $stmt = $conn->prepare("INSERT INTO fee_invoice_items 
                                       (invoice_id, fee_category_id, amount) 
                                       VALUES (?, ?, ?)");
                
                foreach ($fee_items as $item) {
                    $stmt->execute([
                        $invoice_id,
                        $item['fee_category_id'],
                        $item['amount']
                    ]);
                }
                
                // Get student name for activity log
                $stmt = $conn->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
                $stmt->execute([$invoice_student_id]);
                $student_info = $stmt->fetch(PDO::FETCH_ASSOC);
                $student_name = $student_info['first_name'] . ' ' . $student_info['last_name'];
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'invoice_generated',
                    "Generated invoice #{$invoice_number} for {$student_name}, {$term}, {$academic_year}",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                $success_message = "Invoice #{$invoice_number} generated successfully for {$student_name}";
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get all fee categories for custom fees
$category_query = "SELECT * FROM fee_categories ORDER BY name";
$stmt = $conn->prepare($category_query);
$stmt->execute();
$fee_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <div class="mt-2">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-list me-1"></i> View All Invoices
                </a>
                <a href="generate_invoice.php" class="btn btn-secondary">
                    <i class="fas fa-plus me-1"></i> Generate Another Invoice
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-invoice-dollar me-1"></i>
            Generate New Invoice
        </div>
        <div class="card-body">
            <!-- Student Selection Form -->
            <?php if (empty($student)): ?>
                <form action="" method="GET" class="mb-4">
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label for="class_id" class="form-label">Select Class</label>
                            <select class="form-select" id="class_id" name="class_id" onchange="this.form.submit()">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if ($class_id > 0 && !empty($students)): ?>
                            <div class="col-md-5">
                                <label for="student_id" class="form-label">Select Student</label>
                                <select class="form-select" id="student_id" name="student_id">
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?php echo $s['id']; ?>">
                                            <?php echo htmlspecialchars($s['admission_number'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Select</button>
                            </div>
                        <?php elseif ($class_id > 0 && empty($students)): ?>
                            <div class="col-md-7">
                                <div class="alert alert-warning mb-0">
                                    No active students found in this class.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Invoice Generation Form -->
            <?php if ($student): ?>
                <form action="" method="POST" id="invoiceForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Student Information</h5>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <th width="30%">Name:</th>
                                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Admission No:</th>
                                            <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Class:</th>
                                            <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="term" class="form-label">Term</label>
                            <select class="form-select" id="term" name="term" required>
                                <option value="">Select Term</option>
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?php echo $term; ?>"><?php echo $term; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <select class="form-select" id="academic_year" name="academic_year" required>
                                <option value="">Select Year</option>
                                <?php foreach ($academic_years as $year): ?>
                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="issue_date" class="form-label">Issue Date</label>
                            <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="fee_option" id="use_fee_structure" value="structure" checked>
                            <label class="form-check-label" for="use_fee_structure">Use Fee Structure</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="fee_option" id="use_custom_fees" value="custom">
                            <label class="form-check-label" for="use_custom_fees">Custom Fees</label>
                        </div>
                    </div>
                    
                    <!-- Fee Structure Section -->
                    <div id="fee_structure_section" class="mb-4">
                        <input type="hidden" name="use_fee_structure" value="1">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-1"></i>
                            The system will automatically apply the fee structure for the selected class, term, and academic year.
                            Please make sure a fee structure exists for this combination.
                        </div>
                    </div>
                    
                    <!-- Custom Fees Section -->
                    <div id="custom_fees_section" class="mb-4" style="display: none;">
                        <input type="hidden" name="custom_fees" value="1">
                        <h5>Custom Fee Items</h5>
                        <div id="fee_items_container">
                            <div class="row mb-2 fee-item-row">
                                <div class="col-md-6">
                                    <select class="form-select" name="fee_category[]">
                                        <option value="">Select Fee Category</option>
                                        <?php foreach ($fee_categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="fee_amount[]" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-success add-fee-item">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" name="generate_invoice" class="btn btn-primary">
                            <i class="fas fa-file-invoice me-1"></i> Generate Invoice
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Invoices
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set due date default to 30 days from today
        const dueDateInput = document.getElementById('due_date');
        if (dueDateInput) {
            const today = new Date();
            today.setDate(today.getDate() + 30);
            const dueDate = today.toISOString().split('T')[0];
            dueDateInput.value = dueDate;
        }
        
        // Handle fee option toggle
        const feeStructureRadio = document.getElementById('use_fee_structure');
        const customFeesRadio = document.getElementById('use_custom_fees');
        const feeStructureSection = document.getElementById('fee_structure_section');
        const customFeesSection = document.getElementById('custom_fees_section');
        
        if (feeStructureRadio && customFeesRadio) {
            feeStructureRadio.addEventListener('change', function() {
                if (this.checked) {
                    feeStructureSection.style.display = 'block';
                    customFeesSection.style.display = 'none';
                    document.querySelector('input[name="use_fee_structure"]').value = '1';
                    document.querySelector('input[name="custom_fees"]').value = '0';
                }
            });
            
            customFeesRadio.addEventListener('change', function() {
                if (this.checked) {
                    feeStructureSection.style.display = 'none';
                    customFeesSection.style.display = 'block';
                    document.querySelector('input[name="use_fee_structure"]').value = '0';
                    document.querySelector('input[name="custom_fees"]').value = '1';
                }
            });
        }
        
        // Add fee item row
        const addFeeItemBtn = document.querySelector('.add-fee-item');
        const feeItemsContainer = document.getElementById('fee_items_container');
        
        if (addFeeItemBtn && feeItemsContainer) {
            addFeeItemBtn.addEventListener('click', function() {
                const row = document.createElement('div');
                row.className = 'row mb-2 fee-item-row';
                row.innerHTML = `
                    <div class="col-md-6">
                        <select class="form-select" name="fee_category[]">
                            <option value="">Select Fee Category</option>
                            <?php foreach ($fee_categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="fee_amount[]" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-fee-item">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                `;
                
                feeItemsContainer.appendChild(row);
                
                // Add event listener to remove button
                row.querySelector('.remove-fee-item').addEventListener('click', function() {
                    feeItemsContainer.removeChild(row);
                });
            });
        }
        
        // Form validation
        const invoiceForm = document.getElementById('invoiceForm');
        if (invoiceForm) {
            invoiceForm.addEventListener('submit', function(event) {
                const termSelect = document.getElementById('term');
                const academicYearSelect = document.getElementById('academic_year');
                const issueDateInput = document.getElementById('issue_date');
                const dueDateInput = document.getElementById('due_date');
                
                let isValid = true;
                
                if (!termSelect.value) {
                    termSelect.classList.add('is-invalid');
                    isValid = false;
                } else {
                    termSelect.classList.remove('is-invalid');
                }
                
                if (!academicYearSelect.value) {
                    academicYearSelect.classList.add('is-invalid');
                    isValid = false;
                } else {
                    academicYearSelect.classList.remove('is-invalid');
                }
                
                if (!issueDateInput.value) {
                    issueDateInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    issueDateInput.classList.remove('is-invalid');
                }
                
                if (!dueDateInput.value) {
                    dueDateInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    dueDateInput.classList.remove('is-invalid');
                }
                
                // Check if custom fees are selected and validate
                if (customFeesRadio.checked) {
                    const feeCategories = document.querySelectorAll('select[name="fee_category[]"]');
                    const feeAmounts = document.querySelectorAll('input[name="fee_amount[]"]');
                    let hasValidFee = false;
                    
                    for (let i = 0; i < feeCategories.length; i++) {
                        if (feeCategories[i].value && feeAmounts[i].value > 0) {
                            hasValidFee = true;
                            break;
                        }
                    }
                    
                    if (!hasValidFee) {
                        alert('Please add at least one valid fee item with category and amount.');
                        isValid = false;
                    }
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>