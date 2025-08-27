<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="assets/img/logo.png" alt="School Logo" class="sidebar-logo">
        <div class="sidebar-brand">School MS</div>
    </div>
    
    <ul class="sidebar-menu">
        <!-- Dashboard -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/dashboard.php') !== false && dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'dashboard.php' : '../dashboard.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                <span class="sidebar-menu-text">Dashboard</span>
            </a>
        </li>
        
        <!-- Student Management -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'students/index.php' : '../students/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'students' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-user-graduate"></i></span>
                <span class="sidebar-menu-text">Students</span>
            </a>
        </li>
        
        <!-- Fees Management -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'fees/index.php' : '../fees/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'fees' || $currentPage === 'fees_management' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-dollar-sign"></i></span>
                <span class="sidebar-menu-text">Fees</span>
            </a>
        </li>
        
        <!-- Expenses Management -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'expenses/index.php' : '../expenses/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'expenses' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                <span class="sidebar-menu-text">Expenses</span>
            </a>
        </li>
        
        <div class="sidebar-divider"></div>
        
        <!-- Attendance -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'attendance/index.php' : '../attendance/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'attendance' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="sidebar-menu-text">Attendance</span>
            </a>
        </li>
        
        <!-- Exams & Results -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'exams/index.php' : '../exams/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'exams' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-graduation-cap"></i></span>
                <span class="sidebar-menu-text">Exams & Results</span>
            </a>
        </li>
        
        <!-- Photo Gallery -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'gallery/index.php' : '../gallery/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'gallery' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-images"></i></span>
                <span class="sidebar-menu-text">Gallery</span>
            </a>
        </li>
        
        <!-- Communication (Only visible to admin) -->
        <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'communication/index.php' : '../communication/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'communication' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-envelope"></i></span>
                <span class="sidebar-menu-text">Communication</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
        <div class="sidebar-divider"></div>
        
        <!-- Settings -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'settings/index.php' : '../settings/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-cog"></i></span>
                <span class="sidebar-menu-text">Settings</span>
            </a>
        </li>
        
        <!-- Users -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'users/index.php' : '../users/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-users-cog"></i></span>
                <span class="sidebar-menu-text">Users</span>
            </a>
        </li>
        
        <!-- Backup & Restore -->
        <li class="sidebar-menu-item">
            <a href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'backup/index.php' : '../backup/index.php'; ?>" class="sidebar-menu-link <?php echo $currentPage === 'backup' ? 'active' : ''; ?>">
                <span class="sidebar-menu-icon"><i class="fas fa-database"></i></span>
                <span class="sidebar-menu-text">Backup & Restore</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
    
    <!-- Sidebar Footer -->
<div class="mt-auto p-3 text-center">
    <a href="logout.php" class="btn btn-light btn-sm">
        <i class="fas fa-sign-out-alt"></i>
        <span class="sidebar-menu-text">Logout</span>
    </a>
</div>
</div>

<!-- Top Navbar -->
<div class="main-content" id="main-content">
    <div class="top-navbar">
        <button id="sidebar-toggle" class="navbar-toggler">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="navbar-search position-relative">
            <input type="text" class="form-control navbar-search-input" placeholder="Search...">
            <i class="fas fa-search navbar-search-icon"></i>
        </div>
        
        <div class="navbar-user">
            <div class="navbar-user-name d-none d-md-block">
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
            </div>
            <div class="dropdown">
                <a class="dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="assets/img/user-avatar.png" alt="User" class="navbar-user-img">
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'profile.php' : '../profile.php'; ?>"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'settings/index.php' : '../settings/index.php'; ?>"><i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i> Settings</a></li>
                    <li><a class="dropdown-item" href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'activity-log.php' : '../activity-log.php'; ?>"><i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i> Activity Log</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo (dirname($_SERVER['PHP_SELF']) == '/xampp/htdocs') ? 'logout.php' : '../logout.php'; ?>"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
