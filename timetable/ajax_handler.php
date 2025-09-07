<?php
require_once __DIR__ . '/../config/database.php';
session_start();
header('Content-Type: application/json');

if ($_SESSION['role'] !== 'Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit();
}

$class_id = $_POST['class_id'];
$day = $_POST['day'];
$start_time = $_POST['start_time'];
$subject_id = $_POST['subject_id'];
$teacher_id = $_POST['teacher_id'];

// Simple validation
if (empty($class_id) || empty($day) || empty($start_time)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit();
}

// Determine end time (e.g., 1 hour period)
$end_time = date('H:i:s', strtotime($start_time . ' +1 hour'));

try {
    // Use REPLACE INTO (or DELETE then INSERT) to simplify logic
    $delete_stmt = $pdo->prepare("DELETE FROM timetable WHERE class_id = ? AND day_of_week = ? AND start_time = ?");
    $delete_stmt->execute([$class_id, $day, $start_time]);

    if ($subject_id > 0) { // Only insert if a subject is selected
        $insert_stmt = $pdo->prepare("INSERT INTO timetable (class_id, day_of_week, start_time, end_time, subject_id, teacher_id) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_stmt->execute([$class_id, $day, $start_time, $end_time, $subject_id, $teacher_id]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Timetable updated!']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>