<?php
/**
 * View Results
 * Page for viewing exam results
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
$pageTitle = "View Exam Results";
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

// Get results with student information
$stmt = $conn->prepare("SELECT r.*, s.first_name, s.last_name, s.roll_number 
                       FROM exam_results r 
                       JOIN students s ON r.student_id = s.id 
                       WHERE r.exam_id = ? 
                       ORDER BY s.roll_number, s.first_name, s.last_name");
$stmt->execute([$exam_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalStudents = count($results);
$presentStudents = 0;
$absentStudents = 0;
$passedStudents = 0;
$failedStudents = 0;
$highestMarks = 0;
$lowestMarks = $exam['total_marks'];
$totalMarks = 0;

foreach ($results as $result) {
    if ($result['status'] === 'present') {
        $presentStudents++;
        $marks = (float)$result['marks_obtained'];
        $totalMarks += $marks;
        
        if ($marks >= $exam['passing_marks']) {
            $passedStudents++;
        } else {
            $failedStudents++;
        }
        
        if ($marks > $highestMarks) {
            $highestMarks = $marks;
        }
        
        if ($marks < $lowestMarks) {
            $lowestMarks = $marks;
        }
    } else {
        $absentStudents++;
    }
}

$averageMarks = $presentStudents > 0 ? round($totalMarks / $presentStudents, 2) : 0;
$passPercentage = $presentStudents > 0 ? round(($passedStudents / $presentStudents) * 100, 2) : 0;

// If no students are present, set lowest marks to 0
if ($presentStudents === 0) {
    $lowestMarks = 0;
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
    
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Total Students</div>
                            <div class="fs-4"><?php echo $totalStudents; ?></div>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Passed</div>
                            <div class="fs-4"><?php echo $passedStudents; ?> (<?php echo $passPercentage; ?>%)</div>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Failed</div>
                            <div class="fs-4"><?php echo $failedStudents; ?></div>
                        </div>
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Absent</div>
                            <div class="fs-4"><?php echo $absentStudents; ?></div>
                        </div>
                        <i class="fas fa-user-slash fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Average Marks</div>
                            <div class="fs-4"><?php echo $averageMarks; ?></div>
                        </div>
                        <i class="fas fa-calculator fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Highest Marks</div>
                            <div class="fs-4"><?php echo $highestMarks; ?></div>
                        </div>
                        <i class="fas fa-arrow-up fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Lowest Marks</div>
                            <div class="fs-4"><?php echo $lowestMarks; ?></div>
                        </div>
                        <i class="fas fa-arrow-down fa-2x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Exam Results
            </div>
            <div>
                <a href="enter_results.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i> Edit Results
                </a>
                <a href="print_results.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-sm btn-info" target="_blank">
                    <i class="fas fa-print"></i> Print Results
                </a>
                <a href="export_results.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($results)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    No results found for this exam. Please enter results first.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="resultsTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="10%">Roll No</th>
                                <th width="25%">Student Name</th>
                                <th width="10%">Status</th>
                                <th width="10%">Marks</th>
                                <th width="10%">Percentage</th>
                                <th width="10%">Result</th>
                                <th width="20%">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; foreach ($results as $result): ?>
                                <?php 
                                $marks = (float)$result['marks_obtained'];
                                $percentage = $exam['total_marks'] > 0 ? round(($marks / $exam['total_marks']) * 100, 2) : 0;
                                $resultClass = '';
                                $resultText = '';
                                
                                if ($result['status'] === 'present') {
                                    if ($marks >= $exam['passing_marks']) {
                                        $resultClass = 'text-success';
                                        $resultText = 'Pass';
                                    } else {
                                        $resultClass = 'text-danger';
                                        $resultText = 'Fail';
                                    }
                                } else {
                                    $resultClass = 'text-warning';
                                    $resultText = 'Absent';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($result['roll_number']); ?></td>
                                    <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                    <td><?php echo ucfirst($result['status']); ?></td>
                                    <td>
                                        <?php if ($result['status'] === 'present'): ?>
                                            <?php echo $marks; ?> / <?php echo $exam['total_marks']; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($result['status'] === 'present'): ?>
                                            <?php echo $percentage; ?>%
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?php echo $resultClass; ?>">
                                        <?php echo $resultText; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['remarks']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mb-4">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Exams
        </a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        $('#resultsTable').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    });
</script>