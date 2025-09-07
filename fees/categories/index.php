<?php
require_once __DIR__ . '/../../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

$stmt = $pdo->query("SELECT * FROM fee_categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header">
    <h1>Manage Fee Categories</h1>
    <a href="add.php" class="btn">Add New Category</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($_GET['success'])):
            echo "<div class='alert alert-success'>" . htmlspecialchars($_GET['success']) . "</div>";
        endif; ?>
        <?php if (isset($_GET['error'])):
            echo "<div class='alert alert-danger'>" . htmlspecialchars($_GET['error']) . "</div>";
        endif; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                        <td class="actions">
                            <a href="edit.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                            <a href="delete.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>