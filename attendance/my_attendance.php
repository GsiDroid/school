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

$selected_month = $_GET['month'] ?? date('Y-m');

$start_date = $selected_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));
$att_stmt = $pdo->prepare("SELECT attendance_date, status FROM attendance WHERE student_id = ? AND attendance_date BETWEEN ? AND ?");
$att_stmt->execute([$student_id, $start_date, $end_date]);
$records = $att_stmt->fetchAll(PDO::FETCH_ASSOC);

$attendance_data = [];
foreach ($records as $record) {
    $day = date('j', strtotime($record['attendance_date']));
    $attendance_data[$day] = $record['status'];
}

$days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($selected_month)), date('Y', strtotime($selected_month)));
$total_present = count(array_filter($attendance_data, fn($s) => $s == 'Present'));
$total_marked = count($attendance_data);
$percentage = $total_marked > 0 ? number_format(($total_present / $total_marked) * 100, 2) : 'N/A';

?>
<div class="content-header"><h1>My Attendance</h1></div>
<div class="card">
    <div class="card-header">
        <form method="GET">
            <div class="form-group"><label for="month">Select Month</label><input type="month" id="month" name="month" value="<?php echo $selected_month; ?>" onchange="this.form.submit()"></div>
        </form>
    </div>
    <div class="card-body">
        <p><strong>Summary for <?php echo date('F Y', strtotime($selected_month)); ?>:</strong> Present for <?php echo $total_present; ?> out of <?php echo $total_marked; ?> marked days. (<?php echo $percentage; ?>%)</p>
        <table class="table table-bordered attendance-report">
            <thead><tr>
                <?php for ($i = 1; $i <= $days_in_month; $i++) echo "<th>$i</th>"; ?>
            </tr></thead>
            <tbody><tr>
                <?php for ($i = 1; $i <= $days_in_month; $i++): 
                    $status = $attendance_data[$i] ?? '';
                ?>
                    <td class="status-cell-<?php echo strtolower($status); ?>"><?php echo $status ? substr($status, 0, 1) : '-'; ?></td>
                <?php endfor; ?>
            </tr></tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>