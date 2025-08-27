<?php
/**
 * Print Fee Payment Receipt
 * Generates a printable receipt for fee payments
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

// Get payment ID
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

// Validate payment ID
if ($payment_id <= 0) {
    die("Invalid payment ID");
}

// Get payment details with related information
$stmt = $conn->prepare("SELECT p.*, 
                       fi.invoice_number, fi.term, fi.academic_year, fi.total_amount, 
                       s.admission_number, s.first_name, s.last_name, s.gender, 
                       c.name as class_name, c.section, 
                       u.name as collected_by 
                       FROM fee_payments p 
                       JOIN fee_invoices fi ON p.invoice_id = fi.id 
                       JOIN students s ON fi.student_id = s.id 
                       JOIN classes c ON s.class_id = c.id 
                       JOIN users u ON p.created_by = u.id 
                       WHERE p.id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if payment exists
if (!$payment) {
    die("Payment not found");
}

// Get school settings
$stmt = $conn->prepare("SELECT * FROM system_settings WHERE setting_key IN ('school_name', 'school_address', 'school_phone', 'school_email', 'school_logo')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Format receipt number
$receipt_number = str_pad($payment_id, 6, '0', STR_PAD_LEFT);

// Get invoice items
$stmt = $conn->prepare("SELECT fii.*, fc.name as category_name 
                       FROM fee_invoice_items fii 
                       JOIN fee_categories fc ON fii.fee_category_id = fc.id 
                       WHERE fii.invoice_id = ?");
$stmt->execute([$payment['invoice_id']]);
$invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log activity
$activity_stmt = $conn->prepare("INSERT INTO activity_logs 
    (user_id, activity_type, description, ip_address) 
    VALUES (?, ?, ?, ?)");

$activity_stmt->execute([
    $_SESSION['user_id'],
    'receipt_printed',
    "Printed receipt #{$receipt_number} for payment of $" . number_format($payment['amount'], 2),
    $_SERVER['REMOTE_ADDR']
]);

// Set content type to HTML
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt #<?php echo $receipt_number; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .receipt-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-bottom: 2px solid #dee2e6;
        }
        .receipt-body {
            padding: 20px;
        }
        .receipt-footer {
            background-color: #f8f9fa;
            padding: 20px;
            border-top: 2px solid #dee2e6;
            font-size: 12px;
        }
        .school-logo {
            max-height: 80px;
            max-width: 80px;
        }
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            color: #4e73df;
            margin-bottom: 5px;
        }
        .receipt-number {
            font-size: 16px;
            color: #5a5c69;
        }
        .receipt-date {
            font-size: 14px;
            color: #5a5c69;
        }
        .student-info, .payment-info {
            margin-bottom: 20px;
        }
        .table {
            font-size: 14px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .signature-line {
            border-top: 1px solid #dee2e6;
            width: 200px;
            margin-top: 70px;
            margin-bottom: 5px;
        }
        .qr-code {
            text-align: center;
            margin-top: 20px;
        }
        .qr-code img {
            max-width: 100px;
            max-height: 100px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            opacity: 0.05;
            color: #4e73df;
            pointer-events: none;
            z-index: 1;
        }
        .print-buttons {
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            body {
                background-color: #fff;
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
                width: 100%;
                max-width: 100%;
            }
            .print-buttons {
                display: none;
            }
            .receipt-header, .receipt-footer {
                background-color: #fff;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container position-relative">
        <div class="watermark">PAID</div>
        
        <!-- Receipt Header -->
        <div class="receipt-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <?php if (!empty($settings['school_logo'])): ?>
                        <img src="../assets/img/<?php echo htmlspecialchars($settings['school_logo']); ?>" alt="School Logo" class="school-logo">
                    <?php else: ?>
                        <i class="fas fa-school fa-3x text-primary"></i>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <h1 class="receipt-title"><?php echo htmlspecialchars($settings['school_name'] ?? 'School Management System'); ?></h1>
                    <p class="mb-0"><?php echo htmlspecialchars($settings['school_address'] ?? ''); ?></p>
                    <p class="mb-0">
                        <?php if (!empty($settings['school_phone'])): ?>
                            <i class="fas fa-phone-alt me-1"></i> <?php echo htmlspecialchars($settings['school_phone']); ?>
                        <?php endif; ?>
                        <?php if (!empty($settings['school_email'])): ?>
                            <i class="fas fa-envelope ms-2 me-1"></i> <?php echo htmlspecialchars($settings['school_email']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-auto text-end">
                    <h2 class="receipt-title">RECEIPT</h2>
                    <p class="receipt-number mb-0">#<?php echo $receipt_number; ?></p>
                    <p class="receipt-date mb-0">Date: <?php echo date('d M, Y', strtotime($payment['payment_date'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Receipt Body -->
        <div class="receipt-body">
            <div class="row">
                <!-- Student Information -->
                <div class="col-md-6 student-info">
                    <h5>Student Information</h5>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th width="40%">Name:</th>
                            <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Admission No:</th>
                            <td><?php echo htmlspecialchars($payment['admission_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Class:</th>
                            <td><?php echo htmlspecialchars($payment['class_name'] . ' ' . $payment['section']); ?></td>
                        </tr>
                        <tr>
                            <th>Term/Year:</th>
                            <td><?php echo htmlspecialchars($payment['term'] . ' / ' . $payment['academic_year']); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Payment Information -->
                <div class="col-md-6 payment-info">
                    <h5>Payment Information</h5>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th width="40%">Invoice No:</th>
                            <td><?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Payment Method:</th>
                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                        </tr>
                        <?php if (!empty($payment['transaction_id'])): ?>
                        <tr>
                            <th>Transaction ID:</th>
                            <td><?php echo htmlspecialchars($payment['transaction_id']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Collected By:</th>
                            <td><?php echo htmlspecialchars($payment['collected_by']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Fee Details -->
            <h5>Fee Details</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th>Fee Category</th>
                            <th class="text-end" width="20%">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($invoice_items as $item): 
                        ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Total Invoice Amount:</th>
                            <td class="text-end">$<?php echo number_format($payment['total_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th colspan="2" class="text-end">This Payment:</th>
                            <td class="text-end">$<?php echo number_format($payment['amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Payment Notes -->
            <?php if (!empty($payment['notes'])): ?>
            <div class="mt-3">
                <h5>Notes</h5>
                <p><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Signature -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="signature-line"></div>
                    <p>Authorized Signature</p>
                </div>
                <div class="col-md-6 text-end">
                    <!-- QR Code for digital verification -->
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=RECEIPT:<?php echo $receipt_number; ?>|AMOUNT:<?php echo $payment['amount']; ?>|DATE:<?php echo $payment['payment_date']; ?>" alt="QR Code">
                        <p class="mt-2">Scan to verify</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Receipt Footer -->
        <div class="receipt-footer text-center">
            <p class="mb-1">This is a computer-generated receipt and does not require a physical signature.</p>
            <p class="mb-0">Thank you for your payment!</p>
        </div>
    </div>
    
    <!-- Print Buttons -->
    <div class="print-buttons">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Receipt
        </button>
        <a href="collect.php?invoice_id=<?php echo $payment['invoice_id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Uncomment the line below to automatically open print dialog
            // window.print();
        };
    </script>
</body>
</html>