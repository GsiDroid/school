<?php
/**
 * Database Connection File
 * 
 * This file provides a simple database connection for API endpoints
 * and other scripts that need direct database access.
 */

// Include the main database configuration
require_once __DIR__ . '/database.php';

/**
 * Get database connection
 * @return PDO Database connection object
 */
function get_db_connection() {
    $database = new Database();
    return $database->getConnection();
}

/**
 * Get database connection with custom error handling
 * @return PDO Database connection object
 */
function get_api_db_connection() {
    try {
        $database = new Database();
        return $database->getConnection();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}
?>
