<?php
/**
 * Expenses Management Module - Main Page
 * Lists all expenses with search, filter and pagination
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
$pageTitle = "Expenses Management";
$currentPage = "expenses";

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$params = [];
$where_clauses = [];

if (!empty($search)) {
    $where_clauses[] = "(e.description LIKE ? OR e.reference_no LIKE ?)"; 
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $where_clauses[] = "e.expense_category_id = ?";
    $params[] = $category_filter;
}

if (!empty($date_from)) {
    $where_clauses[] = "e.expense_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "e.expense_date <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM expenses e 
               LEFT JOIN expense_categories c ON e.expense_category_id = c.id 
               $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get expenses with category information
$query = "SELECT e.*, c.name as category_name 
          FROM expenses e 
          LEFT JOIN expense_categories c ON e.expense_category_id = c.id 
          $where_clause 
          ORDER BY e.expense_date DESC 
          LIMIT $offset, $per_page";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all expense categories for filter dropdown
$category_query = "SELECT id, name FROM expense_categories ORDER BY name";
$stmt = $conn->prepare($category_query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get expense statistics
$stats_query = "SELECT 
                SUM(amount) as total_expenses,
                COUNT(*) as expense_count,
                AVG(amount) as average_expense,
                MAX(amount) as highest_expense
              FROM expenses";
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
    
    <!-- Expense Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Total Expenses</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['total_expenses'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="categories.php">View Categories</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Expense Count</h5>
                            <h3 class="mb-0"><?php echo $stats['expense_count'] ?? 0; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-list fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php">View All</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Average Expense</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['average_expense'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="reports.php">View Reports</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Highest Expense</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['highest_expense'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php?sort=amount&order=desc">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-file-invoice-dollar me-1"></i>
                Expenses List
            </div>
            <div>
                <div class="btn-group" role="group">
                    <a href="add_expense.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Expense
                    </a>
                    <a href="categories.php" class="btn btn-info btn-sm">
                        <i class="fas fa-tags"></i> Categories
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
                                <input type="text" class="form-control" placeholder="Search expenses" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="date_from" placeholder="From Date" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="date_to" placeholder="To Date" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-3">
                            <div class="btn-group" role="group">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="index.php" class="btn btn-secondary">Reset</a>
                                <button type="button" class="btn btn-success" id="exportBtn">
                                    <i class="fas fa-file-excel"></i> Export
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Expenses Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($expenses) > 0): ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($expense['reference_no']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($expense['title']); ?>
                                        <?php if (!empty($expense['description'])): ?>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars(substr($expense['description'], 0, 50)) . (strlen($expense['description']) > 50 ? '...' : ''); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                    <td><?php echo number_format($expense['amount'], 2); ?></td>
                                    <td><?php echo date('d M, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['payment_method']); ?></td>
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
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-expense" 
                                                    data-id="<?php echo $expense['id']; ?>" 
                                                    data-title="<?php echo htmlspecialchars($expense['title']); ?>" 
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No expenses found</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
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
                <p>Are you sure you want to delete the expense: <strong id="expenseTitle"></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteExpenseForm" action="delete_expense.php" method="POST">
                    <input type="hidden" name="expense_id" id="expenseId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#expensesTable').DataTable({
            "paging": false,  // Disable DataTables pagination as we're using custom pagination
            "ordering": true,
            "info": false,
            "searching": false, // Disable DataTables search as we're using custom search
            "responsive": true
        });
        
        // Handle delete expense button click
        $('.delete-expense').click(function() {
            const id = $(this).data('id');
            const title = $(this).data('title');
            
            $('#expenseId').val(id);
            $('#expenseTitle').text(title);
            $('#deleteExpenseModal').modal('show');
        });
        
        // Handle export button click
        $('#exportBtn').click(function() {
            window.location.href = 'export_expenses.php' + window.location.search;
        });
    });
</script>