<?php
/**
 * Update Fee Category
 * Handles updating existing fee categories
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
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);
    
    // Validate category ID
    if ($category_id <= 0) {
        $_SESSION['error'] = "Invalid category ID";
        header("Location: fee_structure.php");
        exit();
    }
    
    // Validate category name
    if (empty($category_name)) {
        $_SESSION['error'] = "Category name is required";
        header("Location: fee_structure.php");
        exit();
    }
    
    // Check if category name already exists for other categories
    $stmt = $conn->prepare("SELECT COUNT(*) FROM fee_categories WHERE name = ? AND id != ?");
    $stmt->execute([$category_name, $category_id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Fee category with this name already exists";
        header("Location: fee_structure.php");
        exit();
    }
    
    try {
        // Update category
        $stmt = $conn->prepare("UPDATE fee_categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$category_name, $category_description, $category_id]);
        
        // Log activity
        $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
            (user_id, activity_type, description, ip_address) 
            VALUES (?, ?, ?, ?)");
        
        $activity_stmt->execute([
            $_SESSION['user_id'],
            'fee_category_updated',
            "Updated fee category: {$category_name}",
            $_SERVER['REMOTE_ADDR']
        ]);
        
        $_SESSION['success'] = "Fee category updated successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Redirect back to fee structure page
header("Location: fee_structure.php");
exit();