<?php
/**
 * Delete Fee Category
 * Handles deletion of fee categories and associated fee structures
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
    
    // Get category ID
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    
    // Validate category ID
    if ($category_id <= 0) {
        $_SESSION['error'] = "Invalid category ID";
        header("Location: fee_structure.php");
        exit();
    }
    
    try {
        // Get category name for activity log
        $stmt = $conn->prepare("SELECT name FROM fee_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category_name = $stmt->fetchColumn();
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Delete associated fee structures
        $stmt = $conn->prepare("DELETE FROM fee_structures WHERE fee_category_id = ?");
        $stmt->execute([$category_id]);
        
        // Delete the category
        $stmt = $conn->prepare("DELETE FROM fee_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        
        // Log activity
        $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
            (user_id, activity_type, description, ip_address) 
            VALUES (?, ?, ?, ?)");
        
        $activity_stmt->execute([
            $_SESSION['user_id'],
            'fee_category_deleted',
            "Deleted fee category: {$category_name}",
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Fee category deleted successfully";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Redirect back to fee structure page
header("Location: fee_structure.php");
exit();