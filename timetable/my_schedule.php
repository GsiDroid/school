<?php
require_once __DIR__ . '/../includes/header.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$timetable_data = [];
$title = 'My Schedule';

if ($role === 'Student' || $role === 'Parent') {
    if ($role === 'Parent') {
        if (!isset($_SESSION['viewing_child_id'])) exit('No child selected.');
        $student_id = $_SESSION['viewing_child_id'];
        $stmt = $pdo->prepare("SELECT class_id FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $class_id = $stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT class_id FROM students WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $class_id = $stmt->fetchColumn();
    } elseif ($role === 'Teacher') {
    $tt_stmt = $pdo->prepare("SELECT tt.*, s.subject_name, c.class_name FROM timetable tt JOIN subjects s ON tt.subject_id = s.id JOIN classes c ON tt.class_id = c.id WHERE tt.teacher_id = ? ORDER BY tt.start_time");
    $tt_stmt->execute([$user_id]);
    $timetable_data = $tt_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$schedule = [];
foreach ($timetable_data as $row) {
    $schedule[$row['day_of_week']][] = $row;
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<style>.timetable-view { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; } .day-column h3 { border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem; } .period { background: #f9fafb; border: 1px solid var(--border-color); padding: 1rem; margin-bottom: 1rem; border-radius: 4px; } </style>

<div class="content-header"><h1><?php echo $title; ?></h1></div>
<div class="card"><div class="card-body">
    <div class="timetable-view">
        <?php foreach ($days as $day): ?>
            <div class="day-column">
                <h3><?php echo $day; ?></h3>
                <?php if (!empty($schedule[$day])): ?>
                    <?php foreach ($schedule[$day] as $period): ?>
                        <div class="period">
                            <strong><?php echo htmlspecialchars(date('h:i A', strtotime($period['start_time']))); ?></strong>
                            <p><?php echo htmlspecialchars($period['subject_name']); ?></p>
                            <?php if ($role === 'Student'): ?>
                                <small>By <?php echo htmlspecialchars($period['teacher_name']); ?></small>
                            <?php elseif ($role === 'Teacher'): ?>
                                <small>For <?php echo htmlspecialchars($period['class_name']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No classes scheduled.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>