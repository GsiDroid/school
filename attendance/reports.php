<?php
require_once __DIR__ . '/../includes/header.php';

// Role check - Admins and Teachers
if (!in_array($_SESSION['role'], ['Admin', 'Teacher'])) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Get filter parameters
$selected_class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
$selected_month = $_GET['month'] ?? date('Y-m');

// Fetch classes
if ($_SESSION['role'] === 'Admin') {
    $classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT DISTINCT c.id, c.class_name FROM classes c JOIN class_subjects cs ON c.id = cs.class_id WHERE cs.teacher_id = ? ORDER BY c.class_name");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$attendance_data = [];
$students = [];
if ($selected_class_id) {
    // Fetch students for the selected class
    $student_stmt = $pdo->prepare("SELECT id, first_name, last_name FROM students WHERE class_id = ? AND is_active = 1 ORDER BY first_name, last_name");
    $student_stmt->execute([$selected_class_id]);
    $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch attendance for the month
    $start_date = $selected_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $att_stmt = $pdo->prepare("SELECT student_id, attendance_date, status FROM attendance WHERE class_id = ? AND attendance_date BETWEEN ? AND ?");
    $att_stmt->execute([$selected_class_id, $start_date, $end_date]);
    $records = $att_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as $record) {
        $day = date('j', strtotime($record['attendance_date']));
        $attendance_data[$record['student_id']][$day] = $record['status'];
    }
}

$days_in_month = $selected_class_id ? cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($selected_month)), date('Y', strtotime($selected_month))) : 0;

?>

<div class="content-header">
    <h1>Attendance Report</h1>
</div>

<div class="card">
    <div class="card-header">
        <form action="reports.php" method="GET" class="form-grid">
            <div class="form-group">
                <label for="class_id">Class</label>
                <select name="class_id" id="class_id" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class_id == $class['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="month">Month</label>
                <input type="month" id="month" name="month" value="<?php echo $selected_month; ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>
    <?php if ($selected_class_id): ?>
    <div class="card-body" style="overflow-x:auto;">
        <table class="table table-bordered attendance-report">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <?php for ($i = 1; $i <= $days_in_month; $i++): ?>
                        <th><?php echo $i; ?></th>
                    <?php endfor; ?>
                    <th>Total Present</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): 
                    $total_present = 0;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                        <?php for ($i = 1; $i <= $days_in_month; $i++): 
                            $status = $attendance_data[$student['id']][$i] ?? '';
                            if ($status === 'Present') $total_present++;
                        ?>
                            <td class="status-cell-<?php echo strtolower($status); ?>">
                                <?php echo $status ? substr($status, 0, 1) : '-'; ?>
                            </td>
                        <?php endfor; ?>
                        <td><?php echo $total_present; ?> / <?php echo $days_in_month; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>