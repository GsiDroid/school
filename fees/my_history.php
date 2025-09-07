<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] === 'Parent') {
    if (!isset($_SESSION['viewing_child_id'])) exit('No child selected.');
    $student_id = $_SESSION['viewing_child_id'];
} else { // Student
    $student_info = $pdo->prepare("SELECT id, class_id FROM students WHERE user_id = ?");
    $student_info->execute([$_SESSION['user_id']]);
    $student = $student_info->fetch(PDO::FETCH_ASSOC);
    $student_id = $student['id'];
}

// Fee Calculation
$fee_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM fee_structure WHERE class_id = ?");
$fee_stmt->execute([$student['class_id']]);
$total_fees = $fee_stmt->fetchColumn() ?: 0;

$paid_stmt = $pdo->prepare("SELECT SUM(amount_paid) as total FROM fee_payments WHERE student_id = ?");
$paid_stmt->execute([$student_id]);
$total_paid = $paid_stmt->fetchColumn() ?: 0;
$balance = $total_fees - $total_paid;

// Payment History
$history_stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? ORDER BY payment_date DESC");
$history_stmt->execute([$student_id]);
$history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header"><h1>My Fee History</h1></div>
<div class="card">
    <div class="card-header"><h3>Fee Summary</h3></div>
    <div class="card-body fee-summary">
        <dl>
            <dt>Total Fee Amount:</dt><dd><?php echo number_format($total_fees, 2); ?></dd>
            <dt>Total Paid:</dt><dd><?php echo number_format($total_paid, 2); ?></dd>
            <dt style="color: red;">Balance Due:</dt><dd style="color: red; font-weight: bold;"><?php echo number_format($balance, 2); ?></dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Payment History</h3></div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Receipt No</th><th>Payment Date</th><th>Amount Paid</th><th>Method</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($history as $payment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                    <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                    <td><?php echo number_format($payment['amount_paid'], 2); ?></td>
                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                    <td><a href="print_receipt.php?payment_id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info" target="_blank"><i class="bi bi-printer"></i> Print</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>