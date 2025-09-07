<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$classes = $pdo->query("SELECT id, class_name, academic_year FROM classes ORDER BY academic_year DESC, class_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header"><h1>Manage Timetables</h1></div>
<div class="card">
    <div class="card-body">
        <p>Select a class to create or edit its timetable.</p>
        <div class="list-group">
            <?php foreach ($classes as $class): ?>
                <a href="manage.php?class_id=<?php echo $class['id']; ?>" class="list-group-item">
                    <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['academic_year'] . ')'); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>