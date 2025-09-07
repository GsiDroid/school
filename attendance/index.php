<?php
require_once __DIR__ . '/../includes/header.php';

// Role check - Teachers only
if ($_SESSION['role'] !== 'Teacher') {
    echo "<div class='alert alert-danger'>Access Denied. Only teachers can access this page.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Fetch classes assigned to the logged-in teacher
$teacher_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT DISTINCT c.id, c.class_name, c.academic_year 
                         FROM classes c 
                         JOIN class_subjects cs ON c.id = cs.class_id 
                         WHERE cs.teacher_id = ? 
                         ORDER BY c.class_name ASC");
$stmt->execute([$teacher_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="content-header">
    <h1>My Classes - Attendance</h1>
</div>

<div class="card">
    <div class="card-body">
        <p>Select a class to take or view attendance.</p>
        <div class="list-group">
            <?php if (empty($classes)): ?>
                <div class="list-group-item">You are not currently assigned to any classes.</div>
            <?php else: ?>
                <?php foreach ($classes as $class): ?>
                    <a href="take_attendance.php?class_id=<?php echo $class['id']; ?>" class="list-group-item">
                        <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['academic_year'] . ')'); ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>