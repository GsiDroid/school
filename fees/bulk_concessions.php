<?php
/**
 * Fees Management Module - Bulk Concessions
 * Handles adding fee concessions for multiple students at once
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
$pageTitle = "Bulk Fee Concessions";
$currentPage = "fees";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success_message = '';
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get all classes for filter
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all fee categories
$category_query = "SELECT * FROM fee_categories ORDER BY name";
$stmt = $conn->prepare($category_query);
$stmt->execute();
$fee_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get concession types
$concession_types = ['Percentage', 'Fixed Amount'];

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

// Process form submission for adding bulk concessions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bulk_concessions'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
        $fee_category_id = isset($_POST['fee_category_id']) ? (int)$_POST['fee_category_id'] : 0;
        $concession_type = isset($_POST['concession_type']) ? trim($_POST['concession_type']) : '';
        $concession_value = isset($_POST['concession_value']) ? (float)$_POST['concession_value'] : 0;
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        $valid_from = isset($_POST['valid_from']) ? $_POST['valid_from'] : '';
        $valid_until = isset($_POST['valid_until']) && !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // Validate required fields
        if (empty($student_ids)) {
            $errors[] = "Please select at least one student";
        }
        
        if ($fee_category_id <= 0) {
            $errors[] = "Please select a fee category";
        }
        
        if (empty($concession_type) || !in_array($concession_type, $concession_types)) {
            $errors[] = "Please select a valid concession type";
        }
        
        if ($concession_value <= 0) {
            $errors[] = "Concession value must be greater than zero";
        }
        
        if ($concession_type == 'Percentage' && $concession_value > 100) {
            $errors[] = "Percentage concession cannot exceed 100%";
        }
        
        if (empty($reason)) {
            $errors[] = "Reason is required";
        }
        
        if (empty($valid_from)) {
            $errors[] = "Valid from date is required";
        }
        
        // If no errors, save concessions
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                $success_count = 0;
                $skipped_count = 0;
                
                foreach ($student_ids as $student_id) {
                    // Check if concession already exists for this student and fee category
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM fee_concessions 
                                           WHERE student_id = ? AND fee_category_id = ? AND 
                                           ((valid_until IS NULL) OR (valid_until >= CURRENT_DATE()))");
                    $stmt->execute([(int)$student_id, $fee_category_id]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        // Skip if concession already exists
                        $skipped_count++;
                        continue;
                    }
                    
                    // Add new concession
                    $stmt = $conn->prepare("INSERT INTO fee_concessions 
                                           (student_id, fee_category_id, concession_type, 
                                            concession_value, reason, valid_from, valid_until, 
                                            notes, created_by) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        (int)$student_id,
                        $fee_category_id,
                        $concession_type,
                        $concession_value,
                        $reason,
                        $valid_from,
                        $valid_until,
                        $notes,
                        $_SESSION['user_id']
                    ]);
                    
                    $success_count++;
                }
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'bulk_concessions_added',
                    "Added {$success_count} fee concessions in bulk",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                
                if ($success_count > 0) {
                    $success_message = "Successfully added {$success_count} fee concessions";
                    if ($skipped_count > 0) {
                        $success_message .= ". Skipped {$skipped_count} students who already have active concessions for the selected fee category.";
                    }
                } else {
                    $errors[] = "No concessions were added. All selected students already have active concessions for the selected fee category.";
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
        <li class="breadcrumb-item"><a href="concessions.php">Fee Concessions</a></li>
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
                    Filter Students
                </div>
                <div class="card-body">
                    <form action="" method="GET">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Select Class</label>
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
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i> Filter Students
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($class_id > 0): ?>
        <form action="" method="POST" id="bulkConcessionForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
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
                                                <th>Existing Concessions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): 
                                                // Check for existing concessions
                                                $stmt = $conn->prepare("SELECT fc.*, fcat.name as category_name 
                                                                       FROM fee_concessions fc 
                                                                       JOIN fee_categories fcat ON fc.fee_category_id = fcat.id 
                                                                       WHERE fc.student_id = ? AND 
                                                                       ((fc.valid_until IS NULL) OR (fc.valid_until >= CURRENT_DATE()))
                                                                       ORDER BY fcat.name");
                                                $stmt->execute([$student['id']]);
                                                $existing_concessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <div class="form-check">
                                                            <input class="form-check-input student-checkbox" type="checkbox" 
                                                                   name="student_ids[]" value="<?php echo $student['id']; ?>" 
                                                                   id="student_<?php echo $student['id']; ?>">
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                                                    <td>
                                                        <?php if (empty($existing_concessions)): ?>
                                                            <span class="text-muted">None</span>
                                                        <?php else: ?>
                                                            <ul class="list-unstyled mb-0">
                                                                <?php foreach ($existing_concessions as $concession): ?>
                                                                    <li>
                                                                        <?php echo htmlspecialchars($concession['category_name']); ?>: 
                                                                        <?php if ($concession['concession_type'] == 'Percentage'): ?>
                                                                            <?php echo $concession['concession_value']; ?>%
                                                                        <?php else: ?>
                                                                            $<?php echo number_format($concession['concession_value'], 2); ?>
                                                                        <?php endif; ?>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
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
                                <i class="fas fa-percent me-1"></i>
                                Concession Details
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fee_category_id" class="form-label">Fee Category</label>
                                            <select class="form-select" id="fee_category_id" name="fee_category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($fee_categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="concession_type" class="form-label">Concession Type</label>
                                            <select class="form-select" id="concession_type" name="concession_type" required>
                                                <option value="">Select Type</option>
                                                <?php foreach ($concession_types as $type): ?>
                                                    <option value="<?php echo $type; ?>">
                                                        <?php echo $type; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="concession_value" class="form-label">Concession Value</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="concession_value" name="concession_value" 
                                                       step="0.01" min="0" max="100" required>
                                                <span class="input-group-text" id="value_suffix">%</span>
                                            </div>
                                            <div class="form-text" id="value_help">For percentage, enter a value between 0 and 100</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reason" class="form-label">Reason</label>
                                            <select class="form-select" id="reason" name="reason" required>
                                                <option value="">Select Reason</option>
                                                <option value="Financial Hardship">Financial Hardship</option>
                                                <option value="Staff Child">Staff Child</option>
                                                <option value="Sibling Discount">Sibling Discount</option>
                                                <option value="Merit Scholarship">Merit Scholarship</option>
                                                <option value="Sports Scholarship">Sports Scholarship</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="valid_from" class="form-label">Valid From</label>
                                            <input type="date" class="form-control" id="valid_from" name="valid_from" 
                                                   required value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="valid_until" class="form-label">Valid Until (Optional)</label>
                                            <input type="date" class="form-control" id="valid_until" name="valid_until">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="submit" name="save_bulk_concessions" class="btn btn-primary" id="submitBtn" disabled>
                                        <i class="fas fa-save me-1"></i> Apply Concessions
                                    </button>
                                    <a href="concessions.php" class="btn btn-secondary">
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
        
        // Handle concession type change
        const concessionTypeSelect = document.getElementById('concession_type');
        const concessionValueInput = document.getElementById('concession_value');
        const valueSuffix = document.getElementById('value_suffix');
        const valueHelp = document.getElementById('value_help');
        
        if (concessionTypeSelect) {
            concessionTypeSelect.addEventListener('change', function() {
                if (this.value === 'Percentage') {
                    valueSuffix.textContent = '%';
                    concessionValueInput.setAttribute('max', '100');
                    valueHelp.textContent = 'For percentage, enter a value between 0 and 100';
                } else {
                    valueSuffix.textContent = '$';
                    concessionValueInput.removeAttribute('max');
                    valueHelp.textContent = 'Enter the fixed amount to be deducted';
                }
            });
        }
        
        // Handle select all checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox');
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
        const bulkConcessionForm = document.getElementById('bulkConcessionForm');
        
        if (bulkConcessionForm) {
            bulkConcessionForm.addEventListener('submit', function(e) {
                const anyChecked = Array.from(studentCheckboxes).some(cb => cb.checked);
                
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Please select at least one student');
                    return false;
                }
                
                const feeCategory = document.getElementById('fee_category_id').value;
                const concessionType = document.getElementById('concession_type').value;
                const concessionValue = document.getElementById('concession_value').value;
                const reason = document.getElementById('reason').value;
                const validFrom = document.getElementById('valid_from').value;
                
                if (!feeCategory || !concessionType || !concessionValue || !reason || !validFrom) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return false;
                }
                
                if (concessionType === 'Percentage' && (concessionValue <= 0 || concessionValue > 100)) {
                    e.preventDefault();
                    alert('Percentage concession must be between 0 and 100');
                    return false;
                }
                
                if (concessionType === 'Fixed Amount' && concessionValue <= 0) {
                    e.preventDefault();
                    alert('Fixed amount concession must be greater than 0');
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