<?php
/**
 * School Management System - Installation Script
 * 
 * This script will:
 * 1. Check system requirements
 * 2. Create database and tables
 * 3. Set up default data
 * 4. Create necessary directories
 * 5. Set proper permissions
 */

// Prevent direct access if already installed
if (file_exists('config/installed.lock')) {
    die('School Management System is already installed. Remove config/installed.lock to reinstall.');
}

// Start output buffering
ob_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .install-container { max-width: 800px; margin: 50px auto; }
        .step { margin-bottom: 30px; }
        .step-header { background: #4e73df; color: white; padding: 15px; border-radius: 5px; }
        .step-content { background: white; padding: 20px; border-radius: 0 0 5px 5px; border: 1px solid #e3e6f0; }
        .success { color: #1cc88a; }
        .error { color: #e74a3b; }
        .warning { color: #f6c23e; }
        .progress { height: 25px; }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-graduation-cap text-primary"></i> School Management System</h1>
            <h3>Installation Wizard</h3>
        </div>

        <div class="progress mb-4">
            <div class="progress-bar" role="progressbar" style="width: 0%" id="progress-bar"></div>
        </div>

        <div id="installation-steps">
            <!-- Step 1: System Requirements Check -->
            <div class="step" id="step1">
                <div class="step-header">
                    <h5><i class="fas fa-check-circle"></i> Step 1: System Requirements Check</h5>
                </div>
                <div class="step-content" id="step1-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Checking...</span>
                        </div>
                        <p>Checking system requirements...</p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Database Setup -->
            <div class="step" id="step2" style="display: none;">
                <div class="step-header">
                    <h5><i class="fas fa-database"></i> Step 2: Database Configuration</h5>
                </div>
                <div class="step-content" id="step2-content">
                    <form id="db-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" value="school_management" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_user" class="form-label">Database Username</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="db_pass" class="form-label">Database Password</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" value="">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Test Connection & Continue</button>
                    </form>
                </div>
            </div>

            <!-- Step 3: Admin Account Setup -->
            <div class="step" id="step3" style="display: none;">
                <div class="step-header">
                    <h5><i class="fas fa-user-shield"></i> Step 3: Administrator Account</h5>
                </div>
                <div class="step-content" id="step3-content">
                    <form id="admin-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="admin_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" value="System Administrator" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="admin_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@schoolms.com" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="admin_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="admin_confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="admin_confirm_password" name="admin_confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Admin Account</button>
                    </form>
                </div>
            </div>

            <!-- Step 4: Installation Progress -->
            <div class="step" id="step4" style="display: none;">
                <div class="step-header">
                    <h5><i class="fas fa-cogs"></i> Step 4: Installation Progress</h5>
                </div>
                <div class="step-content" id="step4-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Installing...</span>
                        </div>
                        <p>Installing School Management System...</p>
                        <div id="install-log"></div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Installation Complete -->
            <div class="step" id="step5" style="display: none;">
                <div class="step-header">
                    <h5><i class="fas fa-check-circle"></i> Step 5: Installation Complete</h5>
                </div>
                <div class="step-content" id="step5-content">
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h3 class="mt-3">Installation Successful!</h3>
                        <p class="lead">School Management System has been installed successfully.</p>
                        <div class="alert alert-info">
                            <strong>Default Login Credentials:</strong><br>
                            Email: <span id="admin-email-display"></span><br>
                            Password: <span id="admin-password-display"></span>
                        </div>
                        <a href="login.php" class="btn btn-success btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Go to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 5;

        // Update progress bar
        function updateProgress(step) {
            const progress = (step / totalSteps) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';
            document.getElementById('progress-bar').textContent = Math.round(progress) + '%';
        }

        // Show step
        function showStep(step) {
            document.querySelectorAll('.step').forEach(s => s.style.display = 'none');
            document.getElementById('step' + step).style.display = 'block';
            updateProgress(step);
        }

        // Check system requirements
        async function checkRequirements() {
            const response = await fetch('install_ajax.php?action=check_requirements');
            const result = await response.json();
            
            let html = '<ul class="list-group list-group-flush">';
            let allPassed = true;
            
            result.checks.forEach(check => {
                const status = check.passed ? 'success' : 'error';
                const icon = check.passed ? 'fa-check-circle' : 'fa-times-circle';
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${check.name} <span class="${status}"><i class="fas ${icon}"></i> ${check.message}</span>
                </li>`;
                if (!check.passed) allPassed = false;
            });
            
            html += '</ul>';
            
            if (allPassed) {
                html += '<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> All requirements met!</div>';
                document.getElementById('step1-content').innerHTML = html;
                setTimeout(() => showStep(2), 1000);
            } else {
                html += '<div class="alert alert-danger mt-3"><i class="fas fa-exclamation-triangle"></i> Please fix the issues above before continuing.</div>';
                document.getElementById('step1-content').innerHTML = html;
            }
        }

        // Database connection test
        document.getElementById('db-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'test_db');
            
            const response = await fetch('install_ajax.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('step2-content').innerHTML = 
                    '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Database connection successful!</div>';
                setTimeout(() => showStep(3), 1000);
            } else {
                document.getElementById('step2-content').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + result.message + '</div>' +
                    document.getElementById('db-form').outerHTML;
            }
        });

        // Admin account creation
        document.getElementById('admin-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_admin');
            
            // Add database config
            const dbForm = document.getElementById('db-form');
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);
            
            const response = await fetch('install_ajax.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('step3-content').innerHTML = 
                    '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Admin account created successfully!</div>';
                setTimeout(() => showStep(4), 1000);
                startInstallation();
            } else {
                document.getElementById('step3-content').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + result.message + '</div>' +
                    document.getElementById('admin-form').outerHTML;
            }
        });

        // Start installation
        async function startInstallation() {
            const formData = new FormData();
            formData.append('action', 'install');
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);
            formData.append('admin_name', document.getElementById('admin_name').value);
            formData.append('admin_email', document.getElementById('admin_email').value);
            formData.append('admin_password', document.getElementById('admin_password').value);
            
            const response = await fetch('install_ajax.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('admin-email-display').textContent = document.getElementById('admin_email').value;
                document.getElementById('admin-password-display').textContent = document.getElementById('admin_password').value;
                showStep(5);
            } else {
                document.getElementById('step4-content').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Installation failed: ' + result.message + '</div>';
            }
        }

        // Start installation process
        document.addEventListener('DOMContentLoaded', function() {
            checkRequirements();
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>