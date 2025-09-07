<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php?error=Access Denied");
    exit();
}

$student_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$student_id) {
    header("Location: index.php?error=Invalid ID");
    exit();
}

// First, get the photo filename to delete it from the server
$stmt = $pdo->prepare("SELECT photo FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$photo = $stmt->fetchColumn();

if ($photo) {
    $photo_path = __DIR__ . '/../uploads/students/' . $photo;
    if (file_exists($photo_path)) {
        unlink($photo_path);
    }
}

// Now, delete the student record from the database
$stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
$stmt->execute([$student_id]);

log_activity($pdo, $_SESSION['user_id'], "Deleted student with ID: $student_id");

header("Location: index.php?success=Student deleted successfully.");
exit();
?>