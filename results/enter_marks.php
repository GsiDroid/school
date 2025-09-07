<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Teacher') { exit('Access Denied'); }

$exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
$subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);
if (!$exam_id || !$class_id || !$subject_id) { header("Location: index.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marks = $_POST['marks'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("REPLACE INTO exam_results (exam_id, student_id, subject_id, marks_obtained, total_marks, entered_by) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($marks as $student_id => $student_marks) {
            if (is_numeric($student_marks['obtained']) && is_numeric($student_marks['total'])) {
                $stmt->execute([$exam_id, $student_id, $subject_id, $student_marks['obtained'], $student_marks['total'], $_SESSION['user_id']]);
            }
        }
        $pdo->commit();
        header("Location: enter_marks.php?exam_id=$exam_id&class_id=$class_id&subject_id=$subject_id&success=Marks saved.");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to save marks: " . $e->getMessage();
    }
}

$students = $pdo->prepare("SELECT id, first_name, last_name FROM students WHERE class_id = ? AND is_active = 1");
$students->execute([$class_id]);
$students = $students->fetchAll(PDO::FETCH_ASSOC);

$results_stmt = $pdo->prepare("SELECT student_id, marks_obtained, total_marks FROM exam_results WHERE exam_id = ? AND subject_id = ?");
$results_stmt->execute([$exam_id, $subject_id]);
$existing_results = $results_stmt->fetchAll(PDO::FETCH_KEY_PAIR | PDO::FETCH_GROUP);

?>
<div class="content-header"><h1>Enter Marks</h1></div>
<div class="card"><div class="card-body">
    <?php if(isset($_GET['success'])) echo "<div class='alert alert-success'>".htmlspecialchars($_GET['success'])."</div>"; ?>
    <form method="POST">
    <table class="table">
        <thead><tr><th>Student Name</th><th>Marks Obtained</th><th>Total Marks</th></tr></thead>
        <tbody>
        <?php foreach ($students as $student): 
            $result = $existing_results[$student['id']][0] ?? null;
        ?>
            <tr>
                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                <td><input type="number" step="0.01" name="marks[<?php echo $student['id']; ?>][obtained]" value="<?php echo $result['marks_obtained'] ?? ''; ?>"></td>
                <td><input type="number" step="0.01" name="marks[<?php echo $student['id']; ?>][total]" value="<?php echo $result['total_marks'] ?? '100'; ?>"></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" class="btn">Save Marks</button>
    </form>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>