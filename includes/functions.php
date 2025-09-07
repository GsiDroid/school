<?php
require_once __DIR__ . '/../config/database.php';

function log_activity($pdo, $user_id, $activity) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity) VALUES (?, ?)");
        $stmt->execute([$user_id, $activity]);
    } catch (PDOException $e) {
        // Optional: log error to a file
    }
}

function get_db_connection() {
    $database = new Database();
    return $database->getConnection();
}

// Add more helper functions here as the application grows
?>