<?php
function log_activity($pdo, $user_id, $activity) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity) VALUES (?, ?)");
        $stmt->execute([$user_id, $activity]);
    } catch (PDOException $e) {
        // Optional: log error to a file
    }
}

function get_user_role($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Add more helper functions here as the application grows
?>