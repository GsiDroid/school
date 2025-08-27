<?php
/**
 * Edit Exam
 * Page for editing an existing exam
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
$pageTitle = "Edit Exam";
$currentPage = "exams";

// Check if exam_id is provided
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    header("Location: index.php");
    exit();
}

$exam_id = (int)$_GET['exam_id'];

// Get exam details
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header("Location: index.php");
    exit();
}

// Get all classes for dropdown
$stmt = $conn->prepare("SELECT id, class_name FROM classes ORDER BY class_name");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects for dropdown
$stmt = $conn->prepare("SELECT id, subject_name FROM subjects ORDER BY subject_name");
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $exam_name = trim($_POST['exam_name']);
        $class_id = (int)$_POST['class_id'];
        $subject_id = (int)$_POST['subject_id'];
        $exam_date = $_POST['exam_date'];
        $start_time = $_POST['start_time'];
        $duration = (int)$_POST['duration'];
        $total_marks = (float)$_POST['total_marks'];
        $passing_marks = (float)$_POST['passing_marks'];
        $description = trim($_POST['description']);
        
        // Validate input
        if (empty($exam_name) || $class_id <= 0 || $subject_id <= 0 || empty($exam_date) || empty($start_time) || $duration <= 0 || $total_marks <= 0) {
            throw new Exception("Please fill all required fields.");
        }
        
        if ($passing_marks >= $total_marks) {
            throw new Exception("Passing marks must be less than total marks.");
        }
        
        // Check if class has changed
        $class_changed = $class_id != $exam['class_id'];
        
        // Update exam record
        $stmt = $conn->prepare("UPDATE exams 
                              SET exam_name = ?, class_id = ?, subject_id = ?, exam_date = ?, 
                                  start_time = ?, duration = ?, total_marks = ?, passing_marks = ?, 
                                  description = ?, updated_at = NOW(), updated_by = ? 
                              WHERE id = ?");
        $stmt->execute([
            $exam_name,
            $class_id,
            $subject_id,
            $exam_date,
            $start_time,
            $duration,
            $total_marks,
            $passing_marks,
            $description,
            $_SESSION['user_id'],
            $exam_id
        ]);
        
        // If class has changed, delete existing results
        if ($class_changed) {
            $stmt = $conn->prepare("DELETE FROM exam_results WHERE exam_id = ?");
            $stmt->execute([$exam_id]);
            $success_message = "Exam has been updated successfully! Note: All existing results have been deleted because the class was changed.";
        } else {
            $success_message = "Exam has been updated successfully!";
        }
        
        // Refresh exam data
        $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$exam_id]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Exams & Results</a></li>
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
            Edit Exam Details
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="exam_name" class="form-label">Exam Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="exam_name" name="exam_name" value="<?php echo htmlspecialchars($exam['exam_name']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $class['id'] == $exam['class_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($exam['class_id']): ?>
                            <div class="form-text text-warning">
                                <i class="fas fa-exclamation-triangle"></i> Changing the class will delete all existing results for this exam.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo $subject['id'] == $exam['subject_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="exam_date" class="form-label">Exam Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date" value="<?php echo $exam['exam_date']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo $exam['start_time']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="duration" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="duration" name="duration" min="1" value="<?php echo $exam['duration']; ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="total_marks" class="form-label">Total Marks <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="total_marks" name="total_marks" min="1" step="0.01" value="<?php echo $exam['total_marks']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="passing_marks" class="form-label">Passing Marks <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="passing_marks" name="passing_marks" min="0" step="0.01" value="<?php echo $exam['passing_marks']; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($exam['description']); ?></textarea>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Exam
                    </button>
                    <a href="view_results.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-info ms-2">
                        <i class="fas fa-eye"></i> View Results
                    </a>
                    <a href="enter_results.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-success ms-2">
                        <i class="fas fa-edit"></i> Enter Results
                    </a>
                    <a href="index.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Validate passing marks
        $('#passing_marks, #total_marks').change(function() {
            const totalMarks = parseFloat($('#total_marks').val()) || 0;
            const passingMarks = parseFloat($('#passing_marks').val()) || 0;
            
            if (passingMarks >= totalMarks) {
                alert('Passing marks must be less than total marks.');
                $('#passing_marks').val(Math.floor(totalMarks * 0.4));
            }
        });
        
        // Confirm class change
        const originalClassId = <?php echo $exam['class_id']; ?>;
        
        $('form').submit(function(e) {
            const selectedClassId = parseInt($('#class_id').val());
            
            if (selectedClassId !== originalClassId) {
                if (!confirm('Changing the class will delete all existing results for this exam. Are you sure you want to continue?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });
</script>