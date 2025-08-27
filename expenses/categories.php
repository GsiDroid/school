<?php
/**
 * Expense Categories Management
 * Allows creating, editing and deleting expense categories
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
$pageTitle = "Expense Categories";
$currentPage = "expenses";

// Process form submission for adding/editing categories
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new category
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            
            if (!empty($name)) {
                $stmt = $conn->prepare("INSERT INTO expense_categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                
                $_SESSION['success_message'] = "Category added successfully!";
                header("Location: categories.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Category name cannot be empty!";
            }
        }
        
        // Update existing category
        if ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            
            if (!empty($name)) {
                $stmt = $conn->prepare("UPDATE expense_categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                
                $_SESSION['success_message'] = "Category updated successfully!";
                header("Location: categories.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Category name cannot be empty!";
            }
        }
        
        // Delete category
        if ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            
            // Check if category is in use
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM expenses WHERE category_id = ?");
            $check_stmt->execute([$id]);
            $count = $check_stmt->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['error_message'] = "Cannot delete category because it is used by {$count} expenses!";
            } else {
                $stmt = $conn->prepare("DELETE FROM expense_categories WHERE id = ?");
                $stmt->execute([$id]);
                
                $_SESSION['success_message'] = "Category deleted successfully!";
            }
            
            header("Location: categories.php");
            exit();
        }
    }
}

// Get all categories
$stmt = $conn->prepare("SELECT c.*, COUNT(e.id) as expense_count, SUM(e.amount) as total_amount 
                       FROM expense_categories c 
                       LEFT JOIN expenses e ON c.id = e.category_id 
                       GROUP BY c.id 
                       ORDER BY c.name ASC");
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
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-tags me-1"></i>
                Expense Categories
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="categoriesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Expenses Count</th>
                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                    <td><?php echo $category['expense_count']; ?></td>
                                    <td><?php echo number_format($category['total_amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-primary edit-category" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="index.php?category=<?php echo $category['id']; ?>" class="btn btn-sm btn-info" title="View Expenses">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-category" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    data-count="<?php echo $category['expense_count']; ?>"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No categories found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editCategoryId">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="editCategoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editCategoryDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category: <strong id="deleteCategoryName"></strong>?</p>
                <div id="categoryInUseWarning" class="alert alert-warning d-none">
                    <i class="fas fa-exclamation-triangle"></i> This category is used by <span id="expenseCount"></span> expenses. You cannot delete it until you reassign or delete those expenses.
                </div>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteCategoryForm" action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#categoriesTable').DataTable({
            "ordering": true,
            "info": true,
            "paging": true,
            "responsive": true
        });
        
        // Handle edit category button click
        $('.edit-category').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const description = $(this).data('description');
            
            $('#editCategoryId').val(id);
            $('#editCategoryName').val(name);
            $('#editCategoryDescription').val(description);
            
            $('#editCategoryModal').modal('show');
        });
        
        // Handle delete category button click
        $('.delete-category').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const count = parseInt($(this).data('count'));
            
            $('#deleteCategoryId').val(id);
            $('#deleteCategoryName').text(name);
            
            if (count > 0) {
                $('#expenseCount').text(count);
                $('#categoryInUseWarning').removeClass('d-none');
                $('#confirmDeleteBtn').prop('disabled', true);
            } else {
                $('#categoryInUseWarning').addClass('d-none');
                $('#confirmDeleteBtn').prop('disabled', false);
            }
            
            $('#deleteCategoryModal').modal('show');
        });
    });
</script>