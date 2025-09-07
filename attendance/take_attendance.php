<?php
require_once __DIR__ . '/../includes/header.php';

// Role check - Teachers only
if ($_SESSION['role'] !== 'Teacher') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
$attendance_date = $_GET['date'] ?? date('Y-m-d');

if (!$class_id) {
    header("Location: index.php?error=Invalid class");
    exit();
}

// Verify teacher is assigned to this class
$teacher_id = $_SESSION['user_id'];
$verify_stmt = $pdo->prepare("SELECT COUNT(*) FROM class_subjects WHERE class_id = ? AND teacher_id = ?");
$verify_stmt->execute([$class_id, $teacher_id]);
if ($verify_stmt->fetchColumn() == 0) {
    echo "<div class='alert alert-danger'>You are not assigned to this class.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Fetch class details
$class_stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$class_stmt->execute([$class_id]);
$class = $class_stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $statuses = $_POST['status'];
    $marked_by = $_SESSION['user_id'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("REPLACE INTO attendance (student_id, class_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?, ?)");
        foreach ($statuses as $student_id => $status) {
            if (!empty($status)) {
                $stmt->execute([$student_id, $class_id, $attendance_date, $status, $marked_by]);
            }
        }
        $pdo->commit();
        header("Location: take_attendance.php?class_id=$class_id&date=$attendance_date&success=Attendance saved successfully.");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to save attendance: " . $e->getMessage();
    }
}

// Fetch students in the class
$students_stmt = $pdo->prepare("SELECT id, first_name, last_name FROM students WHERE class_id = ? AND is_active = 1 ORDER BY first_name, last_name");
$students_stmt->execute([$class_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing attendance for this date
$attendance_stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE class_id = ? AND attendance_date = ?");
$attendance_stmt->execute([$class_id, $attendance_date]);
$existing_attendance = $attendance_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<div class="content-header">
    <h1>Take Attendance: <?php echo htmlspecialchars($class['class_name']); ?></h1>
</div>

<div class="card">
    <div class="card-header">
        <form action="take_attendance.php" method="GET">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <div class="form-group">
                <label for="date">Select Date:</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($attendance_date); ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="take_attendance.php?class_id=<?php echo $class_id; ?>&date=<?php echo $attendance_date; ?>" method="POST">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): 
                        $status = $existing_attendance[$student['id']] ?? '';
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td>
                                <div class="radio-group">
                                    <label><input type="radio" name="status[<?php echo $student['id']; ?>]" value="Present" <?php echo ($status == 'Present') ? 'checked' : ''; ?>> Present</label>
                                    <label><input type="radio" name="status[<?php echo $student['id']; ?>]" value="Absent" <?php echo ($status == 'Absent') ? 'checked' : ''; ?>> Absent</label>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="form-group form-group-full" style="margin-top: 1.5rem;">
                <button type="submit" class="btn">Save Attendance</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>