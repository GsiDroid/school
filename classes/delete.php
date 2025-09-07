<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php?error=Access Denied");
    exit();
}

$class_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$class_id) {
    header("Location: index.php?error=Invalid ID");
    exit();
}

// Check if any students are assigned to this class
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
$stmt->execute([$class_id]);
if ($stmt->fetchColumn() > 0) {
    header("Location: index.php?error=Cannot delete class. Students are currently assigned to it.");
    exit();
}

// Add similar checks for teachers or subjects if necessary in the future

// Proceed with deletion
try {
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    log_activity($pdo, $_SESSION['user_id'], "Deleted class with ID: $class_id");
    header("Location: index.php?success=Class deleted successfully.");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=Failed to delete class. It might be in use.");
    exit();
}
?>