<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Teacher') { exit('Access Denied'); }

$exams = $pdo->query("SELECT * FROM exams ORDER BY academic_year DESC, start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header"><h1>Exam Results - Select Exam</h1></div>
<div class="card"><div class="card-body">
    <p>Select an exam to enter or edit results.</p>
    <div class="list-group">
        <?php foreach ($exams as $exam): ?>
            <a href="select_class.php?exam_id=<?php echo $exam['id']; ?>" class="list-group-item">
                <?php echo htmlspecialchars($exam['name'] . ' (' . $exam['academic_year'] . ')'); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>