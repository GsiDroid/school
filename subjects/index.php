<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$stmt = $pdo->query("SELECT * FROM subjects ORDER BY subject_name ASC");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header">
    <h1>Manage Subjects</h1>
    <a href="add.php" class="btn">Add New Subject</a>
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
                    <th>Subject Name</th>
                    <th>Subject Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)):
                    ?><tr style="text-align:center;">
                        <td colspan="3">No subjects found.</td>
                    </tr>
                <?php else:
                    ?><?php foreach ($subjects as $subject):
                        ?><tr>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <a href="delete.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this subject?');"><i class="bi bi-trash"></i></a>
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
