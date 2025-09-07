<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Student') { exit('Access Denied'); }

$exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
if (!$exam_id) { header("Location: my_results.php"); exit(); }

$student_info = $pdo->prepare("SELECT id, class_id FROM students WHERE user_id = ?");
$student_info->execute([$_SESSION['user_id']]);
$student = $student_info->fetch(PDO::FETCH_ASSOC);
$student_id = $student['id'];

$results_stmt = $pdo->prepare("SELECT s.subject_name, er.marks_obtained, er.total_marks FROM exam_results er JOIN subjects s ON er.subject_id = s.id WHERE er.exam_id = ? AND er.student_id = ?");
$results_stmt->execute([$exam_id, $student_id]);
$results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);

$exam_stmt = $pdo->prepare("SELECT name FROM exams WHERE id = ?");
$exam_stmt->execute([$exam_id]);
$exam_name = $exam_stmt->fetchColumn();

$total_marks_obtained = 0;
$total_max_marks = 0;
?>
<style>.report-card { border: 2px solid #333; padding: 2rem; } .report-card h2, .report-card h3 { text-align: center; } </style>
<div class="content-header"><h1>Report Card</h1></div>
<div class="card"><div class="card-body">
    <div class="report-card">
        <h2>School Name</h2>
        <h3><?php echo htmlspecialchars($exam_name); ?> - Report Card</h3>
        <p><strong>Student:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        <table class="table table-bordered">
            <thead><tr><th>Subject</th><th>Marks Obtained</th><th>Total Marks</th></tr></thead>
            <tbody>
            <?php foreach ($results as $result): 
                $total_marks_obtained += $result['marks_obtained'];
                $total_max_marks += $result['total_marks'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($result['marks_obtained']); ?></td>
                    <td><?php echo htmlspecialchars($result['total_marks']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:bold;">
                    <td>Total</td>
                    <td><?php echo $total_marks_obtained; ?></td>
                    <td><?php echo $total_max_marks; ?></td>
                </tr>
                <tr style="font-weight:bold;">
                    <td colspan="2">Percentage</td>
                    <td><?php echo $total_max_marks > 0 ? number_format(($total_marks_obtained / $total_max_marks) * 100, 2) . '%' : 'N/A'; ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <button onclick="window.print()" class="btn no-print" style="margin-top: 1rem;">Print Report Card</button>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>