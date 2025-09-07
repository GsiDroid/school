<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] === 'Parent') {
    if (!isset($_SESSION['viewing_child_id'])) exit('No child selected.');
    $student_id = $_SESSION['viewing_child_id'];
} else { // Student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_id = $stmt->fetchColumn();
}

$exams = $pdo->query("SELECT * FROM exams ORDER BY academic_year DESC, start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header"><h1>My Results - Select Exam</h1></div>
<div class="card"><div class="card-body">
    <p>Select an exam to view your report card.</p>
    <div class="list-group">
        <?php foreach ($exams as $exam): ?>
            <a href="view_report_card.php?exam_id=<?php echo $exam['id']; ?>" class="list-group-item">
                <?php echo htmlspecialchars($exam['name'] . ' (' . $exam['academic_year'] . ')'); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>