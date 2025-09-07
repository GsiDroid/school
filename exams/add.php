<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $academic_year = trim($_POST['academic_year']);
    $start_date = $_POST['start_date'];
    $stmt = $pdo->prepare("INSERT INTO exams (name, academic_year, start_date) VALUES (?, ?, ?)");
    $stmt->execute([$name, $academic_year, $start_date]);
    header("Location: index.php?success=Exam added.");
    exit();
}
?>

<div class="content-header"><h1>Add New Exam</h1></div>
<div class="card"><div class="card-body">
    <form action="add.php" method="POST">
        <div class="form-group"><label for="name">Exam Name *</label><input type="text" id="name" name="name" required></div>
        <div class="form-group"><label for="academic_year">Academic Year *</label><input type="text" id="academic_year" name="academic_year" required value="2025-2026"></div>
        <div class="form-group"><label for="start_date">Start Date</label><input type="date" id="start_date" name="start_date"></div>
        <button type="submit" class="btn">Add Exam</button>
    </form>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>