<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$exams = $pdo->query("SELECT * FROM exams ORDER BY academic_year DESC, start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header"><h1>Manage Exams</h1><a href="add.php" class="btn">Add New Exam</a></div>
<div class="card"><div class="card-body">
    <?php if (isset($_GET['success'])) echo "<div class='alert alert-success'>".htmlspecialchars($_GET['success'])."</div>"; ?>
    <table class="table">
        <thead><tr><th>Exam Name</th><th>Academic Year</th><th>Start Date</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($exams as $exam): ?>
                <tr>
                    <td><?php echo htmlspecialchars($exam['name']); ?></td>
                    <td><?php echo htmlspecialchars($exam['academic_year']); ?></td>
                    <td><?php echo htmlspecialchars($exam['start_date']); ?></td>
                    <td class="actions">
                        <a href="edit.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        <a href="delete.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>