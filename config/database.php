<?php
/**
 * Database Configuration File
 * 
 * This file establishes a connection to the MySQL database using PDO
 * for secure database interactions as specified in the requirements.
 */

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'school_management');
define('DB_USER', 'root'); // Change in production
define('DB_PASS', '');     // Change in production

/**
 * Database Class for handling database connections
 */
class Database {
    private $host = DB_HOST;
    private $dbname = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;
    
    /**
     * Get database connection
     * @return PDO Database connection object
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Set error mode
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Create a new PDO instance
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8mb4",
                $this->username,
                $this->password,
                $options
            );
        } catch (PDOException $e) {
            // Log error and display user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            die("Connection failed: The system is currently unavailable. Please try again later.");
        }
        
        return $this->conn;
    }
}