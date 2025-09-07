<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $academic_year = trim($_POST['academic_year']);
    $start_date = $_POST['start_date'];
    $stmt = $pdo->prepare("UPDATE exams SET name = ?, academic_year = ?, start_date = ? WHERE id = ?");
    $stmt->execute([$name, $academic_year, $start_date, $id]);
    header("Location: index.php?success=Exam updated.");
    exit();
}
?>

<div class="content-header"><h1>Edit Exam</h1></div>
<div class="card"><div class="card-body">
    <form action="edit.php?id=<?php echo $id; ?>" method="POST">
        <div class="form-group"><label for="name">Exam Name *</label><input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($exam['name']); ?>"></div>
        <div class="form-group"><label for="academic_year">Academic Year *</label><input type="text" id="academic_year" name="academic_year" required value="<?php echo htmlspecialchars($exam['academic_year']); ?>"></div>
        <div class="form-group"><label for="start_date">Start Date</label><input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($exam['start_date']); ?>"></div>
        <button type="submit" class="btn">Update Exam</button>
    </form>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>