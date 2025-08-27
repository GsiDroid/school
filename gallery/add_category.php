<?php
/**
 * Gallery Management - Add Category
 * Handles AJAX request to add a new gallery category
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include common functions
require_once '../includes/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Set default response
$response = ['success' => false, 'message' => 'Invalid request'];

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['name'])) {
        $response['message'] = 'Category name is required';
    } else {
        try {
            // Check if category already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM gallery_categories WHERE name = ?");
            $stmt->execute([$_POST['name']]);
            
            if ($stmt->fetchColumn() > 0) {
                $response['message'] = 'Category already exists';
            } else {
                // Insert new category
                $stmt = $conn->prepare("INSERT INTO gallery_categories (name, description) VALUES (?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'] ?? null
                ]);
                
                $category_id = $conn->lastInsertId();
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'gallery_category_added',
                    "Added new gallery category: {$_POST['name']}",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $response = [
                    'success' => true, 
                    'message' => 'Category added successfully',
                    'category_id' => $category_id,
                    'category_name' => $_POST['name']
                ];
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);