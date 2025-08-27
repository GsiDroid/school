<?php
/**
 * Take Attendance
 * Page for recording daily student attendance
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
$pageTitle = "Take Attendance";
$currentPage = "attendance";

// Get all classes for filter dropdown
$stmt = $conn->prepare("SELECT id, class_name FROM classes ORDER BY class_name");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Default to today's date
$selected_date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');
$selected_class = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

// Get students based on selected class
$students = [];
if ($selected_class > 0) {
    $stmt = $conn->prepare("SELECT id, student_id as student_code, first_name, last_name, gender 
                          FROM students 
                          WHERE class_id = ? AND status = 'active' 
                          ORDER BY last_name, first_name");
    $stmt->execute([$selected_class]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if attendance already exists for this date and class
    $stmt = $conn->prepare("SELECT COUNT(*) as count 
                          FROM attendance a 
                          JOIN students s ON a.student_id = s.id 
                          WHERE DATE(a.attendance_date) = ? AND s.class_id = ?");
    $stmt->execute([$selected_date, $selected_class]);
    $attendance_exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Check if attendance already exists for this date and class
        $stmt = $conn->prepare("SELECT COUNT(*) as count 
                              FROM attendance a 
                              JOIN students s ON a.student_id = s.id 
                              WHERE DATE(a.attendance_date) = ? AND s.class_id = ?");
        $stmt->execute([$selected_date, $selected_class]);
        $attendance_exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($attendance_exists) {
            // Delete existing attendance records for this date and class
            $stmt = $conn->prepare("DELETE a FROM attendance a 
                                  JOIN students s ON a.student_id = s.id 
                                  WHERE DATE(a.attendance_date) = ? AND s.class_id = ?");
            $stmt->execute([$selected_date, $selected_class]);
        }
        
        // Insert new attendance records
        $stmt = $conn->prepare("INSERT INTO attendance 
                              (student_id, attendance_date, status, check_in_time, remarks, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($_POST['student'] as $student_id => $data) {
            $status = $data['status'];
            $check_in_time = !empty($data['check_in_time']) ? $selected_date . ' ' . $data['check_in_time'] : null;
            $remarks = $data['remarks'] ?? '';
            
            $stmt->execute([
                $student_id,
                $selected_date,
                $status,
                $check_in_time,
                $remarks,
                $_SESSION['user_id']
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        $success_message = "Attendance has been recorded successfully!";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = "Error recording attendance: " . $e->getMessage();
    }
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Attendance</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-check me-1"></i>
            Select Class and Date
        </div>
        <div class="card-body">
            <form action="" method="POST" id="classSelectForm">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="attendance_date" class="form-label">Attendance Date</label>
                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" value="<?php echo $selected_date; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="class_id" class="form-label">Select Class</label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Get Students
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($selected_class > 0 && count($students) > 0): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-clipboard-list me-1"></i>
                Attendance Sheet
                <?php if ($attendance_exists): ?>
                    <span class="badge bg-warning ms-2">Attendance already recorded for this date. Submitting will overwrite existing records.</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form action="" method="POST" id="attendanceForm">
                    <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">Student ID</th>
                                    <th width="20%">Name</th>
                                    <th width="10%">Gender</th>
                                    <th width="15%">Status</th>
                                    <th width="15%">Check-in Time</th>
                                    <th width="25%">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                        <td>
                                            <select name="student[<?php echo $student['id']; ?>][status]" class="form-select status-select" required>
                                                <option value="present">Present</option>
                                                <option value="absent">Absent</option>
                                                <option value="late">Late</option>
                                                <option value="excused">Excused</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" class="form-control check-in-time" name="student[<?php echo $student['id']; ?>][check_in_time]" value="<?php echo date('H:i'); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="student[<?php echo $student['id']; ?>][remarks]" placeholder="Optional remarks">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" name="submit_attendance" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                        <a href="index.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="button" id="markAllPresent" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-check-double"></i> Mark All Present
                        </button>
                        <button type="button" id="markAllAbsent" class="btn btn-outline-danger ms-2">
                            <i class="fas fa-times-circle"></i> Mark All Absent
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($selected_class > 0 && count($students) === 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No students found in the selected class.
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Handle status change to toggle check-in time field
        $('.status-select').change(function() {
            const status = $(this).val();
            const checkInTimeField = $(this).closest('tr').find('.check-in-time');
            
            if (status === 'absent' || status === 'excused') {
                checkInTimeField.val('').prop('disabled', true);
            } else {
                checkInTimeField.prop('disabled', false);
                if (status === 'present' && !checkInTimeField.val()) {
                    checkInTimeField.val('<?php echo date('H:i'); ?>');
                }
            }
        });
        
        // Mark all present button
        $('#markAllPresent').click(function() {
            $('.status-select').val('present');
            $('.check-in-time').prop('disabled', false).val('<?php echo date('H:i'); ?>');
        });
        
        // Mark all absent button
        $('#markAllAbsent').click(function() {
            $('.status-select').val('absent');
            $('.check-in-time').val('').prop('disabled', true);
        });
    });
</script>