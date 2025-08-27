<?php
/**
 * Gallery Management - Upload Images
 * Handles AJAX request to upload gallery images
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

// Check if it's a POST request with files
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    // Validate required fields
    if (empty($_POST['title'])) {
        $response['message'] = 'Image title is required';
    } else {
        try {
            // Create upload directory if it doesn't exist
            $upload_dir = "../uploads/gallery/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $uploaded_count = 0;
            $failed_count = 0;
            
            // Process each uploaded file
            $file_count = count($_FILES['images']['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    // Validate file type
                    if (!in_array($_FILES['images']['type'][$i], $allowed_types)) {
                        $failed_count++;
                        continue;
                    }
                    
                    // Validate file size
                    if ($_FILES['images']['size'][$i] > $max_size) {
                        $failed_count++;
                        continue;
                    }
                    
                    // Generate unique filename
                    $filename = time() . '_' . uniqid() . '_' . $_FILES['images']['name'][$i];
                    $target_file = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target_file)) {
                        // Insert image record into database
                        $stmt = $conn->prepare("INSERT INTO gallery_images 
                            (title, description, category_id, file_path, uploaded_by, upload_date) 
                            VALUES (?, ?, ?, ?, ?, NOW())");
                        
                        $stmt->execute([
                            $_POST['title'],
                            $_POST['description'] ?? null,
                            !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                            $filename,
                            $_SESSION['user_id']
                        ]);
                        
                        $uploaded_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    $failed_count++;
                }
            }
            
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                (user_id, activity_type, description, ip_address) 
                VALUES (?, ?, ?, ?)");
            
            $activity_stmt->execute([
                $_SESSION['user_id'],
                'gallery_images_uploaded',
                "Uploaded $uploaded_count images to gallery",
                $_SERVER['REMOTE_ADDR']
            ]);
            
            if ($uploaded_count > 0) {
                $response = [
                    'success' => true,
                    'message' => "$uploaded_count images uploaded successfully" . 
                                ($failed_count > 0 ? ", $failed_count failed" : "")
                ];
            } else {
                $response['message'] = 'Failed to upload any images';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
} else {
    $response['message'] = 'No images uploaded';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);