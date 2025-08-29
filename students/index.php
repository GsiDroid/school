<?php
/**
 * Student Management Module - Main Page
 * Lists all students with search, filter and pagination
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include common functions
require_once '../includes/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Set page title
$pageTitle = "Student Management";
$currentPage = "students";

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$params = [];
$where_clauses = [];

if (!empty($search)) {
    $where_clauses[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ?)"; 
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($class_filter > 0) {
    $where_clauses[] = "s.current_class_id = ?";
    $params[] = $class_filter;
}

if (!empty($status_filter)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM students s $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get students with class information
$query = "SELECT s.*, c.name as class_name 
          FROM students s 
          LEFT JOIN classes c ON s.current_class_id = c.id 
          $where_clause 
          ORDER BY s.admission_no DESC 
          LIMIT $offset, $per_page";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all classes for filter dropdown
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-users me-1"></i>
                Student List
            </div>
            <div>
                <a href="add.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add New Student
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by name or admission no" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="class" class="form-select" onchange="this.form.submit()">
                                <option value="0">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($class_filter == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="transferred" <?php echo ($status_filter == 'transferred') ? 'selected' : ''; ?>>Transferred</option>
                                <option value="graduated" <?php echo ($status_filter == 'graduated') ? 'selected' : ''; ?>>Graduated</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Students Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Admission No</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Gender</th>
                            <th>Admission Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($student['profile_image']) && file_exists("../uploads/students/" . $student['profile_image'])): ?>
                                            <img src="../uploads/students/<?php echo $student['profile_image']; ?>" 
                                                 alt="Profile" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                            <img src="../assets/img/default-student.svg" 
                                                 alt="Default Profile" class="rounded-circle" width="40" height="40">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($student['gender'])); ?></td>
                                    <td><?php echo date('d M, Y', strtotime($student['admission_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($student['status']) {
                                            case 'active':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'inactive':
                                                $status_class = 'bg-danger';
                                                break;
                                            case 'transferred':
                                                $status_class = 'bg-warning';
                                                break;
                                            case 'graduated':
                                                $status_class = 'bg-info';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst(htmlspecialchars($student['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="id_card.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-success" title="ID Card">
                                                <i class="fas fa-id-card"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-student" 
                                                    data-id="<?php echo $student['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>" 
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No students found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
            <!-- Export Options -->
            <div class="mt-3 text-center">
                <div class="btn-group" role="group">
                    <a href="export.php?format=pdf&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-danger">
                        <i class="fas fa-file-pdf me-1"></i> Export as PDF
                    </a>
                    <a href="export.php?format=excel&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-success">
                        <i class="fas fa-file-excel me-1"></i> Export as Excel
                    </a>
                    <a href="import.php" class="btn btn-info">
                        <i class="fas fa-file-import me-1"></i> Import Students
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteStudentModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the student: <span id="studentName"></span>?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will delete all associated records.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteStudentForm" action="delete.php" method="POST">
                    <input type="hidden" name="student_id" id="studentId">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Student</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/students.js"></script>

<?php
// Include footer
include_once '../includes/footer.php';
?>