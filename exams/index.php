<?php
/**
 * Exams & Results Management
 * Main page for managing exams and student results
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
$conn = get_db_connection();

// Set page title and current page for sidebar highlighting
$pageTitle = "Exams & Results";
$currentPage = "exams";

// Get filter parameters
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_exam = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$selected_subject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// Get all classes for filter dropdown
$stmt = $conn->prepare("SELECT id, name as class_name FROM classes ORDER BY name");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all exams for filter dropdown
$stmt = $conn->prepare("SELECT id, exam_name FROM exams ORDER BY exam_date DESC");
$stmt->execute();
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects for filter dropdown
$stmt = $conn->prepare("SELECT id, subject_name FROM subjects ORDER BY subject_name");
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent exams
$stmt = $conn->prepare("SELECT e.*, 
                        (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id) as result_count 
                      FROM exams e 
                      ORDER BY e.exam_date DESC 
                      LIMIT 5");
$stmt->execute();
$recent_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Total Exams</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM exams");
                            $stmt->execute();
                            $total_exams = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $total_exams; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-file-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Upcoming Exams</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM exams WHERE exam_date >= CURDATE()");
                            $stmt->execute();
                            $upcoming_exams = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $upcoming_exams; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Results Entered</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM exam_results");
                            $stmt->execute();
                            $total_results = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $total_results; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Pass Rate</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT 
                                                    ROUND(COUNT(CASE WHEN marks_obtained >= e.passing_marks THEN 1 END) * 100.0 / COUNT(*), 1) as pass_rate 
                                                  FROM exam_results er
                                                  JOIN exams e ON er.exam_id = e.id");
                            $stmt->execute();
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            $pass_rate = $result ? $result['pass_rate'] : 0;
                            ?>
                            <h3 class="mb-0"><?php echo $pass_rate; ?>%</h3>
                        </div>
                        <div>
                            <i class="fas fa-graduation-cap fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-table me-1"></i>
                        Exam List
                    </div>
                    <div>
                        <a href="add_exam.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Exam
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="examsTable">
                            <thead>
                                <tr>
                                    <th>Exam Name</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Total Marks</th>
                                    <th>Results</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Build query based on filters
                                $params = [];
                                $where_clauses = [];
                                
                                if ($selected_class > 0) {
                                    $where_clauses[] = "e.class_id = ?";
                                    $params[] = $selected_class;
                                }
                                
                                if ($selected_exam > 0) {
                                    $where_clauses[] = "e.id = ?";
                                    $params[] = $selected_exam;
                                }
                                
                                if ($selected_subject > 0) {
                                    $where_clauses[] = "e.subject_id = ?";
                                    $params[] = $selected_subject;
                                }
                                
                                $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
                                
                                // Get exams
                                $query = "SELECT e.*, c.class_name, s.subject_name, 
                                                 (SELECT COUNT(*) FROM exam_results er WHERE er.exam_id = e.id) as result_count 
                                         FROM exams e 
                                         LEFT JOIN classes c ON e.class_id = c.id 
                                         LEFT JOIN subjects s ON e.subject_id = s.id 
                                         $where_clause 
                                         ORDER BY e.exam_date DESC";
                                $stmt = $conn->prepare($query);
                                $stmt->execute($params);
                                $exams_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($exams_list) > 0):
                                    foreach ($exams_list as $exam):
                                ?>
                                    <tr>
                                        <td>
                                            <a href="view_exam.php?id=<?php echo $exam['id']; ?>">
                                                <?php echo htmlspecialchars($exam['exam_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($exam['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                        <td><?php echo date('d M, Y', strtotime($exam['exam_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($exam['duration']); ?> mins</td>
                                        <td><?php echo htmlspecialchars($exam['total_marks']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($exam['result_count'] > 0) ? 'success' : 'warning'; ?>">
                                                <?php echo $exam['result_count']; ?> results
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="enter_results.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-success" title="Enter Results">
                                                    <i class="fas fa-plus-circle"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-danger delete-exam" data-id="<?php echo $exam['id']; ?>" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No exams found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i>
                    Filter Exams
                </div>
                <div class="card-body">
                    <form action="" method="GET">
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Class</label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="0">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="exam_id" class="form-label">Exam</label>
                            <select name="exam_id" id="exam_id" class="form-select">
                                <option value="0">All Exams</option>
                                <?php foreach ($exams as $exam): ?>
                                    <option value="<?php echo $exam['id']; ?>" <?php echo ($selected_exam == $exam['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($exam['exam_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select name="subject_id" id="subject_id" class="form-select">
                                <option value="0">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject == $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Reset Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-calendar me-1"></i>
                    Recent Exams
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php if (count($recent_exams) > 0): ?>
                            <?php foreach ($recent_exams as $exam): ?>
                                <a href="view_exam.php?id=<?php echo $exam['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($exam['exam_name']); ?></h6>
                                        <small><?php echo date('d M', strtotime($exam['exam_date'])); ?></small>
                                    </div>
                                    <p class="mb-1 small">
                                        <?php 
                                        $stmt = $conn->prepare("SELECT class_name FROM classes WHERE id = ?");
                                        $stmt->execute([$exam['class_id']]);
                                        $class_name = $stmt->fetch(PDO::FETCH_COLUMN);
                                        echo htmlspecialchars($class_name); 
                                        ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small>Results: <?php echo $exam['result_count']; ?></small>
                                        <span class="badge bg-<?php echo (strtotime($exam['exam_date']) >= strtotime('today')) ? 'primary' : 'secondary'; ?>">
                                            <?php echo (strtotime($exam['exam_date']) >= strtotime('today')) ? 'Upcoming' : 'Completed'; ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item">
                                <p class="mb-0">No recent exams found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-link me-1"></i>
                    Quick Links
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="add_exam.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Add New Exam
                        </a>
                        <a href="results.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2"></i> View All Results
                        </a>
                        <a href="grade_settings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-cog me-2"></i> Grade Settings
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt me-2"></i> Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this exam? This will also delete all associated results. This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#examsTable').DataTable({
            "ordering": true,
            "info": true,
            "paging": true,
            "responsive": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
        
        // Handle delete button click
        $('.delete-exam').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            $('#confirmDelete').attr('href', 'delete_exam.php?id=' + id);
            $('#deleteModal').modal('show');
        });
    });
</script>