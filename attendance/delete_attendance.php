<?php
/**
 * Delete Attendance
 * Handles deletion of attendance records
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
$db = new Database();
$conn = $db->getConnection();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid attendance record ID.";
    header("Location: index.php");
    exit();
}

$attendance_id = (int)$_GET['id'];

// Verify attendance record exists
$stmt = $conn->prepare("SELECT id FROM attendance WHERE id = ?");
$stmt->execute([$attendance_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attendance) {
    $_SESSION['error'] = "Attendance record not found.";
    header("Location: index.php");
    exit();
}

try {
    // Delete attendance record
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
    $stmt->execute([$attendance_id]);
    
    $_SESSION['success'] = "Attendance record has been deleted successfully.";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting attendance record: " . $e->getMessage();
}

// Redirect back to attendance list
header("Location: index.php");
exit();