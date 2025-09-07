<aside class="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo $base_path; ?>assets/img/<?php echo htmlspecialchars($_SESSION['settings']['school_logo'] ?? 'default.png'); ?>" alt="Logo" class="sidebar-logo">
        <h2><?php echo htmlspecialchars($_SESSION['settings']['school_name'] ?? 'SMS'); ?></h2>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="home.php" class="active"><i class="bi bi-house-door-fill"></i> <span>Dashboard</span></a>
            </li>
            
            <?php if ($_SESSION['role'] == 'Admin'): ?>
            <li class="menu-header"><span>Admin</span></li>
            <li><a href="<?php echo $base_path; ?>students/index.php"><i class="bi bi-people-fill"></i> <span>Students</span></a></li>
            <li><a href="<?php echo $base_path; ?>teachers/index.php"><i class="bi bi-person-video3"></i> <span>Teachers</span></a></li>
            <li><a href="<?php echo $base_path; ?>classes/index.php"><i class="bi bi-bank"></i> <span>Classes</span></a></li>
            <li><a href="<?php echo $base_path; ?>subjects/index.php"><i class="bi bi-book-fill"></i> <span>Subjects</span></a></li>
            <li class="has-submenu">
                <a href="#"><i class="bi bi-cash-coin"></i> <span>Fee Management</span> <i class="bi bi-chevron-down"></i></a>
                <ul class="submenu">
                    <li><a href="<?php echo $base_path; ?>fees/categories/index.php">Fee Categories</a></li>
                    <li><a href="<?php echo $base_path; ?>fees/index.php">Fee Structure</a></li>
                    <li><a href="#">Payment Reports</a></li>
                </ul>
            </li>
            <li class="menu-header"><span>Reports</span></li>
            <li><a href="<?php echo $base_path; ?>attendance/reports.php"><i class="bi bi-table"></i> <span>Attendance Report</span></a></li>
            <li><a href="#"><i class="bi bi-gear-fill"></i> <span>Settings</span></a></li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'Teacher'): ?>
            <li class="menu-header"><span>Teacher</span></li>
            <li><a href="#"><i class="bi bi-calendar-check-fill"></i> <span>Attendance</span></a></li>
            <li><a href="#"><i class="bi bi-pencil-square"></i> <span>Exam Results</span></a></li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'Cashier'): ?>
            <li class="menu-header"><span>Cashier</span></li>
            <li><a href="#"><i class="bi bi-receipt"></i> <span>Collect Fees</span></a></li>
            <li><a href="#"><i class="bi bi-search"></i> <span>Search Student</span></a></li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'Student'): ?>
            <li class="menu-header"><span>Student</span></li>
            <li><a href="#"><i class="bi bi-person-fill"></i> <span>My Profile</span></a></li>
            <li><a href="<?php echo $base_path; ?>results/my_results.php"><i class="bi bi-journal-text"></i> <span>View Results</span></a></li>
            <li><a href="#"><i class="bi bi-check-circle-fill"></i> <span>View Attendance</span></a></li>
            <li><a href="<?php echo $base_path; ?>fees/my_history.php"><i class="bi bi-currency-dollar"></i> <span>Fee History</span></a></li>
            <li><a href="<?php echo $base_path; ?>timetable/my_schedule.php"><i class="bi bi-table"></i> <span>My Timetable</span></a></li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'Parent'): ?>
            <li class="menu-header"><span>Parent Portal</span></li>
            <li><a href="<?php echo $base_path; ?>home.php"><i class="bi bi-house-door-fill"></i> <span>My Children</span></a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
</aside>