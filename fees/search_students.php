<?php
/**
 * Fees Management Module - Search Students
 * AJAX endpoint to search for students by name or admission number
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Include common functions
require_once '../includes/functions.php';

// Check if search term is provided
if (!isset($_GET['term']) || empty($_GET['term'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Search term is required']);
    exit();
}

$search_term = trim($_GET['term']);

// Validate search term length
if (strlen($search_term) < 2) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Search term must be at least 2 characters']);
    exit();
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Search for students by name or admission number
    $stmt = $conn->prepare("SELECT s.id, s.first_name, s.last_name, s.admission_number, 
                            c.name as class_name, c.section 
                            FROM students s 
                            JOIN classes c ON s.class_id = c.id 
                            WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR 
                                  s.admission_number LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ?) 
                            AND s.status = 'Active' 
                            ORDER BY s.first_name, s.last_name 
                            LIMIT 10");
    
    $search_param = "%{$search_term}%";
    $stmt->execute([$search_param, $search_param, $search_param, $search_param]);
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results for select dropdown
    $results = [];
    foreach ($students as $student) {
        $results[] = [
            'id' => $student['id'],
            'text' => $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['admission_number'] . ') - ' . 
                     $student['class_name'] . ' ' . $student['section']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['results' => $results]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}