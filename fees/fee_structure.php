<?php
/**
 * Fees Management Module - Fee Structure
 * Manages fee categories and fee structures for different classes and terms
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
$pageTitle = "Fee Structure Management";
$currentPage = "fees";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success_message = '';
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$term_filter = isset($_GET['term']) ? $_GET['term'] : '';
$academic_year_filter = isset($_GET['academic_year']) ? $_GET['academic_year'] : date('Y');

// Get all classes
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all fee categories
$category_query = "SELECT * FROM fee_categories ORDER BY name";
$stmt = $conn->prepare($category_query);
$stmt->execute();
$fee_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get terms
$terms = ['Term 1', 'Term 2', 'Term 3', 'Annual'];

// Get academic years (current year and next 5 years)
$current_year = (int)date('Y');
$academic_years = [];
for ($i = 0; $i < 6; $i++) {
    $year = $current_year + $i;
    $academic_years[] = $year . '-' . ($year + 1);
}

// Process fee category form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $category_name = trim($_POST['category_name']);
        $category_description = trim($_POST['category_description']);
        
        // Validate category name
        if (empty($category_name)) {
            $errors[] = "Category name is required";
        }
        
        // Check if category name already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM fee_categories WHERE name = ?");
        $stmt->execute([$category_name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Fee category with this name already exists";
        }
        
        // If no errors, insert category
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("INSERT INTO fee_categories (name, description) VALUES (?, ?)");
                $stmt->execute([$category_name, $category_description]);
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'fee_category_added',
                    "Added new fee category: {$category_name}",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $success_message = "Fee category added successfully";
                
                // Refresh fee categories
                $stmt = $conn->prepare($category_query);
                $stmt->execute();
                $fee_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Process fee structure form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee_structure'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $class_id = (int)$_POST['class_id'];
        $term = $_POST['term'];
        $academic_year = $_POST['academic_year'];
        $due_date = $_POST['due_date'];
        $fee_categories = isset($_POST['fee_category']) ? $_POST['fee_category'] : [];
        $fee_amounts = isset($_POST['fee_amount']) ? $_POST['fee_amount'] : [];
        
        // Validate required fields
        if ($class_id <= 0) {
            $errors[] = "Please select a class";
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
        
        if (empty($fee_categories) || empty($fee_amounts)) {
            $errors[] = "At least one fee category and amount must be specified";
        }
        
        // If no errors, insert fee structure
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Check for existing fee structure for this class, term and academic year
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM fee_structures 
                                             WHERE class_id = ? AND term = ? AND academic_year = ?");
                $check_stmt->execute([$class_id, $term, $academic_year]);
                
                if ($check_stmt->fetchColumn() > 0) {
                    // Delete existing fee structure
                    $delete_stmt = $conn->prepare("DELETE FROM fee_structures 
                                                 WHERE class_id = ? AND term = ? AND academic_year = ?");
                    $delete_stmt->execute([$class_id, $term, $academic_year]);
                }
                
                // Insert new fee structure
                $insert_stmt = $conn->prepare("INSERT INTO fee_structures 
                                             (class_id, fee_category_id, amount, term, academic_year, due_date) 
                                             VALUES (?, ?, ?, ?, ?, ?)");
                
                foreach ($fee_categories as $index => $category_id) {
                    if (isset($fee_amounts[$index]) && $fee_amounts[$index] > 0) {
                        $insert_stmt->execute([
                            $class_id,
                            $category_id,
                            $fee_amounts[$index],
                            $term,
                            $academic_year,
                            $due_date
                        ]);
                    }
                }
                
                // Get class name for activity log
                $class_stmt = $conn->prepare("SELECT name, section FROM classes WHERE id = ?");
                $class_stmt->execute([$class_id]);
                $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
                $class_name = $class_info['name'] . ' ' . $class_info['section'];
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'fee_structure_updated',
                    "Updated fee structure for {$class_name}, {$term}, {$academic_year}",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                $success_message = "Fee structure saved successfully";
                
                // Update filters to show the newly added structure
                $class_filter = $class_id;
                $term_filter = $term;
                $academic_year_filter = $academic_year;
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get fee structure based on filters
$structure_params = [];
$structure_where = [];

if ($class_filter > 0) {
    $structure_where[] = "fs.class_id = ?";
    $structure_params[] = $class_filter;
}

if (!empty($term_filter)) {
    $structure_where[] = "fs.term = ?";
    $structure_params[] = $term_filter;
}

if (!empty($academic_year_filter)) {
    $structure_where[] = "fs.academic_year = ?";
    $structure_params[] = $academic_year_filter;
}

$structure_where_clause = !empty($structure_where) ? "WHERE " . implode(" AND ", $structure_where) : "";

$structure_query = "SELECT fs.*, c.name as class_name, c.section, fc.name as category_name 
                   FROM fee_structures fs 
                   LEFT JOIN classes c ON fs.class_id = c.id 
                   LEFT JOIN fee_categories fc ON fs.fee_category_id = fc.id 
                   $structure_where_clause 
                   ORDER BY c.name, c.section, fc.name";

$stmt = $conn->prepare($structure_query);
$stmt->execute($structure_params);
$fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group fee structures by class, term, and academic year
$grouped_structures = [];
foreach ($fee_structures as $structure) {
    $key = $structure['class_id'] . '_' . $structure['term'] . '_' . $structure['academic_year'];
    if (!isset($grouped_structures[$key])) {
        $grouped_structures[$key] = [
            'class_id' => $structure['class_id'],
            'class_name' => $structure['class_name'] . ' ' . $structure['section'],
            'term' => $structure['term'],
            'academic_year' => $structure['academic_year'],
            'due_date' => $structure['due_date'],
            'items' => []
        ];
    }
    
    $grouped_structures[$key]['items'][] = [
        'id' => $structure['id'],
        'category_name' => $structure['category_name'],
        'amount' => $structure['amount']
    ];
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
        <!-- Fee Categories Card -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tags me-1"></i>
                    Fee Categories
                </div>
                <div class="card-body">
                    <!-- Add Category Form -->
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Description</label>
                            <textarea class="form-control" id="category_description" name="category_description" rows="2"></textarea>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add Category
                        </button>
                    </form>
                    
                    <hr>
                    
                    <!-- Categories List -->
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($fee_categories) > 0): ?>
                                    <?php foreach ($fee_categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary edit-category" 
                                                        data-id="<?php echo $category['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>" 
                                                        data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-category" 
                                                        data-id="<?php echo $category['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No fee categories found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fee Structure Card -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-money-bill-wave me-1"></i>
                    Fee Structure
                </div>
                <div class="card-body">
                    <!-- Add Fee Structure Form -->
                    <form action="" method="POST" id="feeStructureForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="class_id" class="form-label">Class</label>
                                <select class="form-select" id="class_id" name="class_id" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>">
                                            <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fee Categories</label>
                            <div id="fee_categories_container">
                                <div class="row mb-2 fee-category-row">
                                    <div class="col-md-6">
                                        <select class="form-select" name="fee_category[]" required>
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
                                            <input type="number" class="form-control" name="fee_amount[]" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-success add-fee-category">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_fee_structure" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Fee Structure
                        </button>
                    </form>
                    
                    <hr>
                    
                    <!-- Fee Structure Filter -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form action="" method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <select name="class" class="form-select" onchange="this.form.submit()">
                                        <option value="0">All Classes</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>" <?php echo ($class_filter == $class['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="term" class="form-select" onchange="this.form.submit()">
                                        <option value="">All Terms</option>
                                        <?php foreach ($terms as $term): ?>
                                            <option value="<?php echo $term; ?>" <?php echo ($term_filter == $term) ? 'selected' : ''; ?>>
                                                <?php echo $term; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="academic_year" class="form-select" onchange="this.form.submit()">
                                        <option value="">All Academic Years</option>
                                        <?php foreach ($academic_years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo ($academic_year_filter == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Fee Structure List -->
                    <?php if (count($grouped_structures) > 0): ?>
                        <?php foreach ($grouped_structures as $structure): ?>
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($structure['class_name']); ?></strong> - 
                                            <?php echo htmlspecialchars($structure['term'] . ' (' . $structure['academic_year'] . ')'); ?>
                                        </div>
                                        <div>
                                            <span class="badge bg-info">Due: <?php echo date('d M, Y', strtotime($structure['due_date'])); ?></span>
                                            <button class="btn btn-sm btn-primary edit-structure" 
                                                    data-class="<?php echo $structure['class_id']; ?>" 
                                                    data-term="<?php echo $structure['term']; ?>" 
                                                    data-year="<?php echo $structure['academic_year']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-structure" 
                                                    data-class="<?php echo $structure['class_id']; ?>" 
                                                    data-term="<?php echo $structure['term']; ?>" 
                                                    data-year="<?php echo $structure['academic_year']; ?>" 
                                                    data-class-name="<?php echo htmlspecialchars($structure['class_name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Fee Category</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total = 0;
                                            foreach ($structure['items'] as $item): 
                                                $total += $item['amount'];
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                                    <td class="text-end">$<?php echo number_format($item['amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-active">
                                                <th>Total</th>
                                                <th class="text-end">$<?php echo number_format($total, 2); ?></th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No fee structures found for the selected filters.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Fee Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm" action="update_category.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_category_description" name="category_description" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the fee category: <span id="categoryName"></span>?</p>
                <p class="text-danger"><strong>Warning:</strong> This will also delete all fee structures associated with this category.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteCategoryForm" action="delete_category.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Structure Modal -->
<div class="modal fade" id="deleteStructureModal" tabindex="-1" aria-labelledby="deleteStructureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteStructureModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the fee structure for: <span id="structureInfo"></span>?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteStructureForm" action="delete_structure.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="class_id" id="delete_structure_class">
                    <input type="hidden" name="term" id="delete_structure_term">
                    <input type="hidden" name="academic_year" id="delete_structure_year">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Structure</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add fee category row
        const addFeeCategoryBtn = document.querySelector('.add-fee-category');
        const feeCategoriesContainer = document.getElementById('fee_categories_container');
        
        addFeeCategoryBtn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'row mb-2 fee-category-row';
            row.innerHTML = `
                <div class="col-md-6">
                    <select class="form-select" name="fee_category[]" required>
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
                        <input type="number" class="form-control" name="fee_amount[]" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-fee-category">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            `;
            
            feeCategoriesContainer.appendChild(row);
            
            // Add event listener to remove button
            row.querySelector('.remove-fee-category').addEventListener('click', function() {
                feeCategoriesContainer.removeChild(row);
            });
        });
        
        // Handle edit category button click
        const editCategoryButtons = document.querySelectorAll('.edit-category');
        editCategoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.getAttribute('data-id');
                const categoryName = this.getAttribute('data-name');
                const categoryDescription = this.getAttribute('data-description');
                
                document.getElementById('edit_category_id').value = categoryId;
                document.getElementById('edit_category_name').value = categoryName;
                document.getElementById('edit_category_description').value = categoryDescription;
                
                const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                editModal.show();
            });
        });
        
        // Handle save category button click
        document.getElementById('saveCategoryBtn').addEventListener('click', function() {
            document.getElementById('editCategoryForm').submit();
        });
        
        // Handle delete category button click
        const deleteCategoryButtons = document.querySelectorAll('.delete-category');
        deleteCategoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.getAttribute('data-id');
                const categoryName = this.getAttribute('data-name');
                
                document.getElementById('delete_category_id').value = categoryId;
                document.getElementById('categoryName').textContent = categoryName;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
                deleteModal.show();
            });
        });
        
        // Handle edit structure button click
        const editStructureButtons = document.querySelectorAll('.edit-structure');
        editStructureButtons.forEach(button => {
            button.addEventListener('click', function() {
                const classId = this.getAttribute('data-class');
                const term = this.getAttribute('data-term');
                const year = this.getAttribute('data-year');
                
                // Set form values
                document.getElementById('class_id').value = classId;
                document.getElementById('term').value = term;
                document.getElementById('academic_year').value = year;
                
                // Scroll to form
                document.getElementById('feeStructureForm').scrollIntoView({ behavior: 'smooth' });
            });
        });
        
        // Handle delete structure button click
        const deleteStructureButtons = document.querySelectorAll('.delete-structure');
        deleteStructureButtons.forEach(button => {
            button.addEventListener('click', function() {
                const classId = this.getAttribute('data-class');
                const term = this.getAttribute('data-term');
                const year = this.getAttribute('data-year');
                const className = this.getAttribute('data-class-name');
                
                document.getElementById('delete_structure_class').value = classId;
                document.getElementById('delete_structure_term').value = term;
                document.getElementById('delete_structure_year').value = year;
                document.getElementById('structureInfo').textContent = `${className}, ${term}, ${year}`;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteStructureModal'));
                deleteModal.show();
            });
        });
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>