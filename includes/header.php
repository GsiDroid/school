<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: /login.php");
    exit;
}

// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Include common functions
require_once __DIR__ . '/functions.php';

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get user role for menu access control
$user_role = $_SESSION['user_role'] ?? '';

// Define theme colors
$theme_colors = [
    'blue' => ['primary' => '#4e73df', 'secondary' => '#2e59d9'],
    'green' => ['primary' => '#1cc88a', 'secondary' => '#169b6b'],
    'dark' => ['primary' => '#5a5c69', 'secondary' => '#484a54'],
    'orange' => ['primary' => '#f6c23e', 'secondary' => '#dda20a'],
    'purple' => ['primary' => '#6f42c1', 'secondary' => '#5a32a3']
];

// Default theme
$theme = 'blue';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    
    <style>
        :root {
            --primary-color: <?php echo $theme_colors[$theme]['primary']; ?>;
            --secondary-color: <?php echo $theme_colors[$theme]['secondary']; ?>;
        }
    </style>
</head>
<body>
    <!-- Theme Selector -->
    <div class="theme-selector">
        <button class="theme-btn theme-blue" data-theme="blue" title="Corporate Blue"></button>
        <button class="theme-btn theme-green" data-theme="green" title="Academic Green"></button>
        <button class="theme-btn theme-dark" data-theme="dark" title="Modern Dark"></button>
        <button class="theme-btn theme-orange" data-theme="orange" title="Vibrant Orange"></button>
        <button class="theme-btn theme-purple" data-theme="purple" title="Clean Purple"></button>
    </div>
