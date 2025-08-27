<?php
session_start();
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if exam ID is provided
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    set_message('error', 'Invalid exam ID.');
    header('Location: index.php');
    exit;
}

$exam_id = intval($_GET['exam_id']);

// Get exam details
$exam = null;
$students = [];

try {
    $pdo = get_db_connection();
    
    // Get exam details
    $stmt = $pdo->prepare("
        SELECT e.*, c.name AS class_name, s.subject_name, 
               u.name AS created_by_name
        FROM exams e
        LEFT JOIN classes c ON e.class_id = c.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam) {
        set_message('error', 'Exam not found.');
        header('Location: index.php');
        exit;
    }
    
    // Get students with their results
    $stmt = $pdo->prepare("
        SELECT s.id, s.admission_no, s.first_name, s.last_name,
               er.id AS result_id, er.marks_obtained, er.status, er.remarks
        FROM students s
        LEFT JOIN exam_results er ON s.id = er.student_id AND er.exam_id = ?
        WHERE s.current_class_id = ? AND s.status = 'active'
        ORDER BY s.first_name, s.last_name
    ");
    $stmt->execute([$exam_id, $exam['class_id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        set_message('error', 'Invalid request. Please try again.');
        header('Location: edit_results.php?exam_id=' . $exam_id);
        exit;
    }
    
    try {
        $pdo = get_db_connection();
        $pdo->beginTransaction();
        
        foreach ($students as $student) {
            $student_id = $student['id'];
            $status = isset($_POST['status'][$student_id]) ? $_POST['status'][$student_id] : 'absent';
            $marks = ($status === 'present') ? floatval($_POST['marks'][$student_id]) : 0;
            $remarks = isset($_POST['remarks'][$student_id]) ? trim($_POST['remarks'][$student_id]) : '';
            
            // Validate marks
            if ($status === 'present' && ($marks < 0 || $marks > $exam['total_marks'])) {
                $pdo->rollBack();
                set_message('error', 'Invalid marks for student ' . $student['first_name'] . ' ' . $student['last_name'] . '. Marks must be between 0 and ' . $exam['total_marks'] . '.');
                header('Location: edit_results.php?exam_id=' . $exam_id);
                exit;
            }
            
            if (!empty($student['result_id'])) {
                // Update existing result
                $stmt = $pdo->prepare("
                    UPDATE exam_results 
                    SET marks_obtained = ?, status = ?, remarks = ?, updated_by = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$marks, $status, $remarks, $_SESSION['user_id'], $student['result_id']]);
            } else {
                // Insert new result
                $stmt = $pdo->prepare("
                    INSERT INTO exam_results (exam_id, student_id, marks_obtained, status, remarks, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$exam_id, $student_id, $marks, $status, $remarks, $_SESSION['user_id']]);
            }
        }
        
        $pdo->commit();
        set_message('success', 'Exam results updated successfully.');
        header('Location: view_results.php?exam_id=' . $exam_id);
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Database error: ' . $e->getMessage());
        header('Location: edit_results.php?exam_id=' . $exam_id);
        exit;
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = 'Edit Exam Results';
include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Exams</a></li>
        <li class="breadcrumb-item"><a href="view_results.php?exam_id=<?php echo $exam_id; ?>">View Results</a></li>
        <li class="breadcrumb-item active">Edit Results</li>
    </ol>
    
    <?php display_messages(); ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Edit Results for <?php echo htmlspecialchars($exam['exam_name']); ?>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th>Exam Name:</th>
                            <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Class:</th>
                            <td><?php echo htmlspecialchars($exam['class_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Subject:</th>
                            <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th>Date:</th>
                            <td><?php echo date('d M Y', strtotime($exam['exam_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Total Marks:</th>
                            <td><?php echo $exam['total_marks']; ?></td>
                        </tr>
                        <tr>
                            <th>Passing Marks:</th>
                            <td><?php echo $exam['passing_marks']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <form method="post" action="edit_results.php?exam_id=<?php echo $exam_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Status</th>
                                <th>Marks Obtained</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No students found in this class.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td>
                                        <select name="status[<?php echo $student['id']; ?>]" class="form-select status-select" data-student-id="<?php echo $student['id']; ?>">
                                            <option value="present" <?php echo (isset($student['status']) && $student['status'] === 'present') ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo (isset($student['status']) && $student['status'] === 'absent') ? 'selected' : ''; ?>>Absent</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="marks[<?php echo $student['id']; ?>]" class="form-control marks-input" 
                                               value="<?php echo isset($student['marks_obtained']) ? $student['marks_obtained'] : ''; ?>" 
                                               min="0" max="<?php echo $exam['total_marks']; ?>" step="0.01"
                                               <?php echo (isset($student['status']) && $student['status'] === 'absent') ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[<?php echo $student['id']; ?>]" class="form-control" 
                                               value="<?php echo isset($student['remarks']) ? htmlspecialchars($student['remarks']) : ''; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Results</button>
                    <a href="view_results.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Handle status change
    $('.status-select').change(function() {
        const studentId = $(this).data('student-id');
        const status = $(this).val();
        const marksInput = $('input[name="marks[' + studentId + ']"]');
        
        if (status === 'absent') {
            marksInput.prop('disabled', true);
            marksInput.val('0');
        } else {
            marksInput.prop('disabled', false);
        }
    });
});
</script>