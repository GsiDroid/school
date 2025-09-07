<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Parent') { exit('Access Denied'); }

$student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
if (!$student_id) { header("Location: ../home.php"); exit(); }

// Verify this student belongs to the logged-in parent
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND parent_id = ?");
$stmt->execute([$student_id, $_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) { header("Location: ../home.php?error=access_denied"); exit(); }

// Store selected child in session to make other pages work
$_SESSION['viewing_child_id'] = $student_id;
$_SESSION['viewing_child_name'] = $student['first_name'] . ' ' . $student['last_name'];

?>
<div class="content-header"><h1>Dashboard for <?php echo htmlspecialchars($student['first_name']); ?></h1></div>
<p>You are now viewing details for <?php echo htmlspecialchars($student['first_name']); ?>. Use the sidebar to navigate their records.</p>

<div class="dashboard-grid">
    <div class="dashboard-widget">
        <a href="<?php echo $base_path; ?>results/my_results.php" class="widget-link">
            <div class="widget-icon"><i class="bi bi-journal-text"></i></div>
            <div class="widget-content"><div class="widget-title">Results</div></div>
        </a>
    </div>
    <div class="dashboard-widget">
        <a href="<?php echo $base_path; ?>attendance/my_attendance.php" class="widget-link">
            <div class="widget-icon"><i class="bi bi-calendar-check-fill"></i></div>
            <div class="widget-content"><div class="widget-title">Attendance</div></div>
        </a>
    </div>
    <div class="dashboard-widget">
        <a href="<?php echo $base_path; ?>fees/my_history.php" class="widget-link">
            <<div class="widget-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="widget-content"><div class="widget-title">Fee History</div></div>
        </a>
    </div>
    <div class="dashboard-widget">
        <a href="<?php echo $base_path; ?>timetable/my_schedule.php" class="widget-link">
            <div class="widget-icon"><i class="bi bi-table"></i></div>
            <div class="widget-content"><div class="widget-title">Timetable</div></div>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>