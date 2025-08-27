<?php
/**
 * Fees Management Module - View Invoice
 * Displays detailed information about a specific fee invoice
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

// Check if invoice ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=invoice_id_required");
    exit();
}

$invoice_id = (int)$_GET['id'];

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get invoice details
$stmt = $conn->prepare("SELECT fi.*, s.first_name, s.last_name, s.admission_number, 
                       c.name as class_name, c.section 
                       FROM fee_invoices fi 
                       JOIN students s ON fi.student_id = s.id 
                       JOIN classes c ON s.class_id = c.id 
                       WHERE fi.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    header("Location: index.php?error=invoice_not_found");
    exit();
}

// Get invoice items
$stmt = $conn->prepare("SELECT fii.*, fc.name as category_name 
                       FROM fee_invoice_items fii 
                       JOIN fee_categories fc ON fii.fee_category_id = fc.id 
                       WHERE fii.invoice_id = ?
                       ORDER BY fc.name");
$stmt->execute([$invoice_id]);
$invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment history
$stmt = $conn->prepare("SELECT fp.*, u.name as collected_by_name 
                       FROM fee_payments fp 
                       LEFT JOIN users u ON fp.collected_by = u.id 
                       WHERE fp.invoice_id = ? 
                       ORDER BY fp.payment_date DESC");
$stmt->execute([$invoice_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
foreach ($invoice_items as $item) {
    $total_amount += $item['amount'];
}

$total_paid = 0;
foreach ($payments as $payment) {
    $total_paid += $payment['amount'];
}

$balance = $total_amount - $total_paid;

// Determine status
$status = 'Unpaid';
$status_class = 'bg-danger';

if ($balance <= 0) {
    $status = 'Paid';
    $status_class = 'bg-success';
} elseif ($total_paid > 0) {
    $status = 'Partial';
    $status_class = 'bg-warning';
}

if ($balance > 0 && strtotime($invoice['due_date']) < time()) {
    $status = 'Overdue';
    $status_class = 'bg-danger';
}

// Get system settings for school info
$stmt = $conn->prepare("SELECT * FROM system_settings WHERE setting_key IN ('school_name', 'school_address', 'school_phone', 'school_email', 'school_website')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Set page title
$pageTitle = "View Invoice";
$currentPage = "fees";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    
    <div class="row mb-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-file-invoice-dollar me-1"></i>
                        Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?>
                    </div>
                    <div>
                        <div class="btn-group">
                            <?php if ($balance > 0): ?>
                                <a href="collect.php?id=<?php echo $invoice_id; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-money-bill-wave me-1"></i> Collect Payment
                                </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-primary btn-sm" onclick="printInvoice()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body" id="printableArea">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4 class="mb-3"><?php echo htmlspecialchars($settings['school_name'] ?? 'School Management System'); ?></h4>
                            <p class="mb-1"><?php echo htmlspecialchars($settings['school_address'] ?? ''); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($settings['school_phone'] ?? ''); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($settings['school_email'] ?? ''); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($settings['school_website'] ?? ''); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h4 class="mb-3">INVOICE</h4>
                            <p class="mb-1"><strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                            <p class="mb-1"><strong>Issue Date:</strong> <?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?></p>
                            <p class="mb-1"><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></p>
                            <p class="mb-1"><strong>Status:</strong> <span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-2">Bill To:</h5>
                            <p class="mb-1"><strong>Student:</strong> <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></p>
                            <p class="mb-1"><strong>Admission No:</strong> <?php echo htmlspecialchars($invoice['admission_number']); ?></p>
                            <p class="mb-1"><strong>Class:</strong> <?php echo htmlspecialchars($invoice['class_name'] . ' ' . $invoice['section']); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5 class="mb-2">Payment Details:</h5>
                            <p class="mb-1"><strong>Term:</strong> <?php echo htmlspecialchars($invoice['term']); ?></p>
                            <p class="mb-1"><strong>Academic Year:</strong> <?php echo htmlspecialchars($invoice['academic_year']); ?></p>
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fee Category</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($invoice_items as $item): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td class="text-end">$<?php echo number_format($item['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th class="text-end">$<?php echo number_format($total_amount, 2); ?></th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Paid:</th>
                                    <th class="text-end">$<?php echo number_format($total_paid, 2); ?></th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Balance:</th>
                                    <th class="text-end">$<?php echo number_format($balance, 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if (!empty($invoice['notes'])): ?>
                        <div class="mb-4">
                            <h5>Notes:</h5>
                            <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payments)): ?>
                        <div class="mb-4">
                            <h5>Payment History:</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Transaction ID</th>
                                            <th>Collected By</th>
                                            <th>Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($payment['collected_by_name'] ?? 'System'); ?></td>
                                                <td>
                                                    <a href="print_receipt.php?id=<?php echo $payment['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-receipt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mt-5">
                        <div class="col-md-6">
                            <p><strong>Payment Terms:</strong></p>
                            <ul>
                                <li>All fees must be paid by the due date.</li>
                                <li>Late payments may incur additional charges.</li>
                                <li>For any queries regarding this invoice, please contact the school office.</li>
                            </ul>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="mt-4 pt-2">
                                <p><strong>Authorized Signature</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function printInvoice() {
        const printContents = document.getElementById('printableArea').innerHTML;
        const originalContents = document.body.innerHTML;
        
        document.body.innerHTML = `
            <html>
                <head>
                    <title>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
                    <link href="../assets/css/styles.css" rel="stylesheet" />
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .badge { display: inline-block; padding: 0.25em 0.4em; font-size: 75%; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.25rem; }
                        .bg-success { background-color: #28a745 !important; color: white; }
                        .bg-warning { background-color: #ffc107 !important; color: black; }
                        .bg-danger { background-color: #dc3545 !important; color: white; }
                        .text-md-end { text-align: right; }
                        @media print {
                            .btn { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container mt-4">
                        ${printContents}
                    </div>
                </body>
            </html>
        `;
        
        window.print();
        document.body.innerHTML = originalContents;
    }
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>