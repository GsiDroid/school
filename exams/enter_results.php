<?php
/**
 * Enter Results
 * Page for entering exam results for students
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
$pageTitle = "Enter Exam Results";
$currentPage = "exams";

// Check if exam_id is provided
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    header("Location: index.php");
    exit();
}

$exam_id = (int)$_GET['exam_id'];

// Get exam details
$stmt = $conn->prepare("SELECT e.*, c.class_name, s.subject_name 
                       FROM exams e 
                       JOIN classes c ON e.class_id = c.id 
                       JOIN subjects s ON e.subject_id = s.id 
                       WHERE e.id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header("Location: index.php");
    exit();
}

// Get students in the class
$stmt = $conn->prepare("SELECT id, first_name, last_name, roll_number 
                       FROM students 
                       WHERE class_id = ? 
                       ORDER BY roll_number, first_name, last_name");
$stmt->execute([$exam['class_id']]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if results already exist for this exam
$stmt = $conn->prepare("SELECT student_id, marks_obtained, remarks, status 
                       FROM exam_results 
                       WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$existingResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create associative array for easier access
$studentResults = [];
foreach ($existingResults as $result) {
    $studentResults[$result['student_id']] = $result;
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Delete existing results for this exam
        $stmt = $conn->prepare("DELETE FROM exam_results WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        
        // Insert new results
        $stmt = $conn->prepare("INSERT INTO exam_results 
                              (exam_id, student_id, marks_obtained, remarks, status, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($_POST['student'] as $student_id => $data) {
            // Skip if student is absent and no marks are entered
            if ($data['status'] === 'absent' && empty($data['marks'])) {
                $marks = 0;
            } else {
                $marks = !empty($data['marks']) ? (float)$data['marks'] : 0;
            }
            
            $remarks = isset($data['remarks']) ? trim($data['remarks']) : '';
            $status = isset($data['status']) ? $data['status'] : 'present';
            
            // Validate marks
            if ($status === 'present' && ($marks < 0 || $marks > $exam['total_marks'])) {
                throw new Exception("Invalid marks for student ID $student_id. Marks must be between 0 and {$exam['total_marks']}.");
            }
            
            // Insert result
            $stmt->execute([
                $exam_id,
                $student_id,
                $marks,
                $remarks,
                $status,
                $_SESSION['user_id']
            ]);
        }
        
        $conn->commit();
        $success_message = "Exam results have been saved successfully!";
        
        // Refresh existing results
        $stmt = $conn->prepare("SELECT student_id, marks_obtained, remarks, status 
                               FROM exam_results 
                               WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        $existingResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update associative array
        $studentResults = [];
        foreach ($existingResults as $result) {
            $studentResults[$result['student_id']] = $result;
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
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
            <i class="fas fa-info-circle me-1"></i>
            Exam Information
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Exam Name:</strong> <?php echo htmlspecialchars($exam['exam_name']); ?></p>
                    <p><strong>Class:</strong> <?php echo htmlspecialchars($exam['class_name']); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($exam['subject_name']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($exam['exam_date'])); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Total Marks:</strong> <?php echo $exam['total_marks']; ?></p>
                    <p><strong>Passing Marks:</strong> <?php echo $exam['passing_marks']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($students)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-1"></i>
            No students found in this class. Please add students to the class first.
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-edit me-1"></i>
                Enter Results
            </div>
            <div class="card-body">
                <form action="" method="POST" id="resultsForm">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">Roll No</th>
                                    <th width="25%">Student Name</th>
                                    <th width="15%">Status</th>
                                    <th width="15%">Marks (<?php echo $exam['total_marks']; ?>)</th>
                                    <th width="15%">Result</th>
                                    <th width="15%">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; foreach ($students as $student): ?>
                                    <?php 
                                    $studentId = $student['id'];
                                    $marks = isset($studentResults[$studentId]) ? $studentResults[$studentId]['marks_obtained'] : '';
                                    $remarks = isset($studentResults[$studentId]) ? $studentResults[$studentId]['remarks'] : '';
                                    $status = isset($studentResults[$studentId]) ? $studentResults[$studentId]['status'] : 'present';
                                    $resultClass = '';
                                    $resultText = '';
                                    
                                    if ($status === 'present' && $marks !== '') {
                                        if ($marks >= $exam['passing_marks']) {
                                            $resultClass = 'text-success';
                                            $resultText = 'Pass';
                                        } else {
                                            $resultClass = 'text-danger';
                                            $resultText = 'Fail';
                                        }
                                    } else if ($status === 'absent') {
                                        $resultClass = 'text-warning';
                                        $resultText = 'Absent';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td>
                                            <select name="student[<?php echo $student['id']; ?>][status]" class="form-select status-select">
                                                <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>Present</option>
                                                <option value="absent" <?php echo $status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control marks-input" 
                                                   name="student[<?php echo $student['id']; ?>][marks]" 
                                                   value="<?php echo $marks; ?>" 
                                                   min="0" max="<?php echo $exam['total_marks']; ?>" step="0.01"
                                                   <?php echo $status === 'absent' ? 'disabled' : ''; ?>>
                                        </td>
                                        <td class="<?php echo $resultClass; ?> result-cell">
                                            <?php echo $resultText; ?>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" 
                                                   name="student[<?php echo $student['id']; ?>][remarks]" 
                                                   value="<?php echo htmlspecialchars($remarks); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Results
                        </button>
                        <a href="view_results.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-info ms-2">
                            <i class="fas fa-eye"></i> View Results
                        </a>
                        <a href="index.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-arrow-left"></i> Back to Exams
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Handle status change
        $('.status-select').change(function() {
            const row = $(this).closest('tr');
            const marksInput = row.find('.marks-input');
            const resultCell = row.find('.result-cell');
            
            if ($(this).val() === 'absent') {
                marksInput.prop('disabled', true);
                marksInput.val('0');
                resultCell.removeClass('text-success text-danger').addClass('text-warning').text('Absent');
            } else {
                marksInput.prop('disabled', false);
                updateResult(row);
            }
        });
        
        // Handle marks change
        $('.marks-input').change(function() {
            updateResult($(this).closest('tr'));
        });
        
        // Function to update result
        function updateResult(row) {
            const marksInput = row.find('.marks-input');
            const resultCell = row.find('.result-cell');
            const statusSelect = row.find('.status-select');
            
            if (statusSelect.val() === 'absent') {
                resultCell.removeClass('text-success text-danger').addClass('text-warning').text('Absent');
                return;
            }
            
            const marks = parseFloat(marksInput.val()) || 0;
            const passingMarks = <?php echo $exam['passing_marks']; ?>;
            
            if (marks >= passingMarks) {
                resultCell.removeClass('text-danger text-warning').addClass('text-success').text('Pass');
            } else {
                resultCell.removeClass('text-success text-warning').addClass('text-danger').text('Fail');
            }
        }
        
        // Quick actions
        $('#markAllPresent').click(function() {
            $('.status-select').val('present').trigger('change');
        });
        
        $('#markAllAbsent').click(function() {
            $('.status-select').val('absent').trigger('change');
        });
    });
</script>