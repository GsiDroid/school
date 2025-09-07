<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Teacher') { exit('Access Denied'); }

$exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
if (!$exam_id) { header("Location: index.php"); exit(); }

$teacher_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT DISTINCT c.id, c.class_name FROM classes c JOIN class_subjects cs ON c.id = cs.class_id WHERE cs.teacher_id = ? ORDER BY c.class_name");
$stmt->execute([$teacher_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header"><h1>Select Class</h1></div>
<div class="card"><div class="card-body">
    <p>Select the class you want to enter results for.</p>
    <div class="list-group">
        <?php foreach ($classes as $class): ?>
            <a href="select_subject.php?exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class['id']; ?>" class="list-group-item">
                <?php echo htmlspecialchars($class['class_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>