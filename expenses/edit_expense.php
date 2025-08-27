<?php
/**
 * Edit Expense
 * Form to update an existing expense record
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
$pageTitle = "Edit Expense";
$currentPage = "expenses";

// Check if expense ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No expense ID provided";
    header("Location: index.php");
    exit();
}

$expense_id = (int)$_GET['id'];

// Get expense details
$stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->execute([$expense_id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if expense exists
if (!$expense) {
    $_SESSION['error_message'] = "Expense not found";
    header("Location: index.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = trim($_POST['title']);
    $amount = (float)$_POST['amount'];
    $category_id = (int)$_POST['category_id'];
    $expense_date = $_POST['expense_date'];
    $payment_method = trim($_POST['payment_method']);
    $reference_no = trim($_POST['reference_no']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if ($amount <= 0) {
        $errors[] = "Amount must be greater than zero";
    }
    
    if ($category_id <= 0) {
        $errors[] = "Please select a category";
    }
    
    if (empty($expense_date)) {
        $errors[] = "Expense date is required";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Payment method is required";
    }
    
    // If no errors, update the expense
    if (empty($errors)) {
        // Update expense record
        $stmt = $conn->prepare("UPDATE expenses SET 
                              title = ?, 
                              amount = ?, 
                              category_id = ?, 
                              expense_date = ?, 
                              payment_method = ?, 
                              reference_no = ?, 
                              description = ?, 
                              status = ?, 
                              updated_at = NOW() 
                              WHERE id = ?");
        
        $stmt->execute([
            $title, 
            $amount, 
            $category_id, 
            $expense_date, 
            $payment_method, 
            $reference_no, 
            $description, 
            $status, 
            $expense_id
        ]);
        
        // Handle file upload if present
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
            $upload_dir = '../uploads/expenses/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $expense_id . '_' . basename($_FILES['receipt']['name']);
            $target_file = $upload_dir . $file_name;
            
            // Check file type
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (in_array($file_type, $allowed_types)) {
                // Delete old receipt file if exists
                if (!empty($expense['receipt_file'])) {
                    $old_file = $upload_dir . $expense['receipt_file'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
                    // Update expense record with receipt file
                    $stmt = $conn->prepare("UPDATE expenses SET receipt_file = ? WHERE id = ?");
                    $stmt->execute([$file_name, $expense_id]);
                }
            }
        }
        
        // Set success message and redirect
        $_SESSION['success_message'] = "Expense updated successfully!";
        header("Location: view_expense.php?id=" . $expense_id);
        exit();
    }
}

// Get all expense categories for dropdown
$stmt = $conn->prepare("SELECT id, name FROM expense_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Expenses</a></li>
        <li class="breadcrumb-item"><a href="view_expense.php?id=<?php echo $expense_id; ?>">Expense Details</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Edit Expense
        </div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Expense Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($expense['title']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" value="<?php echo htmlspecialchars($expense['amount']); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($expense['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="fas fa-plus-circle"></i> Add New Category
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo htmlspecialchars($expense['expense_date']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="Cash" <?php echo ($expense['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="Bank Transfer" <?php echo ($expense['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="Credit Card" <?php echo ($expense['payment_method'] == 'Credit Card') ? 'selected' : ''; ?>>Credit Card</option>
                                <option value="Debit Card" <?php echo ($expense['payment_method'] == 'Debit Card') ? 'selected' : ''; ?>>Debit Card</option>
                                <option value="Check" <?php echo ($expense['payment_method'] == 'Check') ? 'selected' : ''; ?>>Check</option>
                                <option value="Mobile Payment" <?php echo ($expense['payment_method'] == 'Mobile Payment') ? 'selected' : ''; ?>>Mobile Payment</option>
                                <option value="Other" <?php echo ($expense['payment_method'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="reference_no" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference_no" name="reference_no" value="<?php echo htmlspecialchars($expense['reference_no']); ?>">
                            <div class="form-text">Receipt number, invoice number, etc.</div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="approved" <?php echo ($expense['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="pending" <?php echo ($expense['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="rejected" <?php echo ($expense['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="receipt" class="form-label">Receipt/Invoice (Optional)</label>
                            <input type="file" class="form-control" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text">
                                Supported formats: JPG, PNG, PDF. Max size: 2MB
                                <?php if (!empty($expense['receipt_file'])): ?>
                                    <br>
                                    <a href="../uploads/expenses/<?php echo $expense['receipt_file']; ?>" target="_blank">
                                        <i class="fas fa-file"></i> View Current Receipt
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($expense['description']); ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="view_expense.php?id=<?php echo $expense_id; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="categoryName" class="form-label">Category Name</label>
                    <input type="text" class="form-control" id="categoryName" required>
                </div>
                <div class="mb-3">
                    <label for="categoryDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="categoryDescription" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save Category</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize Select2 for better dropdown experience
        if ($.fn.select2) {
            $('#category_id').select2({
                placeholder: "Select Category",
                allowClear: true
            });
            
            $('#payment_method').select2({
                placeholder: "Select Payment Method",
                allowClear: true
            });
            
            $('#status').select2();
        }
        
        // Handle add category via AJAX
        $('#saveCategoryBtn').click(function() {
            const name = $('#categoryName').val();
            const description = $('#categoryDescription').val();
            
            if (!name) {
                alert('Category name is required');
                return;
            }
            
            // Send AJAX request
            $.ajax({
                url: 'ajax_add_category.php',
                type: 'POST',
                data: {
                    name: name,
                    description: description
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Add new option to select
                        const newOption = new Option(response.category.name, response.category.id, true, true);
                        $('#category_id').append(newOption).trigger('change');
                        
                        // Close modal and reset form
                        $('#addCategoryModal').modal('hide');
                        $('#categoryName').val('');
                        $('#categoryDescription').val('');
                    } else {
                        alert(response.message || 'Error adding category');
                    }
                },
                error: function() {
                    alert('Error adding category. Please try again.');
                }
            });
        });
    });
</script>