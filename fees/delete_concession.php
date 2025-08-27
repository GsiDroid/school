<?php
/**
 * Fees Management Module - Delete Concession
 * Handles deletion of fee concessions
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

// Verify request method and parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !isset($_POST['csrf_token'])) {
    header("Location: concessions.php?error=invalid_request");
    exit();
}

// Verify CSRF token
if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: concessions.php?error=invalid_token");
    exit();
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get concession ID
$concession_id = (int)$_POST['id'];

if ($concession_id <= 0) {
    header("Location: concessions.php?error=invalid_id");
    exit();
}

try {
    $conn->beginTransaction();
    
    // Get student ID for activity log
    $stmt = $conn->prepare("SELECT student_id FROM fee_concessions WHERE id = ?");
    $stmt->execute([$concession_id]);
    $student_id = $stmt->fetchColumn();
    
    if (!$student_id) {
        throw new Exception("Concession not found");
    }
    
    // Delete concession
    $stmt = $conn->prepare("DELETE FROM fee_concessions WHERE id = ?");
    $stmt->execute([$concession_id]);
    
    // Log activity
    $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
        (user_id, activity_type, description, ip_address) 
        VALUES (?, ?, ?, ?)");
    
    $activity_stmt->execute([
        $_SESSION['user_id'],
        'concession_deleted',
        "Deleted fee concession ID #{$concession_id} for student ID #{$student_id}",
        $_SERVER['REMOTE_ADDR']
    ]);
    
    $conn->commit();
    header("Location: concessions.php?success=deleted");
    exit();
    
} catch (Exception $e) {
    $conn->rollBack();
    header("Location: concessions.php?error=" . urlencode($e->getMessage()));
    exit();
}