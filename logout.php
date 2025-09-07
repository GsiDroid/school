<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    log_activity($pdo, $_SESSION['user_id'], "User logged out");
}

session_unset();
session_destroy();

header("Location: index.php?message=logged_out");
exit();
?>