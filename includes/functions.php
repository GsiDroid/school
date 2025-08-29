<?php
// Define BASE_URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . $host . $script_name);

/**
 * Common functions for the School Management System
 */

// Include the Database class
require_once __DIR__ . '/../config/database.php';

/**
 * Get database connection
 * @return PDO Database connection object
 */
function get_db_connection() {
    $database = new Database();
    return $database->getConnection();
}

/**
 * Set message in session
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function set_message($type, $message) {
    $_SESSION['messages'][] = [
        'type' => $type,
        'content' => $message
    ];
}

/**
 * Display messages from session
 */
function display_messages() {
    if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
        foreach ($_SESSION['messages'] as $message) {
            $type = $message['type'];
            $content = $message['content'];
            $alert_class = '';
            
            switch ($type) {
                case 'success':
                    $alert_class = 'alert-success';
                    break;
                case 'error':
                    $alert_class = 'alert-danger';
                    break;
                case 'warning':
                    $alert_class = 'alert-warning';
                    break;
                case 'info':
                    $alert_class = 'alert-info';
                    break;
                default:
                    $alert_class = 'alert-secondary';
            }
            
            echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
            echo $content;
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
        
        // Clear messages after displaying
        unset($_SESSION['messages']);
    }
}

/**
 * Format date to a readable format
 * @param string $date Date string
 * @param string $format Format string
 * @return string Formatted date
 */
function format_date($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

/**
 * Format currency
 * @param float $amount Amount to format
 * @param string $currency Currency symbol
 * @return string Formatted currency
 */
function format_currency($amount, $currency = '₹') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Generate a random string
 * @param int $length Length of the string
 * @return string Random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Check if user has permission
 * @param string $required_role Required role
 * @return bool True if user has permission, false otherwise
 */
function has_permission($required_role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'];
    
    // Admin has all permissions
    if ($user_role === 'admin') {
        return true;
    }
    
    // Staff has limited permissions
    if ($user_role === 'staff' && $required_role === 'staff') {
        return true;
    }
    
    // Viewer has very limited permissions
    if ($user_role === 'viewer' && $required_role === 'viewer') {
        return true;
    }
    
    return false;
}

/**
 * Log user activity
 * @param string $activity_type Type of activity
 * @param string $description Description of activity
 */
function log_activity($activity_type, $description) {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $activity_type,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (PDOException $e) {
        // Just log the error, don't stop execution
        error_log("Activity Log Error: " . $e->getMessage());
    }
}