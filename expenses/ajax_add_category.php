<?php
/**
 * AJAX handler for adding expense categories
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Include common functions
require_once '../includes/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Process POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate required fields
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit();
    }
    
    try {
        // Check if category already exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM expense_categories WHERE name = ?");
        $check_stmt->execute([$name]);
        $count = $check_stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => 'A category with this name already exists']);
            exit();
        }
        
        // Insert new category
        $stmt = $conn->prepare("INSERT INTO expense_categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        
        $category_id = $conn->lastInsertId();
        
        // Return success response with new category data
        echo json_encode([
            'success' => true, 
            'message' => 'Category added successfully',
            'category' => [
                'id' => $category_id,
                'name' => $name,
                'description' => $description
            ]
        ]);
        
    } catch (PDOException $e) {
        // Log the error (in a production environment)
        error_log('Database error: ' . $e->getMessage());
        
        // Return error response
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>