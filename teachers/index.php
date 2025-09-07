<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Fetch all users with the role 'Teacher'
$stmt = $pdo->query("SELECT u.id, u.username, u.full_name, u.email, u.is_active, t.designation FROM users u LEFT JOIN teachers t ON u.id = t.user_id WHERE u.role = 'Teacher' ORDER BY u.full_name ASC");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="content-header">
    <h1>Manage Teachers</h1>
    <a href="add.php" class="btn">Add New Teacher</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($_GET['success'])):
            ?><div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Designation</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers)):
                    ?><tr >
                        <td colspan="6" style="text-align:center;">No teachers found.</td>
                    </tr>
                <?php else:
                    ?><?php foreach ($teachers as $teacher):
                        ?><tr>
                            <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['designation']); ?></td>
                            <td>
                                <span class="status-<?php echo $teacher['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $teacher['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <a href="delete.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this teacher? This will also delete their user account.');"><i class="bi bi-trash"></i></a>
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
