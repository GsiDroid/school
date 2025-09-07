<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!in_array($_SESSION['role'], ['Admin', 'Cashier', 'Student'])) {
    exit('Access Denied');
}

$payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);
if (!$payment_id) { exit('Invalid Payment ID'); }

$stmt = $pdo->prepare("SELECT fp.*, s.first_name, s.last_name, s.admission_no, c.class_name, u.full_name as collected_by
                         FROM fee_payments fp
                         JOIN students s ON fp.student_id = s.id
                         JOIN classes c ON s.class_id = c.id
                         JOIN users u ON fp.created_by = u.id
                         WHERE fp.id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) { exit('Payment not found.'); }

// If student, check they are viewing their own receipt
if ($_SESSION['role'] === 'Student') {
    $student_check = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $student_check->execute([$_SESSION['user_id']]);
    if ($student_check->fetchColumn() != $payment['student_id']) {
        exit('Access Denied: You can only view your own receipts.');
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt - <?php echo htmlspecialchars($payment['receipt_no']); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 20px; }
        .receipt-container { border: 2px solid #000; padding: 20px; max-width: 800px; margin: auto; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details-table th, .details-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .details-table th { background-color: #f2f2f2; width: 25%; }
        .footer { text-align: center; margin-top: 40px; font-size: 0.8em; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <h1>School Name</h1>
            <p>123 School Address, City, State, 12345</p>
            <h2>Fee Receipt</h2>
        </div>

        <table class="details-table">
            <tr><th>Receipt No:</th><td><?php echo htmlspecialchars($payment['receipt_no']); ?></td></tr>
            <tr><th>Payment Date:</th><td><?php echo htmlspecialchars($payment['payment_date']); ?></td></tr>
        </table>

        <table class="details-table">
            <tr><th>Admission No:</th><td><?php echo htmlspecialchars($payment['admission_no']); ?></td></tr>
            <tr><th>Student Name:</th><td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td></tr>
            <tr><th>Class:</th><td><?php echo htmlspecialchars($payment['class_name']); ?></td></tr>
        </table>

        <table class="details-table">
            <tr>
                <th>Description</th>
                <th>Amount Paid</th>
            </tr>
            <tr>
                <td>Fee Payment</td>
                <td><?php echo number_format($payment['amount_paid'], 2); ?></td>
            </tr>
            <tr style="font-weight: bold;">
                <td>Total</td>
                <td><?php echo number_format($payment['amount_paid'], 2); ?></td>
            </tr>
        </table>

        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></p>

        <div class="footer">
            <p>Collected by: <?php echo htmlspecialchars($payment['collected_by']); ?></p>
            <p>This is a computer-generated receipt.</p>
        </div>
    </div>
    <div class="no-print" style="text-align:center; margin-top:20px;">
        <button onclick="window.print()">Print Receipt</button>
        <a href="collect.php">Back to Fee Collection</a>
    </div>
</body>
</html>