<?php
/**
 * View Expense Details
 * Displays detailed information about a specific expense
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
$pageTitle = "Expense Details";
$currentPage = "expenses";

// Check if expense ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No expense ID provided";
    header("Location: index.php");
    exit();
}

$expense_id = (int)$_GET['id'];

// Get expense details with category information
$stmt = $conn->prepare("SELECT e.*, c.name as category_name, u.username as created_by_name 
                       FROM expenses e 
                       LEFT JOIN expense_categories c ON e.category_id = c.id 
                       LEFT JOIN users u ON e.created_by = u.id 
                       WHERE e.id = ?");
$stmt->execute([$expense_id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if expense exists
if (!$expense) {
    $_SESSION['error_message'] = "Expense not found";
    header("Location: index.php");
    exit();
}

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
    
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-file-invoice-dollar me-1"></i>
                        Expense Information
                    </div>
                    <div>
                        <div class="btn-group" role="group">
                            <a href="edit_expense.php?id=<?php echo $expense_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="print_expense.php?id=<?php echo $expense_id; ?>" class="btn btn-info btn-sm" target="_blank">
                                <i class="fas fa-print"></i> Print
                            </a>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteExpenseModal">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 35%">Title:</th>
                                    <td><?php echo htmlspecialchars($expense['title']); ?></td>
                                </tr>
                                <tr>
                                    <th>Amount:</th>
                                    <td><strong class="text-primary"><?php echo number_format($expense['amount'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Category:</th>
                                    <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Expense Date:</th>
                                    <td><?php echo date('d M, Y', strtotime($expense['expense_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Payment Method:</th>
                                    <td><?php echo htmlspecialchars($expense['payment_method']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 35%">Reference No:</th>
                                    <td><?php echo htmlspecialchars($expense['reference_no']); ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($expense['status']) {
                                            case 'approved':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'pending':
                                                $status_class = 'bg-warning';
                                                break;
                                            case 'rejected':
                                                $status_class = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($expense['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created By:</th>
                                    <td><?php echo htmlspecialchars($expense['created_by_name'] ?? 'Unknown'); ?></td>
                                </tr>
                                <tr>
                                    <th>Created On:</th>
                                    <td><?php echo date('d M, Y H:i', strtotime($expense['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>
                                        <?php 
                                        echo !empty($expense['updated_at']) ? 
                                            date('d M, Y H:i', strtotime($expense['updated_at'])) : 
                                            'Not updated';
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($expense['description'])): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Description</h5>
                            <p class="border p-3 bg-light"><?php echo nl2br(htmlspecialchars($expense['description'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <?php if (!empty($expense['receipt_file'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-file-alt me-1"></i>
                    Receipt/Invoice
                </div>
                <div class="card-body text-center">
                    <?php 
                    $file_path = '../uploads/expenses/' . $expense['receipt_file'];
                    $file_extension = strtolower(pathinfo($expense['receipt_file'], PATHINFO_EXTENSION));
                    
                    if (in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                        // Display image
                        echo '<img src="' . $file_path . '" class="img-fluid mb-3" alt="Receipt">';
                    } else if ($file_extension === 'pdf') {
                        // Display PDF icon
                        echo '<div class="mb-3"><i class="fas fa-file-pdf fa-5x text-danger"></i></div>';
                    }
                    ?>
                    <a href="<?php echo $file_path; ?>" class="btn btn-primary" target="_blank">
                        <i class="fas fa-download"></i> Download Receipt
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Related Expenses
                </div>
                <div class="card-body">
                    <?php
                    // Get related expenses (same category, excluding current)
                    $stmt = $conn->prepare("SELECT id, title, amount, expense_date 
                                           FROM expenses 
                                           WHERE category_id = ? AND id != ? 
                                           ORDER BY expense_date DESC 
                                           LIMIT 5");
                    $stmt->execute([$expense['category_id'], $expense_id]);
                    $related_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($related_expenses) > 0):
                    ?>
                    <div class="list-group">
                        <?php foreach ($related_expenses as $related): ?>
                        <a href="view_expense.php?id=<?php echo $related['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($related['title']); ?></h6>
                                <small><?php echo date('d M', strtotime($related['expense_date'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo number_format($related['amount'], 2); ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No related expenses found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Expense Modal -->
<div class="modal fade" id="deleteExpenseModal" tabindex="-1" aria-labelledby="deleteExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteExpenseModalLabel">Delete Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this expense: <strong><?php echo htmlspecialchars($expense['title']); ?></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form action="delete_expense.php" method="POST">
                    <input type="hidden" name="expense_id" value="<?php echo $expense_id; ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>