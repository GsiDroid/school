<?php
/**
 * Delete Fee Structure
 * Handles deletion of fee structures for a specific class, term, and academic year
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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "CSRF token validation failed";
        header("Location: fee_structure.php");
        exit();
    }
    
    // Get database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get form data
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $term = isset($_POST['term']) ? $_POST['term'] : '';
    $academic_year = isset($_POST['academic_year']) ? $_POST['academic_year'] : '';
    
    // Validate inputs
    if ($class_id <= 0 || empty($term) || empty($academic_year)) {
        $_SESSION['error'] = "Invalid fee structure information";
        header("Location: fee_structure.php");
        exit();
    }
    
    try {
        // Get class name for activity log
        $stmt = $conn->prepare("SELECT name, section FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $class_name = $class_info ? $class_info['name'] . ' ' . $class_info['section'] : 'Unknown Class';
        
        // Delete fee structure
        $stmt = $conn->prepare("DELETE FROM fee_structures 
                              WHERE class_id = ? AND term = ? AND academic_year = ?");
        $stmt->execute([$class_id, $term, $academic_year]);
        
        // Log activity
        $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
            (user_id, activity_type, description, ip_address) 
            VALUES (?, ?, ?, ?)");
        
        $activity_stmt->execute([
            $_SESSION['user_id'],
            'fee_structure_deleted',
            "Deleted fee structure for {$class_name}, {$term}, {$academic_year}",
            $_SERVER['REMOTE_ADDR']
        ]);
        
        $_SESSION['success'] = "Fee structure deleted successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Redirect back to fee structure page
header("Location: fee_structure.php");
exit();