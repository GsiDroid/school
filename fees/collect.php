<?php
require_once __DIR__ . '/../includes/header.php';

// Role check - Cashier or Admin
if (!in_array($_SESSION['role'], ['Admin', 'Cashier'])) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
$student = null;
$fee_details = [
    'total_fees' => 0,
    'total_paid' => 0,
    'balance' => 0
];

if ($student_id) {
    // Fetch student details
    $stmt = $pdo->prepare("SELECT s.*, c.class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // Get total fee structure for the student's class
        $fee_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM fee_structure WHERE class_id = ?");
        $fee_stmt->execute([$student['class_id']]);
        $fee_details['total_fees'] = $fee_stmt->fetchColumn() ?: 0;

        // Get total paid amount
        $paid_stmt = $pdo->prepare("SELECT SUM(amount_paid) as total FROM fee_payments WHERE student_id = ?");
        $paid_stmt->execute([$student_id]);
        $fee_details['total_paid'] = $paid_stmt->fetchColumn() ?: 0;

        $fee_details['balance'] = $fee_details['total_fees'] - $fee_details['total_paid'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $student) {
    $amount_paid = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = $_POST['payment_method'];

    if ($amount_paid > 0) {
        try {
            $receipt_no = 'RCPT-' . time(); // Simple unique receipt number
            $stmt = $pdo->prepare("INSERT INTO fee_payments (student_id, amount_paid, payment_method, payment_date, receipt_no, academic_year, created_by) VALUES (?, ?, ?, CURDATE(), ?, ?, ?)");
            $stmt->execute([$student_id, $amount_paid, $payment_method, $receipt_no, $student['academic_year'], $_SESSION['user_id']]);
            $last_payment_id = $pdo->lastInsertId();
            header("Location: print_receipt.php?payment_id=" . $last_payment_id);
            exit();
        } catch (Exception $e) {
            $error_message = "Failed to record payment: " . $e->getMessage();
        }
    } else {
        $error_message = "Please enter a valid amount.";
    }
}

?>
<style>
#student-search-results { border: 1px solid #ccc; max-height: 200px; overflow-y: auto; background: #fff; position: absolute; z-index: 100; width: calc(100% - 2rem); }
#student-search-results div { padding: 10px; cursor: pointer; }
#student-search-results div:hover { background: #f0f0f0; }
.fee-summary dl { display: flex; flex-wrap: wrap; }
.fee-summary dt { width: 50%; font-weight: bold; }
.fee-summary dd { width: 50%; margin: 0; }
</style>

<div class="content-header"><h1>Collect Fees</h1></div>

<div class="card">
    <div class="card-body">
        <div class="form-group">
            <label for="student-search">Search Student (by Name or Admission No)</label>
            <input type="text" id="student-search" class="form-control" autocomplete="off">
            <div id="student-search-results"></div>
        </div>
    </div>
</div>

<?php if ($student): ?>
<div class="card">
    <div class="card-header"><h3>Student & Fee Details</h3></div>
    <div class="card-body form-grid">
        <div class="fee-summary">
            <dl>
                <dt>Student Name:</dt><dd><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></dd>
                <dt>Class:</dt><dd><?php echo htmlspecialchars($student['class_name']); ?></dd>
                <hr style="width:100%; border:0; border-top:1px solid #eee; margin: 1rem 0;">
                <dt>Total Fee Amount:</dt><dd><?php echo number_format($fee_details['total_fees'], 2); ?></dd>
                <dt>Total Paid:</dt><dd><?php echo number_format($fee_details['total_paid'], 2); ?></dd>
                <dt style="color: red;">Balance Due:</dt><dd style="color: red; font-weight: bold;"><?php echo number_format($fee_details['balance'], 2); ?></dd>
            </dl>
        </div>
        <div>
            <h4>Record New Payment</h4>
            <?php if(isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
            <form action="collect.php?student_id=<?php echo $student_id; ?>" method="POST">
                <div class="form-group">
                    <label for="amount">Amount to Pay *</label>
                    <input type="number" step="0.01" id="amount" name="amount" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-control">
                        <option>Cash</option><option>Online</option><option>Cheque</option><option>DD</option>
                    </select>
                </div>
                <button type="submit" class="btn">Submit Payment & Print Receipt</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('student-search').addEventListener('keyup', function() {
    let query = this.value;
    if (query.length < 2) {
        document.getElementById('student-search-results').innerHTML = '';
        return;
    }
    fetch(`search_students.php?q=${query}`)
        .then(response => response.json())
        .then(data => {
            let results = document.getElementById('student-search-results');
            results.innerHTML = '';
            data.forEach(student => {
                let div = document.createElement('div');
                div.innerHTML = `${student.first_name} ${student.last_name} (${student.admission_no})`;
                div.onclick = function() {
                    window.location.href = `collect.php?student_id=${student.id}`;
                };
                results.appendChild(div);
            });
        });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>