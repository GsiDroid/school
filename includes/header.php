<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetch settings
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$_SESSION['settings'] = $settings;

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$base_path = '/school/'; // Adjust if your project is in a subfolder
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php require_once 'sidebar.php'; ?>
        <div class="main-content">
            <header class="top-header">
                <button id="menu-toggle" class="menu-toggle"><i class="bi bi-list"></i></button>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="logout.php" class="btn btn-logout">Logout <i class="bi bi-box-arrow-right"></i></a>
                </div>
            </header>
            <main class="content-area">