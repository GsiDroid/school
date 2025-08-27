<?php
/**
 * Print Results
 * Page for printing exam results
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Exam Results - <?php echo htmlspecialchars($exam['exam_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .school-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
        }
        .school-address {
            font-size: 14px;
        }
        .exam-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            text-decoration: underline;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .stat-box {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            width: 16%;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }
        .stat-label {
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .pass {
            color: green;
        }
        .fail {
            color: red;
        }
        .absent {
            color: orange;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 15px;
            }
            @page {
                size: A4;
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Print</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>
    
    <div class="school-header">
        <div class="school-name">SCHOOL MANAGEMENT SYSTEM</div>
        <div class="school-address">123 Education Street, Knowledge City</div>
        <div>Phone: (123) 456-7890 | Email: info@schoolmanagementsystem.com</div>
    </div>
    
    <div class="exam-title">
        EXAM RESULT SHEET
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Exam Name:</div>
            <div><?php echo htmlspecialchars($exam['exam_name']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Class:</div>
            <div><?php echo htmlspecialchars($exam['class_name']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Subject:</div>
            <div><?php echo htmlspecialchars($exam['subject_name']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Date:</div>
            <div><?php echo date('d M Y', strtotime($exam['exam_date'])); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Total Marks:</div>
            <div><?php echo $exam['total_marks']; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Passing Marks:</div>
            <div><?php echo $exam['passing_marks']; ?></div>
        </div>
    </div>
    
    <div class="stats-container">
        <div class="stat-box">
            <div class="stat-value"><?php echo $totalStudents; ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $presentStudents; ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $absentStudents; ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $passedStudents; ?></div>
            <div class="stat-label">Passed</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $failedStudents; ?></div>
            <div class="stat-label">Failed</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $passPercentage; ?>%</div>
            <div class="stat-label">Pass Percentage</div>
        </div>
    </div>
    
    <?php if (empty($results)): ?>
        <div class="alert alert-warning">
            No results found for this exam.
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="10%">Roll No</th>
                    <th width="25%">Student Name</th>
                    <th width="10%">Status</th>
                    <th width="15%">Marks</th>
                    <th width="10%">Percentage</th>
                    <th width="10%">Result</th>
                    <th width="15%">Remarks</th>
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
    <?php endif; ?>
    
    <div class="footer">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>Class Teacher</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>Examination Incharge</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>Principal</div>
        </div>
    </div>
    
    <div class="text-center" style="margin-top: 30px; font-size: 12px;">
        <p>Generated on: <?php echo date('d M Y H:i:s'); ?></p>
    </div>
</body>
</html>