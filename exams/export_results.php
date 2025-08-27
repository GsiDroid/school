<?php
/**
 * Export Results
 * Page for exporting exam results to Excel
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
    } else {
        $absentStudents++;
    }
}

$averageMarks = $presentStudents > 0 ? round($totalMarks / $presentStudents, 2) : 0;
$passPercentage = $presentStudents > 0 ? round(($passedStudents / $presentStudents) * 100, 2) : 0;

// Set filename
$filename = "Exam_Results_" . preg_replace('/[^A-Za-z0-9]/', '_', $exam['exam_name']) . "_" . date('Y-m-d') . ".xls";

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

// Start output buffering
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Exam Results</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .pass { color: green; }
        .fail { color: red; }
        .absent { color: orange; }
    </style>
</head>
<body>
    <h1>Exam Results</h1>
    
    <h2>Exam Information</h2>
    <table>
        <tr>
            <th>Exam Name</th>
            <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
            <th>Class</th>
            <td><?php echo htmlspecialchars($exam['class_name']); ?></td>
        </tr>
        <tr>
            <th>Subject</th>
            <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
            <th>Date</th>
            <td><?php echo date('d M Y', strtotime($exam['exam_date'])); ?></td>
        </tr>
        <tr>
            <th>Total Marks</th>
            <td><?php echo $exam['total_marks']; ?></td>
            <th>Passing Marks</th>
            <td><?php echo $exam['passing_marks']; ?></td>
        </tr>
    </table>
    
    <h2>Summary</h2>
    <table>
        <tr>
            <th>Total Students</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Passed</th>
            <th>Failed</th>
            <th>Pass Percentage</th>
            <th>Average Marks</th>
        </tr>
        <tr>
            <td class="text-center"><?php echo $totalStudents; ?></td>
            <td class="text-center"><?php echo $presentStudents; ?></td>
            <td class="text-center"><?php echo $absentStudents; ?></td>
            <td class="text-center"><?php echo $passedStudents; ?></td>
            <td class="text-center"><?php echo $failedStudents; ?></td>
            <td class="text-center"><?php echo $passPercentage; ?>%</td>
            <td class="text-center"><?php echo $averageMarks; ?></td>
        </tr>
    </table>
    
    <h2>Detailed Results</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Roll No</th>
                <th>Student Name</th>
                <th>Status</th>
                <th>Marks</th>
                <th>Percentage</th>
                <th>Result</th>
                <th>Remarks</th>
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
                        $resultClass = 'pass';
                        $resultText = 'Pass';
                    } else {
                        $resultClass = 'fail';
                        $resultText = 'Fail';
                    }
                } else {
                    $resultClass = 'absent';
                    $resultText = 'Absent';
                }
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($result['roll_number']); ?></td>
                    <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                    <td><?php echo ucfirst($result['status']); ?></td>
                    <td class="text-center">
                        <?php if ($result['status'] === 'present'): ?>
                            <?php echo $marks; ?> / <?php echo $exam['total_marks']; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($result['status'] === 'present'): ?>
                            <?php echo $percentage; ?>%
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-center <?php echo $resultClass; ?>">
                        <?php echo $resultText; ?>
                    </td>
                    <td><?php echo htmlspecialchars($result['remarks']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p>Generated on: <?php echo date('d M Y H:i:s'); ?></p>
</body>
</html>

<?php
// Get the output buffer content and clean it
$output = ob_get_clean();

// Output the content
echo $output;
?>