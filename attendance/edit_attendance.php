<?php
/**
 * Edit Attendance
 * Page for editing an existing attendance record
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
$pageTitle = "Edit Attendance";
$currentPage = "attendance";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$attendance_id = (int)$_GET['id'];

// Get attendance record
$stmt = $conn->prepare("SELECT a.*, s.student_id as student_code, s.first_name, s.last_name, s.class_id, c.class_name 
                      FROM attendance a 
                      LEFT JOIN students s ON a.student_id = s.id 
                      LEFT JOIN classes c ON s.class_id = c.id 
                      WHERE a.id = ?");
$stmt->execute([$attendance_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attendance) {
    header("Location: index.php");
    exit();
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $status = $_POST['status'];
        $attendance_date = $_POST['attendance_date'];
        $check_in_time = !empty($_POST['check_in_time']) ? $attendance_date . ' ' . $_POST['check_in_time'] : null;
        $remarks = $_POST['remarks'] ?? '';
        
        // Update attendance record
        $stmt = $conn->prepare("UPDATE attendance 
                              SET status = ?, attendance_date = ?, check_in_time = ?, remarks = ?, updated_at = NOW() 
                              WHERE id = ?");
        $stmt->execute([$status, $attendance_date, $check_in_time, $remarks, $attendance_id]);
        
        $success_message = "Attendance record has been updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating attendance record: " . $e->getMessage();
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
            <i class="fas fa-edit me-1"></i>
            Edit Attendance Record
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">Student Information</div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Student ID:</th>
                                    <td><?php echo htmlspecialchars($attendance['student_code']); ?></td>
                                </tr>
                                <tr>
                                    <th>Name:</th>
                                    <td><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Class:</th>
                                    <td><?php echo htmlspecialchars($attendance['class_name']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <form action="" method="POST">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="attendance_date" class="form-label">Attendance Date</label>
                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                               value="<?php echo date('Y-m-d', strtotime($attendance['attendance_date'])); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="present" <?php echo ($attendance['status'] == 'present') ? 'selected' : ''; ?>>Present</option>
                            <option value="absent" <?php echo ($attendance['status'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                            <option value="late" <?php echo ($attendance['status'] == 'late') ? 'selected' : ''; ?>>Late</option>
                            <option value="excused" <?php echo ($attendance['status'] == 'excused') ? 'selected' : ''; ?>>Excused</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="check_in_time" class="form-label">Check-in Time</label>
                        <input type="time" class="form-control" id="check_in_time" name="check_in_time" 
                               value="<?php echo !empty($attendance['check_in_time']) ? date('H:i', strtotime($attendance['check_in_time'])) : ''; ?>" 
                               <?php echo ($attendance['status'] == 'absent' || $attendance['status'] == 'excused') ? 'disabled' : ''; ?>>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="3"><?php echo htmlspecialchars($attendance['remarks']); ?></textarea>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Attendance
                    </button>
                    <a href="index.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Handle status change to toggle check-in time field
        $('#status').change(function() {
            const status = $(this).val();
            const checkInTimeField = $('#check_in_time');
            
            if (status === 'absent' || status === 'excused') {
                checkInTimeField.val('').prop('disabled', true);
            } else {
                checkInTimeField.prop('disabled', false);
                if (status === 'present' && !checkInTimeField.val()) {
                    checkInTimeField.val('<?php echo date('H:i'); ?>');
                }
            }
        });
    });
</script>