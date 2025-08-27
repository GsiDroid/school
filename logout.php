<?php
session_start();

// Include common functions
require_once 'includes/functions.php';

// Get database connection
$conn = get_db_connection();

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
        $logStmt->execute([
            $_SESSION['user_id'],
            'logout',
            'User logged out successfully',
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        // Just log the error but continue with logout
        error_log("Logout Error: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;