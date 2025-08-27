<?php
/**
 * Add Exam
 * Page for adding a new exam
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
$pageTitle = "Add New Exam";
$currentPage = "exams";

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
        
        // Insert exam record
        $stmt = $conn->prepare("INSERT INTO exams 
                              (exam_name, class_id, subject_id, exam_date, start_time, duration, total_marks, passing_marks, description, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
            $_SESSION['user_id']
        ]);
        
        $exam_id = $conn->lastInsertId();
        
        $success_message = "Exam has been created successfully!";
        
        // Redirect to enter results page if requested
        if (isset($_POST['submit_and_enter_results'])) {
            header("Location: enter_results.php?exam_id=$exam_id");
            exit();
        }
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
            <i class="fas fa-plus-circle me-1"></i>
            Exam Details
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="exam_name" class="form-label">Exam Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="exam_name" name="exam_name" required>
                    </div>
                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="exam_date" class="form-label">Exam Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date" required>
                    </div>
                    <div class="col-md-4">
                        <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </div>
                    <div class="col-md-4">
                        <label for="duration" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="total_marks" class="form-label">Total Marks <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="total_marks" name="total_marks" min="1" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label for="passing_marks" class="form-label">Passing Marks <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="passing_marks" name="passing_marks" min="0" step="0.01" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Exam
                    </button>
                    <button type="submit" name="submit_and_enter_results" class="btn btn-success ms-2">
                        <i class="fas fa-save"></i> Save & Enter Results
                    </button>
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
        // Set default values
        $('#exam_date').val('<?php echo date('Y-m-d'); ?>');
        $('#start_time').val('<?php echo date('H:i'); ?>');
        $('#duration').val('60');
        $('#total_marks').val('100');
        $('#passing_marks').val('40');
        
        // Validate passing marks
        $('#passing_marks, #total_marks').change(function() {
            const totalMarks = parseFloat($('#total_marks').val()) || 0;
            const passingMarks = parseFloat($('#passing_marks').val()) || 0;
            
            if (passingMarks >= totalMarks) {
                alert('Passing marks must be less than total marks.');
                $('#passing_marks').val(Math.floor(totalMarks * 0.4));
            }
        });
    });
</script>