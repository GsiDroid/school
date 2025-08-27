<?php
/**
 * Delete Exam
 * Script for deleting an exam and its results
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
    $_SESSION['error'] = "Invalid exam ID.";
    header("Location: index.php");
    exit();
}

$exam_id = (int)$_GET['exam_id'];

// Check if exam exists
$stmt = $conn->prepare("SELECT id FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    $_SESSION['error'] = "Exam not found.";
    header("Location: index.php");
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Delete exam results first (foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM exam_results WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    
    // Delete exam
    $stmt = $conn->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Exam and all related results have been deleted successfully.";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    $_SESSION['error'] = "Failed to delete exam: " . $e->getMessage();
}

// Redirect back to exams list
header("Location: index.php");
exit();