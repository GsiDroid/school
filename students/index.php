<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Search and Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base query
$sql = "SELECT s.*, c.class_name FROM students s JOIN classes c ON s.class_id = c.id";
$count_sql = "SELECT COUNT(*) FROM students s JOIN classes c ON s.class_id = c.id";

$params = [];
if ($search) {
    $sql .= " WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ? OR c.class_name LIKE ?";
    $count_sql .= " WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ? OR c.class_name LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

// Get total records
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get records for the current page
$sql .= " ORDER BY s.first_name, s.last_name LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="content-header">
    <h1>Manage Students</h1>
    <a href="add.php" class="btn">Add New Student</a>
</div>

<div class="card">
    <div class="card-header">
        <form action="index.php" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by name, admission no, class..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn">Search</button>
        </form>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Admission No</th>
                    <th>Full Name</th>
                    <th>Class</th>
                    <th>Parent Mobile</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No students found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <img src="<?php echo $base_path; ?>uploads/students/<?php echo htmlspecialchars($student['photo'] ?: 'default.png'); ?>" alt="Student Photo" class="table-photo">
                            </td>
                            <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['parent_mobile']); ?></td>
                            <td>
                                <span class="status-<?php echo $student['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <a href="delete.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>