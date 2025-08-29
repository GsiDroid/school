<?php
/**
 * Attendance Management
 * Main page for attendance tracking and management
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

// Set page title and current page for sidebar highlighting
$pageTitle = "Attendance Management";
$currentPage = "attendance";

// Get current date
$current_date = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get all classes for filter dropdown
$stmt = $conn->prepare("SELECT id, name as class_name FROM classes ORDER BY name");
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
    
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Today's Attendance</h5>
                            <?php
                            // Get today's attendance count
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE() AND status = 'present'");
                            $stmt->execute();
                            $today_present = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
                            $stmt->execute();
                            $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            
                            $attendance_percentage = ($total_students > 0) ? round(($today_present / $total_students) * 100) : 0;
                            ?>
                            <h3 class="mb-0"><?php echo $attendance_percentage; ?>%</h3>
                        </div>
                        <div>
                            <i class="fas fa-calendar-check fa-3x opacity-50"></i>
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
                            <h5 class="mb-0">Present Students</h5>
                            <h3 class="mb-0"><?php echo $today_present; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-user-check fa-3x opacity-50"></i>
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
                            <h5 class="mb-0">Absent Students</h5>
                            <?php
                            // Get today's absent count
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE() AND status = 'absent'");
                            $stmt->execute();
                            $today_absent = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $today_absent; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-user-times fa-3x opacity-50"></i>
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
                            <h5 class="mb-0">Late Students</h5>
                            <?php
                            // Get today's late count
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE() AND status = 'late'");
                            $stmt->execute();
                            $today_late = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <h3 class="mb-0"><?php echo $today_late; ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
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
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-calendar-alt me-1"></i>
                Attendance Register
            </div>
            <div>
                <a href="take_attendance.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Take Attendance
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="date" class="form-label">Select Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $selected_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="class_id" class="form-label">Select Class</label>
                    <select name="class_id" id="class_id" class="form-select">
                        <option value="0">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Reset
                    </a>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Check-in Time</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Build query based on filters
                        $params = [];
                        $where_clauses = [];
                        
                        if (!empty($selected_date)) {
                            $where_clauses[] = "DATE(a.attendance_date) = ?";
                            $params[] = $selected_date;
                        }
                        
                        if ($selected_class > 0) {
                            $where_clauses[] = "s.class_id = ?";
                            $params[] = $selected_class;
                        }
                        
                        $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
                        
                        // Get attendance records
                        $query = "SELECT a.*, s.student_id as student_code, s.first_name, s.last_name, c.class_name 
                                 FROM attendance a 
                                 LEFT JOIN students s ON a.student_id = s.id 
                                 LEFT JOIN classes c ON s.class_id = c.id 
                                 $where_clause 
                                 ORDER BY a.date DESC, s.last_name, s.first_name";
                        $stmt = $conn->prepare($query);
                        $stmt->execute($params);
                        $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($attendance_records) > 0):
                            foreach ($attendance_records as $record):
                                // Determine status badge class
                                $status_class = '';
                                switch($record['status']) {
                                    case 'present':
                                        $status_class = 'bg-success';
                                        break;
                                    case 'absent':
                                        $status_class = 'bg-danger';
                                        break;
                                    case 'late':
                                        $status_class = 'bg-warning';
                                        break;
                                    case 'excused':
                                        $status_class = 'bg-info';
                                        break;
                                }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                <td><?php echo date('d M, Y', strtotime($record['attendance_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo !empty($record['check_in_time']) ? date('h:i A', strtotime($record['check_in_time'])) : 'N/A'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['remarks']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit_attendance.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="delete_attendance.php" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this attendance record?');">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="8" class="text-center">No attendance records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Monthly Attendance Overview
                </div>
                <div class="card-body">
                    <canvas id="monthlyAttendanceChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Attendance Distribution
                </div>
                <div class="card-body">
                    <canvas id="attendanceDistributionChart" width="100%" height="40"></canvas>
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
                Are you sure you want to delete this attendance record? This action cannot be undone.
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
        $('#attendanceTable').DataTable({
            "ordering": true,
            "info": true,
            "paging": true,
            "responsive": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
        
        // Handle delete button click
        $('.delete-attendance').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            $('#confirmDelete').attr('href', 'delete_attendance.php?id=' + id);
            $('#deleteModal').modal('show');
        });
        
        // Monthly Attendance Chart
        const monthlyCtx = document.getElementById('monthlyAttendanceChart');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Present',
                    backgroundColor: '#28a745',
                    data: [85, 88, 92, 90, 85, 87, 89, 91, 93, 90, 88, 0],
                }, {
                    label: 'Absent',
                    backgroundColor: '#dc3545',
                    data: [10, 8, 5, 7, 10, 8, 7, 6, 4, 7, 9, 0],
                }, {
                    label: 'Late',
                    backgroundColor: '#ffc107',
                    data: [5, 4, 3, 3, 5, 5, 4, 3, 3, 3, 3, 0],
                }]
            },
            options: {
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            beginAtZero: true,
                            max: 100,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                }
            }
        });
        
        // Attendance Distribution Chart
        const distributionCtx = document.getElementById('attendanceDistributionChart');
        new Chart(distributionCtx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    data: [<?php echo $today_present; ?>, <?php echo $today_absent; ?>, <?php echo $today_late; ?>, 0],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#17a2b8'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
</script>