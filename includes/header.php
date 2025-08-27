<?php
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
    <link rel="shortcut icon" href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'assets/img/favicon.ico' : '../assets/img/favicon.ico'; ?>" type="image/x-icon">
    
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
    <link rel="stylesheet" href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'assets/css/style.css' : '../assets/css/style.css'; ?>">
    
    <style>
        :root {
            --primary-color: <?php echo $theme_colors[$theme]['primary']; ?>;
            --secondary-color: <?php echo $theme_colors[$theme]['secondary']; ?>;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-collapsed {
            width: 70px;
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            max-width: 50px;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            font-size: 1.2rem;
            font-weight: 600;
            margin-top: 0.5rem;
            transition: all 0.3s;
        }
        
        .sidebar-collapsed .sidebar-brand {
            display: none;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
            list-style: none;
            margin: 0;
        }
        
        .sidebar-menu-item {
            padding: 0.5rem 1rem;
        }
        
        .sidebar-menu-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }
        
        .sidebar-menu-link:hover, .sidebar-menu-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu-icon {
            width: 20px;
            text-align: center;
            margin-right: 0.75rem;
            font-size: 1rem;
        }
        
        .sidebar-collapsed .sidebar-menu-text {
            display: none;
        }
        
        .sidebar-collapsed .sidebar-menu-item {
            padding: 0.5rem 0;
            text-align: center;
        }
        
        .sidebar-collapsed .sidebar-menu-icon {
            margin-right: 0;
            font-size: 1.25rem;
        }
        
        .sidebar-collapsed .sidebar-menu-link {
            justify-content: center;
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 0.5rem 1rem;
        }
        
/* Main Content Styles */
.main-content {
    flex: 1;
    margin-left: 250px;
    padding-top: 70px; /* Add top padding for navbar */
    padding-right: 1rem;
    padding-left: 1rem;
    transition: all 0.3s;
}

.main-content-expanded {
    margin-left: 70px;
}
        
/* Navbar Styles */
.top-navbar {
    background-color: white;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    right: 0;
    left: 250px;
    z-index: 999;
    border-radius: 0.35rem;
    transition: all 0.3s;
}

.main-content-expanded .top-navbar {
    left: 70px;
}
        
        .navbar-toggler {
            background: none;
            border: none;
            color: #6e707e;
            font-size: 1.25rem;
            padding: 0.25rem 0.75rem;
            cursor: pointer;
        }
        
        .navbar-search {
            flex: 1;
            max-width: 400px;
            margin: 0 1rem;
        }
        
        .navbar-search-input {
            border-radius: 2rem;
            padding-left: 2.5rem;
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
        }
        
        .navbar-search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #d1d3e2;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
        }
        
        .navbar-user-name {
            margin-right: 0.5rem;
            font-weight: 500;
        }
        
        .navbar-user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Card Styles */
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-radius: 0.35rem;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            margin-bottom: 0;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 0.35rem;
            margin-bottom: 1.5rem;
        }
        
        .welcome-banner .card-body {
            padding: 2rem;
        }
        
        .current-date-time {
            text-align: right;
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-brand {
                display: none;
            }
            
            .sidebar-menu-text {
                display: none;
            }
            
            .sidebar-menu-item {
                padding: 0.5rem 0;
                text-align: center;
            }
            
            .sidebar-menu-icon {
                margin-right: 0;
                font-size: 1.25rem;
            }
            
            .sidebar-menu-link {
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .navbar-search {
                display: none;
            }
        }
        
        /* Theme Colors */
        .theme-selector {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 0.5rem;
        }
        
        .theme-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin: 0.25rem;
            cursor: pointer;
            border: 2px solid #fff;
        }
        
        .theme-blue { background-color: #4e73df; }
        .theme-green { background-color: #1cc88a; }
        .theme-dark { background-color: #5a5c69; }
        .theme-orange { background-color: #f6c23e; }
        .theme-purple { background-color: #6f42c1; }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Activity Timeline */
        .activity-timeline {
            position: relative;
        }
        
        .activity-item {
            padding: 1rem 0;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }
        
        /* Border Left Cards */
        .border-left-primary {
            border-left: 0.25rem solid var(--primary-color) !important;
        }
        
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        
        /* Announcement Styles */
        .announcement-item {
            padding: 0.75rem 0;
        }
        
        /* Calendar Styles */
        #calendar {
            height: 400px;
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
