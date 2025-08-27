<?php
/**
 * Fees Management Module - Reports
 * Handles fee reports and analytics
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
$pageTitle = "Fee Reports & Analytics";
$currentPage = "fees";

// Initialize variables
$errors = [];
$success_message = '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'collection';
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$term = isset($_GET['term']) ? $_GET['term'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');     // Last day of current month

// Get all classes
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get terms
$terms = ['Term 1', 'Term 2', 'Term 3', 'Annual'];

// Get academic years (current year and previous 5 years)
$current_year = (int)date('Y');
$academic_years = [];
for ($i = -5; $i <= 0; $i++) {
    $year = $current_year + $i;
    $academic_years[] = $year . '-' . ($year + 1);
}

// Initialize report data
$report_data = [];
$chart_data = [];

// Generate report based on type
switch ($report_type) {
    case 'collection':
        // Fee collection report
        $query = "SELECT DATE(fp.payment_date) as payment_date, SUM(fp.amount) as total_amount, 
                 COUNT(DISTINCT fp.invoice_id) as invoice_count 
                 FROM fee_payments fp 
                 JOIN fee_invoices fi ON fp.invoice_id = fi.id 
                 JOIN students s ON fi.student_id = s.id 
                 WHERE fp.payment_date BETWEEN ? AND ? ";
        
        $params = [$start_date, $end_date];
        
        if ($class_id > 0) {
            $query .= " AND s.class_id = ? ";
            $params[] = $class_id;
        }
        
        if (!empty($term)) {
            $query .= " AND fi.term = ? ";
            $params[] = $term;
        }
        
        if (!empty($academic_year)) {
            $query .= " AND fi.academic_year = ? ";
            $params[] = $academic_year;
        }
        
        $query .= " GROUP BY DATE(fp.payment_date) ORDER BY payment_date";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare chart data
        $dates = [];
        $amounts = [];
        
        foreach ($report_data as $row) {
            $dates[] = date('M d', strtotime($row['payment_date']));
            $amounts[] = $row['total_amount'];
        }
        
        $chart_data = [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Fee Collection',
                    'data' => $amounts,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];
        
        // Get summary statistics
        $stmt = $conn->prepare("SELECT SUM(amount) as total_collected, 
                              COUNT(DISTINCT invoice_id) as invoice_count, 
                              COUNT(DISTINCT (SELECT student_id FROM fee_invoices WHERE id = fee_payments.invoice_id)) as student_count 
                              FROM fee_payments 
                              WHERE payment_date BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        break;
        
    case 'outstanding':
        // Outstanding fees report
        $query = "SELECT c.name as class_name, c.section, 
                 COUNT(DISTINCT fi.id) as invoice_count, 
                 SUM(fi.balance) as total_outstanding, 
                 COUNT(DISTINCT fi.student_id) as student_count 
                 FROM fee_invoices fi 
                 JOIN students s ON fi.student_id = s.id 
                 JOIN classes c ON s.class_id = c.id 
                 WHERE fi.balance > 0 ";
        
        $params = [];
        
        if ($class_id > 0) {
            $query .= " AND s.class_id = ? ";
            $params[] = $class_id;
        }
        
        if (!empty($term)) {
            $query .= " AND fi.term = ? ";
            $params[] = $term;
        }
        
        if (!empty($academic_year)) {
            $query .= " AND fi.academic_year = ? ";
            $params[] = $academic_year;
        }
        
        $query .= " GROUP BY c.id ORDER BY c.name, c.section";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare chart data
        $classes_labels = [];
        $outstanding_amounts = [];
        
        foreach ($report_data as $row) {
            $classes_labels[] = $row['class_name'] . ' ' . $row['section'];
            $outstanding_amounts[] = $row['total_outstanding'];
        }
        
        $chart_data = [
            'labels' => $classes_labels,
            'datasets' => [
                [
                    'label' => 'Outstanding Fees',
                    'data' => $outstanding_amounts,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];
        
        // Get summary statistics
        $stmt = $conn->prepare("SELECT SUM(balance) as total_outstanding, 
                              COUNT(DISTINCT id) as invoice_count, 
                              COUNT(DISTINCT student_id) as student_count 
                              FROM fee_invoices 
                              WHERE balance > 0");
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        break;
        
    case 'defaulters':
        // Fee defaulters report
        $query = "SELECT s.admission_number, s.first_name, s.last_name, 
                 c.name as class_name, c.section, 
                 COUNT(fi.id) as invoice_count, 
                 SUM(fi.balance) as total_outstanding, 
                 MAX(fi.due_date) as latest_due_date 
                 FROM fee_invoices fi 
                 JOIN students s ON fi.student_id = s.id 
                 JOIN classes c ON s.class_id = c.id 
                 WHERE fi.balance > 0 AND fi.due_date < CURRENT_DATE() ";
        
        $params = [];
        
        if ($class_id > 0) {
            $query .= " AND s.class_id = ? ";
            $params[] = $class_id;
        }
        
        if (!empty($term)) {
            $query .= " AND fi.term = ? ";
            $params[] = $term;
        }
        
        if (!empty($academic_year)) {
            $query .= " AND fi.academic_year = ? ";
            $params[] = $academic_year;
        }
        
        $query .= " GROUP BY s.id ORDER BY total_outstanding DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get summary statistics
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) as defaulter_count, 
                              SUM(balance) as total_overdue, 
                              AVG(DATEDIFF(CURRENT_DATE(), due_date)) as avg_days_overdue 
                              FROM fee_invoices 
                              WHERE balance > 0 AND due_date < CURRENT_DATE()");
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        break;
        
    case 'category':
        // Fee category breakdown report
        $query = "SELECT fc.name as category_name, 
                 SUM(fii.amount) as total_amount, 
                 COUNT(DISTINCT fi.id) as invoice_count 
                 FROM fee_invoice_items fii 
                 JOIN fee_invoices fi ON fii.invoice_id = fi.id 
                 JOIN fee_categories fc ON fii.fee_category_id = fc.id 
                 JOIN students s ON fi.student_id = s.id 
                 WHERE 1=1 ";
        
        $params = [];
        
        if ($class_id > 0) {
            $query .= " AND s.class_id = ? ";
            $params[] = $class_id;
        }
        
        if (!empty($term)) {
            $query .= " AND fi.term = ? ";
            $params[] = $term;
        }
        
        if (!empty($academic_year)) {
            $query .= " AND fi.academic_year = ? ";
            $params[] = $academic_year;
        }
        
        $query .= " GROUP BY fc.id ORDER BY total_amount DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare chart data
        $categories = [];
        $amounts = [];
        $backgroundColors = [
            'rgba(255, 99, 132, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(255, 206, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)',
            'rgba(153, 102, 255, 0.2)',
            'rgba(255, 159, 64, 0.2)',
            'rgba(199, 199, 199, 0.2)',
            'rgba(83, 102, 255, 0.2)',
            'rgba(40, 159, 64, 0.2)',
            'rgba(210, 199, 199, 0.2)'
        ];
        $borderColors = [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(199, 199, 199, 1)',
            'rgba(83, 102, 255, 1)',
            'rgba(40, 159, 64, 1)',
            'rgba(210, 199, 199, 1)'
        ];
        
        foreach ($report_data as $index => $row) {
            $categories[] = $row['category_name'];
            $amounts[] = $row['total_amount'];
        }
        
        $chart_data = [
            'labels' => $categories,
            'datasets' => [
                [
                    'data' => $amounts,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($categories)),
                    'borderColor' => array_slice($borderColors, 0, count($categories)),
                    'borderWidth' => 1
                ]
            ]
        ];
        
        // Get summary statistics
        $stmt = $conn->prepare("SELECT SUM(fii.amount) as total_amount, 
                              COUNT(DISTINCT fii.fee_category_id) as category_count 
                              FROM fee_invoice_items fii 
                              JOIN fee_invoices fi ON fii.invoice_id = fi.id");
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        break;
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
    
    <!-- Report Type Selection -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-bar me-1"></i>
            Select Report Type
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-group w-100 mb-3" role="group">
                        <a href="?type=collection" class="btn <?php echo $report_type == 'collection' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-money-bill-wave me-1"></i> Fee Collection
                        </a>
                        <a href="?type=outstanding" class="btn <?php echo $report_type == 'outstanding' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-exclamation-circle me-1"></i> Outstanding Fees
                        </a>
                        <a href="?type=defaulters" class="btn <?php echo $report_type == 'defaulters' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-user-clock me-1"></i> Fee Defaulters
                        </a>
                        <a href="?type=category" class="btn <?php echo $report_type == 'category' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-tags me-1"></i> Category Breakdown
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Filter Form -->
            <form action="" method="GET" class="row g-3">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                
                <?php if ($report_type == 'collection'): ?>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                <?php endif; ?>
                
                <div class="col-md-<?php echo $report_type == 'collection' ? '2' : '4'; ?>">
                    <label for="class_id" class="form-label">Class</label>
                    <select class="form-select" id="class_id" name="class_id">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-<?php echo $report_type == 'collection' ? '2' : '4'; ?>">
                    <label for="term" class="form-label">Term</label>
                    <select class="form-select" id="term" name="term">
                        <option value="">All Terms</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo ($term == $t) ? 'selected' : ''; ?>>
                                <?php echo $t; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-<?php echo $report_type == 'collection' ? '2' : '4'; ?>">
                    <label for="academic_year" class="form-label">Academic Year</label>
                    <select class="form-select" id="academic_year" name="academic_year">
                        <option value="">All Years</option>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($academic_year == $year) ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Apply Filters
                    </button>
                    <a href="?type=<?php echo $report_type; ?>" class="btn btn-secondary">
                        <i class="fas fa-undo me-1"></i> Reset Filters
                    </a>
                    <button type="button" class="btn btn-success" id="exportBtn">
                        <i class="fas fa-file-export me-1"></i> Export to Excel
                    </button>
                    <button type="button" class="btn btn-danger" id="printBtn">
                        <i class="fas fa-print me-1"></i> Print Report
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Summary -->
    <div class="row">
        <?php if ($report_type == 'collection' && isset($summary)): ?>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Total Collection</div>
                                <div class="fs-4">$<?php echo number_format($summary['total_collected'] ?? 0, 2); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-success text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Invoices Paid</div>
                                <div class="fs-4"><?php echo number_format($summary['invoice_count'] ?? 0); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-file-invoice-dollar fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-info text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Students Paid</div>
                                <div class="fs-4"><?php echo number_format($summary['student_count'] ?? 0); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($report_type == 'outstanding' && isset($summary)): ?>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-danger text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Total Outstanding</div>
                                <div class="fs-4">$<?php echo number_format($summary['total_outstanding'] ?? 0, 2); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-exclamation-circle fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-warning text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Pending Invoices</div>
                                <div class="fs-4"><?php echo number_format($summary['invoice_count'] ?? 0); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-file-invoice fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-secondary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Students with Balance</div>
                                <div class="fs-4"><?php echo number_format($summary['student_count'] ?? 0); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-user-clock fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($report_type == 'defaulters' && isset($summary)): ?>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-danger text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Total Overdue</div>
                                <div class="fs-4">$<?php echo number_format($summary['total_overdue'] ?? 0, 2); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-warning text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Defaulters Count</div>
                                <div class="fs-4"><?php echo number_format($summary['defaulter_count'] ?? 0); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-user-times fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-info text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Avg. Days Overdue</div>
                                <div class="fs-4"><?php echo number_format($summary['avg_days_overdue'] ?? 0, 0); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-calendar-times fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($report_type == 'category' && isset($summary)): ?>
            <div class="col-xl-6 col-md-6">
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Total Fee Amount</div>
                                <div class="fs-4">$<?php echo number_format($summary['total_amount'] ?? 0, 2); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-money-check-alt fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-md-6">
                <div class="card bg-success text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Fee Categories</div>
                                <div class="fs-4"><?php echo number_format($summary['category_count'] ?? 0); ?></div>
                            </div>
                            <div>
                                <i class="fas fa-tags fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Chart -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    <?php 
                    $chart_title = '';
                    switch ($report_type) {
                        case 'collection':
                            $chart_title = 'Fee Collection Trend';
                            break;
                        case 'outstanding':
                            $chart_title = 'Outstanding Fees by Class';
                            break;
                        case 'defaulters':
                            $chart_title = 'Fee Defaulters';
                            break;
                        case 'category':
                            $chart_title = 'Fee Distribution by Category';
                            break;
                    }
                    echo $chart_title;
                    ?>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:50vh;">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Report Data Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Report Data
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="reportTable">
                    <?php if ($report_type == 'collection'): ?>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount Collected</th>
                                <th>Invoices Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td><?php echo $row['invoice_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($report_data)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>$<?php echo number_format($summary['total_collected'] ?? 0, 2); ?></th>
                                <th><?php echo $summary['invoice_count'] ?? 0; ?></th>
                            </tr>
                        </tfoot>
                    <?php elseif ($report_type == 'outstanding'): ?>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Outstanding Amount</th>
                                <th>Pending Invoices</th>
                                <th>Students Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['section']); ?></td>
                                    <td>$<?php echo number_format($row['total_outstanding'], 2); ?></td>
                                    <td><?php echo $row['invoice_count']; ?></td>
                                    <td><?php echo $row['student_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($report_data)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>$<?php echo number_format($summary['total_outstanding'] ?? 0, 2); ?></th>
                                <th><?php echo $summary['invoice_count'] ?? 0; ?></th>
                                <th><?php echo $summary['student_count'] ?? 0; ?></th>
                            </tr>
                        </tfoot>
                    <?php elseif ($report_type == 'defaulters'): ?>
                        <thead>
                            <tr>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Overdue Amount</th>
                                <th>Invoices</th>
                                <th>Latest Due Date</th>
                                <th>Days Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['section']); ?></td>
                                    <td>$<?php echo number_format($row['total_outstanding'], 2); ?></td>
                                    <td><?php echo $row['invoice_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['latest_due_date'])); ?></td>
                                    <td><?php echo floor((time() - strtotime($row['latest_due_date'])) / (60 * 60 * 24)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($report_data)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    <?php elseif ($report_type == 'category'): ?>
                        <thead>
                            <tr>
                                <th>Fee Category</th>
                                <th>Total Amount</th>
                                <th>Invoice Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach ($report_data as $row) {
                                $total += $row['total_amount'];
                            }
                            foreach ($report_data as $row): 
                                $percentage = $total > 0 ? ($row['total_amount'] / $total) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td><?php echo $row['invoice_count']; ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($report_data)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>$<?php echo number_format($summary['total_amount'] ?? 0, 2); ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        const reportTable = new DataTable('#reportTable', {
            dom: 'Bfrtip',
            buttons: [
                'copy', 'excel', 'pdf', 'print'
            ]
        });
        
        // Initialize Chart
        const ctx = document.getElementById('reportChart').getContext('2d');
        let chartType = 'bar';
        let chartOptions = {};
        
        <?php if ($report_type == 'collection'): ?>
            chartType = 'line';
            chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw;
                            }
                        }
                    }
                }
            };
        <?php elseif ($report_type == 'outstanding'): ?>
            chartType = 'bar';
            chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw;
                            }
                        }
                    }
                }
            };
        <?php elseif ($report_type == 'category'): ?>
            chartType = 'pie';
            chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return label + ': $' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            };
        <?php endif; ?>
        
        const chartData = <?php echo json_encode($chart_data); ?>;
        const reportChart = new Chart(ctx, {
            type: chartType,
            data: chartData,
            options: chartOptions
        });
        
        // Export button
        document.getElementById('exportBtn').addEventListener('click', function() {
            reportTable.button('.buttons-excel').trigger();
        });
        
        // Print button
        document.getElementById('printBtn').addEventListener('click', function() {
            reportTable.button('.buttons-print').trigger();
        });
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>