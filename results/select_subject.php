<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Teacher') { exit('Access Denied'); }

$exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
if (!$exam_id || !$class_id) { header("Location: index.php"); exit(); }

$teacher_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT DISTINCT s.id, s.subject_name FROM subjects s JOIN class_subjects cs ON s.id = cs.subject_id WHERE cs.teacher_id = ? AND cs.class_id = ? ORDER BY s.subject_name");
$stmt->execute([$teacher_id, $class_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header"><h1>Select Subject</h1></div>
<div class="card"><div class="card-body">
    <p>Select the subject you want to enter results for.</p>
    <div class="list-group">
        <?php foreach ($subjects as $subject): ?>
            <a href="enter_marks.php?exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class_id; ?>&subject_id=<?php echo $subject['id']; ?>" class="list-group-item">
                <?php echo htmlspecialchars($subject['subject_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>