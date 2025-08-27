<?php
/**
 * Gallery Management - Delete Image
 * Handles AJAX request to delete a gallery image
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

// Check if it's a POST request with image ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $image_id = $_POST['id'];
    
    try {
        // Get image file path before deletion
        $stmt = $conn->prepare("SELECT file_path FROM gallery_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            // Begin transaction
            $conn->beginTransaction();
            
            // Delete image record from database
            $stmt = $conn->prepare("DELETE FROM gallery_images WHERE id = ?");
            $stmt->execute([$image_id]);
            
            // Delete physical file if exists
            $file_path = "../uploads/gallery/" . $image['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                (user_id, activity_type, description, ip_address) 
                VALUES (?, ?, ?, ?)");
            
            $activity_stmt->execute([
                $_SESSION['user_id'],
                'gallery_image_deleted',
                "Deleted gallery image (ID: $image_id)",
                $_SERVER['REMOTE_ADDR']
            ]);
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Image deleted successfully'
            ];
        } else {
            $response['message'] = 'Image not found';
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);