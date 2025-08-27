<?php
/**
 * Fees Management Module - Concessions
 * Handles fee concessions for students
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
$pageTitle = "Fee Concessions";
$currentPage = "fees";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success_message = '';
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$concession_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

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

// Process form submission for adding/editing concession
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_concession'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $edit_id = isset($_POST['concession_id']) ? (int)$_POST['concession_id'] : 0;
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $fee_category_id = isset($_POST['fee_category_id']) ? (int)$_POST['fee_category_id'] : 0;
        $concession_type = isset($_POST['concession_type']) ? trim($_POST['concession_type']) : '';
        $concession_value = isset($_POST['concession_value']) ? (float)$_POST['concession_value'] : 0;
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        $valid_from = isset($_POST['valid_from']) ? $_POST['valid_from'] : '';
        $valid_until = isset($_POST['valid_until']) ? $_POST['valid_until'] : null;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // Validate required fields
        if ($student_id <= 0) {
            $errors[] = "Please select a student";
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
        
        // Check if concession already exists for this student and fee category
        if ($edit_id == 0) { // Only check for new concessions
            $stmt = $conn->prepare("SELECT COUNT(*) FROM fee_concessions 
                                   WHERE student_id = ? AND fee_category_id = ? AND 
                                   ((valid_until IS NULL) OR (valid_until >= CURRENT_DATE()))");
            $stmt->execute([$student_id, $fee_category_id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "An active concession already exists for this student and fee category";
            }
        }
        
        // If no errors, save concession
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                if ($edit_id > 0) {
                    // Update existing concession
                    $stmt = $conn->prepare("UPDATE fee_concessions 
                                           SET fee_category_id = ?, concession_type = ?, 
                                               concession_value = ?, reason = ?, valid_from = ?, 
                                               valid_until = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
                                           WHERE id = ?");
                    
                    $stmt->execute([
                        $fee_category_id,
                        $concession_type,
                        $concession_value,
                        $reason,
                        $valid_from,
                        $valid_until,
                        $notes,
                        $edit_id
                    ]);
                    
                    $action_type = 'concession_updated';
                    $action_desc = "Updated fee concession for student ID #{$student_id}";
                } else {
                    // Add new concession
                    $stmt = $conn->prepare("INSERT INTO fee_concessions 
                                           (student_id, fee_category_id, concession_type, 
                                            concession_value, reason, valid_from, valid_until, 
                                            notes, created_by) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $student_id,
                        $fee_category_id,
                        $concession_type,
                        $concession_value,
                        $reason,
                        $valid_from,
                        $valid_until,
                        $notes,
                        $_SESSION['user_id']
                    ]);
                    
                    $action_type = 'concession_added';
                    $action_desc = "Added new fee concession for student ID #{$student_id}";
                }
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    $action_type,
                    $action_desc,
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                $success_message = "Fee concession saved successfully";
                
                // Reset form after successful submission
                $student_id = 0;
                $concession_id = 0;
                $action = '';
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Process concession deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_concession'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $delete_id = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
        
        if ($delete_id <= 0) {
            $errors[] = "Invalid concession selected for deletion";
        } else {
            try {
                $conn->beginTransaction();
                
                // Get student ID for activity log
                $stmt = $conn->prepare("SELECT student_id FROM fee_concessions WHERE id = ?");
                $stmt->execute([$delete_id]);
                $student_id_for_log = $stmt->fetchColumn();
                
                // Delete concession
                $stmt = $conn->prepare("DELETE FROM fee_concessions WHERE id = ?");
                $stmt->execute([$delete_id]);
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'concession_deleted',
                    "Deleted fee concession ID #{$delete_id} for student ID #{$student_id_for_log}",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                $success_message = "Fee concession deleted successfully";
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get concession details for editing
$concession = null;
if ($action == 'edit' && $concession_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM fee_concessions WHERE id = ?");
    $stmt->execute([$concession_id]);
    $concession = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($concession) {
        $student_id = $concession['student_id'];
    }
}

// Get student details if student_id is provided
$student = null;
if ($student_id > 0) {
    $stmt = $conn->prepare("SELECT s.*, c.name as class_name, c.section 
                           FROM students s 
                           JOIN classes c ON s.class_id = c.id 
                           WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all concessions with student and category details
$concessions_query = "SELECT fc.*, s.first_name, s.last_name, s.admission_number, 
                      c.name as class_name, c.section, fcat.name as category_name 
                      FROM fee_concessions fc 
                      JOIN students s ON fc.student_id = s.id 
                      JOIN classes c ON s.class_id = c.id 
                      JOIN fee_categories fcat ON fc.fee_category_id = fcat.id 
                      ORDER BY fc.created_at DESC";
$stmt = $conn->prepare($concessions_query);
$stmt->execute();
$concessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <!-- Concession Form -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-percent me-1"></i>
                    <?php echo $concession ? 'Edit' : 'Add'; ?> Fee Concession
                </div>
                <div class="card-body">
                    <?php if ($student): ?>
                        <div class="alert alert-info mb-3">
                            <strong>Student:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?><br>
                            <strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?><br>
                            <strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="concession_id" value="<?php echo $concession ? $concession['id'] : 0; ?>">
                        
                        <?php if (!$student): ?>
                            <div class="mb-3">
                                <label for="student_search" class="form-label">Search Student</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="student_search" placeholder="Enter name or admission number">
                                    <button class="btn btn-outline-secondary" type="button" id="searchStudentBtn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Select Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <!-- Will be populated via AJAX -->
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="fee_category_id" class="form-label">Fee Category</label>
                            <select class="form-select" id="fee_category_id" name="fee_category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($fee_categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($concession && $concession['fee_category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="concession_type" class="form-label">Concession Type</label>
                            <select class="form-select" id="concession_type" name="concession_type" required>
                                <option value="">Select Type</option>
                                <?php foreach ($concession_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo ($concession && $concession['concession_type'] == $type) ? 'selected' : ''; ?>>
                                        <?php echo $type; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="concession_value" class="form-label">Concession Value</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="concession_value" name="concession_value" 
                                       step="0.01" min="0" max="100" required 
                                       value="<?php echo $concession ? $concession['concession_value'] : ''; ?>">
                                <span class="input-group-text" id="value_suffix">%</span>
                            </div>
                            <div class="form-text" id="value_help">For percentage, enter a value between 0 and 100</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <select class="form-select" id="reason" name="reason" required>
                                <option value="">Select Reason</option>
                                <option value="Financial Hardship" <?php echo ($concession && $concession['reason'] == 'Financial Hardship') ? 'selected' : ''; ?>>Financial Hardship</option>
                                <option value="Staff Child" <?php echo ($concession && $concession['reason'] == 'Staff Child') ? 'selected' : ''; ?>>Staff Child</option>
                                <option value="Sibling Discount" <?php echo ($concession && $concession['reason'] == 'Sibling Discount') ? 'selected' : ''; ?>>Sibling Discount</option>
                                <option value="Merit Scholarship" <?php echo ($concession && $concession['reason'] == 'Merit Scholarship') ? 'selected' : ''; ?>>Merit Scholarship</option>
                                <option value="Sports Scholarship" <?php echo ($concession && $concession['reason'] == 'Sports Scholarship') ? 'selected' : ''; ?>>Sports Scholarship</option>
                                <option value="Other" <?php echo ($concession && $concession['reason'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="valid_from" class="form-label">Valid From</label>
                                <input type="date" class="form-control" id="valid_from" name="valid_from" required 
                                       value="<?php echo $concession ? $concession['valid_from'] : date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="valid_until" class="form-label">Valid Until (Optional)</label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                       value="<?php echo $concession && $concession['valid_until'] ? $concession['valid_until'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo $concession ? $concession['notes'] : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" name="save_concession" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> <?php echo $concession ? 'Update' : 'Save'; ?> Concession
                            </button>
                            <a href="concessions.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Concessions List -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-table me-1"></i>
                        Fee Concessions List
                    </div>
                    <div>
                        <a href="bulk_concessions.php" class="btn btn-sm btn-success">
                            <i class="fas fa-users me-1"></i> Bulk Concessions
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="concessionsTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Fee Category</th>
                                    <th>Concession</th>
                                    <th>Reason</th>
                                    <th>Validity</th>
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
                                        <td>
                                            <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($c['admission_number']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?></td>
                                        <td><?php echo htmlspecialchars($c['category_name']); ?></td>
                                        <td>
                                            <?php if ($c['concession_type'] == 'Percentage'): ?>
                                                <?php echo $c['concession_value']; ?>%
                                            <?php else: ?>
                                                $<?php echo number_format($c['concession_value'], 2); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($c['reason']); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($c['valid_from'])); ?>
                                            <?php if ($c['valid_until']): ?>
                                                to <?php echo date('M d, Y', strtotime($c['valid_until'])); ?>
                                            <?php else: ?>
                                                onwards
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
                                                <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger delete-btn" data-id="<?php echo $c['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($concessions)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No concessions found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="delete_id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_concession" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        const concessionsTable = new DataTable('#concessionsTable', {
            order: [[6, 'desc'], [0, 'asc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100]
        });
        
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
            
            // Trigger change event to set initial state
            concessionTypeSelect.dispatchEvent(new Event('change'));
        }
        
        // Handle student search
        const searchStudentBtn = document.getElementById('searchStudentBtn');
        const studentSearchInput = document.getElementById('student_search');
        const studentSelect = document.getElementById('student_id');
        
        if (searchStudentBtn && studentSearchInput && studentSelect) {
            searchStudentBtn.addEventListener('click', function() {
                const searchTerm = studentSearchInput.value.trim();
                if (searchTerm.length < 2) {
                    alert('Please enter at least 2 characters to search');
                    return;
                }
                
                // Use AJAX to search for students
                searchStudentBtn.disabled = true;
                searchStudentBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                
                fetch('search_students.php?term=' + encodeURIComponent(searchTerm))
                    .then(response => response.json())
                    .then(data => {
                        // Reset button
                        searchStudentBtn.disabled = false;
                        searchStudentBtn.innerHTML = '<i class="fas fa-search"></i>';
                        
                        // Clear existing options
                        studentSelect.innerHTML = '';
                        studentSelect.innerHTML += '<option value="">Select Student</option>';
                        
                        // Add new options from search results
                        if (data.results && data.results.length > 0) {
                            data.results.forEach(student => {
                                studentSelect.innerHTML += `<option value="${student.id}">${student.text}</option>`;
                            });
                        } else if (data.error) {
                            alert('Error: ' + data.error);
                        } else {
                            alert('No students found matching: ' + searchTerm);
                        }
                    })
                    .catch(error => {
                        console.error('Error searching students:', error);
                        alert('An error occurred while searching for students');
                        
                        // Reset button
                        searchStudentBtn.disabled = false;
                        searchStudentBtn.innerHTML = '<i class="fas fa-search"></i>';
                    });
            });
            
            // Also allow searching when pressing Enter in the search input
            studentSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchStudentBtn.click();
                }
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