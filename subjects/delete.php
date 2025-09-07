<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php?error=Access Denied");
    exit();
}

$subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$subject_id) {
    header("Location: index.php?error=Invalid ID");
    exit();
}

// Check if the subject is assigned to any class
$stmt = $pdo->prepare("SELECT COUNT(*) FROM class_subjects WHERE subject_id = ?");
$stmt->execute([$subject_id]);
if ($stmt->fetchColumn() > 0) {
    header("Location: index.php?error=Cannot delete subject. It is currently assigned to one or more classes.");
    exit();
}

// Proceed with deletion
try {
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    log_activity($pdo, $_SESSION['user_id'], "Deleted subject with ID: $subject_id");
    header("Location: index.php?success=Subject deleted successfully.");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=Failed to delete subject.");
    exit();
}
?>