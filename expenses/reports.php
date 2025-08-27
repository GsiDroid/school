<?php
/**
 * Expense Reports
 * Provides various expense reports and analytics
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
$pageTitle = "Expense Reports";
$currentPage = "expenses";

// Get filter parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t'); // Last day of current month
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get all expense categories for filter dropdown
$stmt = $conn->prepare("SELECT id, name FROM expense_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query based on filters
$params = [];
$where_clauses = [];

if (!empty($date_from)) {
    $where_clauses[] = "e.expense_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "e.expense_date <= ?";
    $params[] = $date_to;
}

if ($category_id > 0) {
    $where_clauses[] = "e.category_id = ?";
    $params[] = $category_id;
}

if (!empty($payment_method)) {
    $where_clauses[] = "e.payment_method = ?";
    $params[] = $payment_method;
}

if (!empty($status)) {
    $where_clauses[] = "e.status = ?";
    $params[] = $status;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get expense data based on report type
$report_data = [];
$chart_labels = [];
$chart_values = [];

switch ($report_type) {
    case 'daily':
        $query = "SELECT DATE(e.expense_date) as date, SUM(e.amount) as total_amount, COUNT(*) as count 
                 FROM expenses e 
                 $where_clause 
                 GROUP BY DATE(e.expense_date) 
                 ORDER BY date ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($report_data as $row) {
            $chart_labels[] = date('d M', strtotime($row['date']));
            $chart_values[] = $row['total_amount'];
        }
        break;
        
    case 'monthly':
        $query = "SELECT DATE_FORMAT(e.expense_date, '%Y-%m') as month, 
                         SUM(e.amount) as total_amount, 
                         COUNT(*) as count 
                 FROM expenses e 
                 $where_clause 
                 GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m') 
                 ORDER BY month ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($report_data as $row) {
            $chart_labels[] = date('M Y', strtotime($row['month'] . '-01'));
            $chart_values[] = $row['total_amount'];
        }
        break;
        
    case 'category':
        $query = "SELECT c.name as category_name, 
                         SUM(e.amount) as total_amount, 
                         COUNT(*) as count 
                 FROM expenses e 
                 LEFT JOIN expense_categories c ON e.category_id = c.id 
                 $where_clause 
                 GROUP BY e.category_id, c.name 
                 ORDER BY total_amount DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($report_data as $row) {
            $chart_labels[] = $row['category_name'];
            $chart_values[] = $row['total_amount'];
        }
        break;
        
    case 'payment_method':
        $query = "SELECT e.payment_method, 
                         SUM(e.amount) as total_amount, 
                         COUNT(*) as count 
                 FROM expenses e 
                 $where_clause 
                 GROUP BY e.payment_method 
                 ORDER BY total_amount DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($report_data as $row) {
            $chart_labels[] = $row['payment_method'];
            $chart_values[] = $row['total_amount'];
        }
        break;
}

// Get summary statistics
$stats_query = "SELECT 
                SUM(amount) as total_amount,
                COUNT(*) as total_count,
                AVG(amount) as average_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
              FROM expenses e 
              $where_clause";
$stmt = $conn->prepare($stats_query);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

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
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Report Filters
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select name="report_type" id="report_type" class="form-select" onchange="this.form.submit()">
                        <option value="daily" <?php echo ($report_type == 'daily') ? 'selected' : ''; ?>>Daily Report</option>
                        <option value="monthly" <?php echo ($report_type == 'monthly') ? 'selected' : ''; ?>>Monthly Report</option>
                        <option value="category" <?php echo ($report_type == 'category') ? 'selected' : ''; ?>>Category Report</option>
                        <option value="payment_method" <?php echo ($report_type == 'payment_method') ? 'selected' : ''; ?>>Payment Method Report</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="Cash" <?php echo ($payment_method == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                        <option value="Bank Transfer" <?php echo ($payment_method == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="Credit Card" <?php echo ($payment_method == 'Credit Card') ? 'selected' : ''; ?>>Credit Card</option>
                        <option value="Debit Card" <?php echo ($payment_method == 'Debit Card') ? 'selected' : ''; ?>>Debit Card</option>
                        <option value="Check" <?php echo ($payment_method == 'Check') ? 'selected' : ''; ?>>Check</option>
                        <option value="Mobile Payment" <?php echo ($payment_method == 'Mobile Payment') ? 'selected' : ''; ?>>Mobile Payment</option>
                        <option value="Other" <?php echo ($payment_method == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        <option value="approved" <?php echo ($status == 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="rejected" <?php echo ($status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="reports.php" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                        <button type="button" class="btn btn-success" id="exportBtn">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                        <button type="button" class="btn btn-info" id="printBtn">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Total Expenses</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Number of Expenses</h5>
                            <h3 class="mb-0"><?php echo $stats['total_count'] ?? 0; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-list fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Average Expense</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['average_amount'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Highest Expense</h5>
                            <h3 class="mb-0"><?php echo number_format($stats['max_amount'] ?? 0, 2); ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-arrow-up fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    <?php 
                    $report_title = '';
                    switch ($report_type) {
                        case 'daily':
                            $report_title = 'Daily Expenses';
                            break;
                        case 'monthly':
                            $report_title = 'Monthly Expenses';
                            break;
                        case 'category':
                            $report_title = 'Expenses by Category';
                            break;
                        case 'payment_method':
                            $report_title = 'Expenses by Payment Method';
                            break;
                    }
                    echo $report_title;
                    ?>
                </div>
                <div class="card-body">
                    <canvas id="expenseChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Report Summary
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <?php if ($report_type == 'daily'): ?>
                                        <th>Date</th>
                                    <?php elseif ($report_type == 'monthly'): ?>
                                        <th>Month</th>
                                    <?php elseif ($report_type == 'category'): ?>
                                        <th>Category</th>
                                    <?php elseif ($report_type == 'payment_method'): ?>
                                        <th>Payment Method</th>
                                    <?php endif; ?>
                                    <th>Amount</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($report_data) > 0): ?>
                                    <?php foreach ($report_data as $row): ?>
                                        <tr>
                                            <?php if ($report_type == 'daily'): ?>
                                                <td><?php echo date('d M, Y', strtotime($row['date'])); ?></td>
                                            <?php elseif ($report_type == 'monthly'): ?>
                                                <td><?php echo date('M Y', strtotime($row['month'] . '-01')); ?></td>
                                            <?php elseif ($report_type == 'category'): ?>
                                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            <?php elseif ($report_type == 'payment_method'): ?>
                                                <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td><?php echo $row['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No data found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-list me-1"></i>
            Detailed Expense List
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get detailed expense list
                        $detail_query = "SELECT e.*, c.name as category_name 
                                        FROM expenses e 
                                        LEFT JOIN expense_categories c ON e.category_id = c.id 
                                        $where_clause 
                                        ORDER BY e.expense_date DESC";
                        $stmt = $conn->prepare($detail_query);
                        $stmt->execute($params);
                        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($expenses) > 0):
                            foreach ($expenses as $expense):
                        ?>
                            <tr>
                                <td><?php echo date('d M, Y', strtotime($expense['expense_date'])); ?></td>
                                <td><?php echo htmlspecialchars($expense['reference_no']); ?></td>
                                <td>
                                    <a href="view_expense.php?id=<?php echo $expense['id']; ?>">
                                        <?php echo htmlspecialchars($expense['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                <td><?php echo number_format($expense['amount'], 2); ?></td>
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
                            </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="7" class="text-center">No expenses found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#expensesTable').DataTable({
            "ordering": true,
            "info": true,
            "paging": true,
            "responsive": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
        
        // Initialize Chart
        const ctx = document.getElementById('expenseChart');
        
        <?php if (!empty($chart_labels) && !empty($chart_values)): ?>
        const chartType = <?php echo ($report_type == 'category' || $report_type == 'payment_method') ? '"pie"' : '"bar"'; ?>;
        
        if (chartType === 'pie') {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($chart_values); ?>,
                        backgroundColor: [
                            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                            '#5a5c69', '#858796', '#6f42c1', '#20c9a6', '#fd7e14'
                        ],
                        hoverBackgroundColor: [
                            '#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617',
                            '#3a3b45', '#60616f', '#5a35a0', '#169b7f', '#ca6510'
                        ],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                const dataset = data.datasets[tooltipItem.datasetIndex];
                                const value = dataset.data[tooltipItem.index];
                                const label = data.labels[tooltipItem.index];
                                return label + ': $' + parseFloat(value).toFixed(2);
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'right'
                    }
                }
            });
        } else {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        label: 'Expense Amount',
                        backgroundColor: '#4e73df',
                        hoverBackgroundColor: '#2e59d9',
                        borderColor: '#4e73df',
                        data: <?php echo json_encode($chart_values); ?>,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                maxTicksLimit: 20
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                min: 0,
                                maxTicksLimit: 5,
                                padding: 10,
                                callback: function(value) {
                                    return '$' + value;
                                }
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                return 'Amount: $' + parseFloat(tooltipItem.yLabel).toFixed(2);
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
        
        // Handle print button
        $('#printBtn').click(function() {
            window.print();
        });
        
        // Handle export button
        $('#exportBtn').click(function() {
            window.location.href = 'export_report.php' + window.location.search;
        });
    });
</script>