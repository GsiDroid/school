<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$stmt = $pdo->query("SELECT * FROM classes ORDER BY academic_year DESC, class_name ASC");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header">
    <h1>Manage Classes</h1>
    <a href="add.php" class="btn">Add New Class</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($_GET['success'])):
            ?><div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])):
            ?><div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>Academic Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)):
                    ?><tr style="text-align:center;">
                        <td colspan="3">No classes found.</td>
                    </tr>
                <?php else:
                    ?><?php foreach ($classes as $class):
                        ?><tr>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                            <td class="actions">
                                <a href="../subjects/assign.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-journal-plus"></i> Assign Subjects</a>
                                <a href="edit.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <a href="delete.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this class? This might fail if students are assigned to it.');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
