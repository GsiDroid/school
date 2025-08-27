<?php
/**
 * Fees Management Module - Update Concession
 * Handles updating of fee concessions
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['concession_id']) || !isset($_POST['csrf_token'])) {
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

// Get form data
$concession_id = (int)$_POST['concession_id'];
$student_id = (int)$_POST['student_id'];
$fee_category_id = (int)$_POST['fee_category_id'];
$concession_type = trim($_POST['concession_type']);
$concession_value = (float)$_POST['concession_value'];
$reason = trim($_POST['reason']);
$valid_from = $_POST['valid_from'];
$valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
$notes = trim($_POST['notes']);

// Validate required fields
$errors = [];

if ($concession_id <= 0) {
    $errors[] = "Invalid concession ID";
}

if ($student_id <= 0) {
    $errors[] = "Please select a student";
}

if ($fee_category_id <= 0) {
    $errors[] = "Please select a fee category";
}

if (empty($concession_type) || !in_array($concession_type, ['Percentage', 'Fixed Amount'])) {
    $errors[] = "Please select a valid concession type";
}

if ($concession_value <= 0) {
    $errors[] = "Concession value must be greater than zero";
}

if ($concession_type == 'Percentage' && $concession_value > 100) {
    $errors[] = "Percentage concession cannot exceed 100%";
}

if (empty($reason)) {
    $errors[] = "Reason is required";
}

if (empty($valid_from)) {
    $errors[] = "Valid from date is required";
}

// Check if concession exists
$stmt = $conn->prepare("SELECT COUNT(*) FROM fee_concessions WHERE id = ?");
$stmt->execute([$concession_id]);
if ($stmt->fetchColumn() == 0) {
    $errors[] = "Concession not found";
}

// If there are errors, redirect back with error message
if (!empty($errors)) {
    $error_str = implode(", ", $errors);
    header("Location: concessions.php?error=" . urlencode($error_str));
    exit();
}

try {
    $conn->beginTransaction();
    
    // Update concession
    $stmt = $conn->prepare("UPDATE fee_concessions 
                           SET fee_category_id = ?, concession_type = ?, 
                               concession_value = ?, reason = ?, valid_from = ?, 
                               valid_until = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
                           WHERE id = ?");
    
    $stmt->execute([
        $fee_category_id,
        $concession_type,
        $concession_value,
        $reason,
        $valid_from,
        $valid_until,
        $notes,
        $concession_id
    ]);
    
    // Log activity
    $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
        (user_id, activity_type, description, ip_address) 
        VALUES (?, ?, ?, ?)");
    
    $activity_stmt->execute([
        $_SESSION['user_id'],
        'concession_updated',
        "Updated fee concession ID #{$concession_id} for student ID #{$student_id}",
        $_SERVER['REMOTE_ADDR']
    ]);
    
    $conn->commit();
    header("Location: concessions.php?success=updated");
    exit();
    
} catch (Exception $e) {
    $conn->rollBack();
    header("Location: concessions.php?error=" . urlencode($e->getMessage()));
    exit();
}