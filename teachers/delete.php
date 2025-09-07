<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php?error=Access Denied");
    exit();
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: index.php?error=Invalid ID");
    exit();
}

// Deleting a teacher requires deleting from the users table, 
// which will cascade and delete the corresponding teachers record.
try {
    $pdo->beginTransaction();

    // First, remove the teacher from any class assignments
    $stmt = $pdo->prepare("UPDATE class_subjects SET teacher_id = NULL WHERE teacher_id = ?");
    $stmt->execute([$user_id]);

    // Then, delete the user record. The teachers record is deleted by cascade.
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'Teacher'");
    $stmt->execute([$user_id]);

    $pdo->commit();
    log_activity($pdo, $_SESSION['user_id'], "Deleted teacher with user ID: $user_id");
    header("Location: index.php?success=Teacher deleted successfully.");
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.php?error=Failed to delete teacher. " . $e->getMessage());
    exit();
}
?>