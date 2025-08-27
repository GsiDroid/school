<?php
/**
 * Export Expense Reports
 * Exports expense reports to Excel format
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

// Get filter parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t'); // Last day of current month
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

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
$report_title = '';

switch ($report_type) {
    case 'daily':
        $report_title = 'Daily Expense Report';
        $query = "SELECT DATE(e.expense_date) as date, SUM(e.amount) as total_amount, COUNT(*) as count 
                 FROM expenses e 
                 $where_clause 
                 GROUP BY DATE(e.expense_date) 
                 ORDER BY date ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'monthly':
        $report_title = 'Monthly Expense Report';
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
        break;
        
    case 'category':
        $report_title = 'Expense Report by Category';
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
        break;
        
    case 'payment_method':
        $report_title = 'Expense Report by Payment Method';
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
        break;
}

// Get detailed expense list
$detail_query = "SELECT e.*, c.name as category_name 
                FROM expenses e 
                LEFT JOIN expense_categories c ON e.category_id = c.id 
                $where_clause 
                ORDER BY e.expense_date DESC";
$stmt = $conn->prepare($detail_query);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $report_title . '_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .subheader {
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header"><?php echo $report_title; ?></div>
    <div class="subheader">Generated on: <?php echo date('Y-m-d H:i:s'); ?></div>
    <div class="subheader">Period: <?php echo date('Y-m-d', strtotime($date_from)); ?> to <?php echo date('Y-m-d', strtotime($date_to)); ?></div>
    
    <h3>Summary Statistics</h3>
    <table>
        <tr>
            <th>Total Amount</th>
            <th>Number of Expenses</th>
            <th>Average Amount</th>
            <th>Minimum Amount</th>
            <th>Maximum Amount</th>
        </tr>
        <tr>
            <td><?php echo number_format($stats['total_amount'] ?? 0, 2); ?></td>
            <td><?php echo $stats['total_count'] ?? 0; ?></td>
            <td><?php echo number_format($stats['average_amount'] ?? 0, 2); ?></td>
            <td><?php echo number_format($stats['min_amount'] ?? 0, 2); ?></td>
            <td><?php echo number_format($stats['max_amount'] ?? 0, 2); ?></td>
        </tr>
    </table>
    
    <h3>Report Data</h3>
    <table>
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
            <th>Total Amount</th>
            <th>Count</th>
        </tr>
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
    </table>
    
    <h3>Detailed Expense List</h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Reference</th>
            <th>Title</th>
            <th>Category</th>
            <th>Amount</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th>Description</th>
        </tr>
        <?php foreach ($expenses as $expense): ?>
            <tr>
                <td><?php echo date('d M, Y', strtotime($expense['expense_date'])); ?></td>
                <td><?php echo htmlspecialchars($expense['reference_no']); ?></td>
                <td><?php echo htmlspecialchars($expense['title']); ?></td>
                <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                <td><?php echo number_format($expense['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($expense['payment_method']); ?></td>
                <td><?php echo ucfirst($expense['status']); ?></td>
                <td><?php echo htmlspecialchars($expense['description']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>