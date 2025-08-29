<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/functions.php';
$conn = get_db_connection();

$student_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$student_id) {
    header("Location: index.php");
    exit;
}

// Fetch student data
$stmt = $conn->prepare("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.current_class_id = c.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: index.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fc;
        }
        .id-card-container {
            width: 350px;
            margin: 50px auto;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 10px;
            background-color: #fff;
        }
        .id-card-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .id-card-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .id-card-body {
            font-size: 14px;
        }
        .id-card-body p {
            margin-bottom: 5px;
        }
        .print-button {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .id-card-container, .id-card-container * {
                visibility: visible;
            }
            .id-card-container {
                position: absolute;
                left: 0;
                top: 0;
            }
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="id-card-container">
        <div class="id-card-header">
            <?php if ($student['profile_image']): ?>
                <img src="../uploads/students/<?php echo $student['profile_image']; ?>" alt="Profile Image">
            <?php else: ?>
                <img src="../assets/img/default-student.svg" alt="Default Profile Image">
            <?php endif; ?>
            <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
            <p>Student</p>
        </div>
        <div class="id-card-body">
            <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] ?? 'Not Assigned'); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo date('d M, Y', strtotime($student['date_of_birth'])); ?></p>
        </div>
    </div>

    <div class="print-button">
        <button class="btn btn-primary" onclick="window.print()">Print ID Card</button>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

</body>
</html>
