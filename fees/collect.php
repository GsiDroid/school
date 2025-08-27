<?php
/**
 * Fees Management Module - Fee Collection
 * Handles collection of fee payments from students
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
$pageTitle = "Collect Fee Payment";
$currentPage = "fees";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success_message = '';
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
$invoice = null;
$student = null;
$invoice_items = [];
$previous_payments = [];

// Validate invoice ID
if ($invoice_id <= 0) {
    $errors[] = "Invalid invoice ID";
} else {
    // Get invoice details
    $stmt = $conn->prepare("SELECT fi.*, s.admission_number, s.first_name, s.last_name, 
                           c.name as class_name, c.section 
                           FROM fee_invoices fi 
                           JOIN students s ON fi.student_id = s.id 
                           JOIN classes c ON s.class_id = c.id 
                           WHERE fi.id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        $errors[] = "Invoice not found";
    } else {
        // Get invoice items
        $stmt = $conn->prepare("SELECT fii.*, fc.name as category_name 
                               FROM fee_invoice_items fii 
                               JOIN fee_categories fc ON fii.fee_category_id = fc.id 
                               WHERE fii.invoice_id = ?");
        $stmt->execute([$invoice_id]);
        $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get previous payments
        $stmt = $conn->prepare("SELECT * FROM fee_payments 
                               WHERE invoice_id = ? 
                               ORDER BY payment_date DESC");
        $stmt->execute([$invoice_id]);
        $previous_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collect_payment'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
        $transaction_id = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : '';
        $payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // Validate amount
        if ($amount <= 0) {
            $errors[] = "Payment amount must be greater than zero";
        }
        
        // Validate payment method
        if (empty($payment_method)) {
            $errors[] = "Payment method is required";
        }
        
        // Validate payment date
        if (empty($payment_date)) {
            $errors[] = "Payment date is required";
        }
        
        // Check if amount is greater than remaining balance
        if ($amount > $invoice['balance']) {
            $errors[] = "Payment amount cannot exceed the remaining balance of $" . number_format($invoice['balance'], 2);
        }
        
        // If no errors, process payment
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Insert payment record
                $stmt = $conn->prepare("INSERT INTO fee_payments 
                                       (invoice_id, amount, payment_method, transaction_id, payment_date, notes, created_by) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $invoice_id,
                    $amount,
                    $payment_method,
                    $transaction_id,
                    $payment_date,
                    $notes,
                    $_SESSION['user_id']
                ]);
                
                $payment_id = $conn->lastInsertId();
                
                // Update invoice balance and status
                // Note: This will be handled by the database trigger
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $student_name = $invoice['first_name'] . ' ' . $invoice['last_name'];
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'fee_payment_collected',
                    "Collected payment of $" . number_format($amount, 2) . " for invoice #{$invoice['invoice_number']} from {$student_name}",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                $success_message = "Payment of $" . number_format($amount, 2) . " collected successfully. Receipt #" . str_pad($payment_id, 6, '0', STR_PAD_LEFT);
                
                // Refresh invoice data
                $stmt = $conn->prepare("SELECT fi.*, s.admission_number, s.first_name, s.last_name, 
                                       c.name as class_name, c.section 
                                       FROM fee_invoices fi 
                                       JOIN students s ON fi.student_id = s.id 
                                       JOIN classes c ON s.class_id = c.id 
                                       WHERE fi.id = ?");
                $stmt->execute([$invoice_id]);
                $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Refresh previous payments
                $stmt = $conn->prepare("SELECT * FROM fee_payments 
                                       WHERE invoice_id = ? 
                                       ORDER BY payment_date DESC");
                $stmt->execute([$invoice_id]);
                $previous_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
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
            <div class="mt-2">
                <a href="print_receipt.php?payment_id=<?php echo $payment_id; ?>" class="btn btn-sm btn-primary" target="_blank">
                    <i class="fas fa-print me-1"></i> Print Receipt
                </a>
                <a href="index.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Invoices
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($invoice): ?>
        <div class="row">
            <!-- Invoice Details Card -->
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-file-invoice me-1"></i>
                        Invoice Details
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5>Invoice #<?php echo $invoice['invoice_number']; ?></h5>
                                <p class="mb-1">
                                    <strong>Student:</strong> <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Admission #:</strong> <?php echo htmlspecialchars($invoice['admission_number']); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Class:</strong> <?php echo htmlspecialchars($invoice['class_name'] . ' ' . $invoice['section']); ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-1">
                                    <strong>Term:</strong> <?php echo htmlspecialchars($invoice['term']); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Academic Year:</strong> <?php echo htmlspecialchars($invoice['academic_year']); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Due Date:</strong> <?php echo date('d M, Y', strtotime($invoice['due_date'])); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Status:</strong> 
                                    <?php if ($invoice['status'] === 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($invoice['status'] === 'partial'): ?>
                                        <span class="badge bg-warning">Partial</span>
                                    <?php elseif (strtotime($invoice['due_date']) < time()): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pending</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Fee Category</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoice_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                            <td class="text-end">$<?php echo number_format($item['amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <th>Total</th>
                                        <th class="text-end">$<?php echo number_format($invoice['total_amount'], 2); ?></th>
                                    </tr>
                                    <tr>
                                        <th>Paid Amount</th>
                                        <th class="text-end">$<?php echo number_format($invoice['paid_amount'], 2); ?></th>
                                    </tr>
                                    <tr class="table-primary">
                                        <th>Balance</th>
                                        <th class="text-end">$<?php echo number_format($invoice['balance'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Previous Payments Card -->
                <?php if (!empty($previous_payments)): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-history me-1"></i>
                            Payment History
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Receipt #</th>
                                            <th>Date</th>
                                            <th>Method</th>
                                            <th class="text-end">Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($previous_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo date('d M, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                <td class="text-end">$<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td>
                                                    <a href="print_receipt.php?payment_id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Collect Payment Card -->
            <div class="col-xl-6">
                <?php if ($invoice['status'] !== 'paid'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-money-bill-wave me-1"></i>
                            Collect Payment
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Payment Amount ($)</label>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" max="<?php echo $invoice['balance']; ?>" value="<?php echo $invoice['balance']; ?>" required>
                                    <div class="form-text">Maximum amount: $<?php echo number_format($invoice['balance'], 2); ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Check">Check</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Credit Card">Credit Card</option>
                                        <option value="Debit Card">Debit Card</option>
                                        <option value="Mobile Payment">Mobile Payment</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="transaction_id" class="form-label">Transaction ID/Reference</label>
                                    <input type="text" class="form-control" id="transaction_id" name="transaction_id">
                                    <div class="form-text">Optional for cash payments</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="payment_date" class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                                </div>
                                
                                <button type="submit" name="collect_payment" class="btn btn-success">
                                    <i class="fas fa-check-circle me-1"></i> Collect Payment
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Invoices
                                </a>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <h4><i class="fas fa-check-circle me-2"></i> Invoice Fully Paid</h4>
                        <p>This invoice has been fully paid. No further payments are required.</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Invoices
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <p>Invalid or missing invoice information.</p>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Invoices
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-select full balance by default
        const amountInput = document.getElementById('amount');
        if (amountInput) {
            amountInput.value = amountInput.getAttribute('max');
        }
        
        // Handle payment method change
        const paymentMethodSelect = document.getElementById('payment_method');
        const transactionIdInput = document.getElementById('transaction_id');
        
        if (paymentMethodSelect && transactionIdInput) {
            paymentMethodSelect.addEventListener('change', function() {
                if (this.value === 'Cash') {
                    transactionIdInput.removeAttribute('required');
                } else {
                    transactionIdInput.setAttribute('required', 'required');
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>