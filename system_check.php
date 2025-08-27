<?php
/**
 * System Check and Fix Script
 * 
 * This script performs a comprehensive check of the School Management System
 * and attempts to fix any issues found.
 */

// Prevent direct access in production
if (file_exists('config/installed.lock') && !isset($_GET['force'])) {
    die('System is already installed. Add ?force=1 to run system check.');
}

// Start output buffering
ob_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .check-container { max-width: 900px; margin: 30px auto; }
        .check-item { margin-bottom: 15px; }
        .check-header { background: #4e73df; color: white; padding: 10px 15px; border-radius: 5px; }
        .check-content { background: white; padding: 15px; border-radius: 0 0 5px 5px; border: 1px solid #e3e6f0; }
        .success { color: #1cc88a; }
        .error { color: #e74a3b; }
        .warning { color: #f6c23e; }
        .info { color: #36b9cc; }
        .fix-btn { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container check-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-tools text-primary"></i> System Check & Fix</h1>
            <h4>School Management System</h4>
        </div>

        <div id="check-results">
            <!-- System Requirements Check -->
            <div class="check-item" id="requirements-check">
                <div class="check-header">
                    <h5><i class="fas fa-check-circle"></i> System Requirements</h5>
                </div>
                <div class="check-content" id="requirements-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p>Checking system requirements...</p>
                    </div>
                </div>
            </div>

            <!-- Database Check -->
            <div class="check-item" id="database-check">
                <div class="check-header">
                    <h5><i class="fas fa-database"></i> Database Configuration</h5>
                </div>
                <div class="check-content" id="database-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p>Checking database configuration...</p>
                    </div>
                </div>
            </div>

            <!-- File Structure Check -->
            <div class="check-item" id="files-check">
                <div class="check-header">
                    <h5><i class="fas fa-folder"></i> File Structure</h5>
                </div>
                <div class="check-content" id="files-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p>Checking file structure...</p>
                    </div>
                </div>
            </div>

            <!-- Permissions Check -->
            <div class="check-item" id="permissions-check">
                <div class="check-header">
                    <h5><i class="fas fa-lock"></i> File Permissions</h5>
                </div>
                <div class="check-content" id="permissions-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p>Checking file permissions...</p>
                    </div>
                </div>
            </div>

            <!-- Code Issues Check -->
            <div class="check-item" id="code-check">
                <div class="check-header">
                    <h5><i class="fas fa-code"></i> Code Issues</h5>
                </div>
                <div class="check-content" id="code-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p>Checking for code issues...</p>
                    </div>
                </div>
            </div>

            <!-- Security Check -->
            <div class="check-item" id="security-check">
                <div class="check-header">
                    <h5><i class="fas fa-shield-alt"></i> Security Check</h5>
                </div>
                <div class="check-content" id="security-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p>Checking security settings...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4" id="summary-section" style="display: none;">
            <div class="card">
                <div class="card-body">
                    <h5>System Check Summary</h5>
                    <div id="summary-content"></div>
                    <button class="btn btn-primary mt-3" onclick="fixAllIssues()">
                        <i class="fas fa-wrench"></i> Fix All Issues
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let checkResults = {
            requirements: { passed: 0, total: 0, issues: [] },
            database: { passed: 0, total: 0, issues: [] },
            files: { passed: 0, total: 0, issues: [] },
            permissions: { passed: 0, total: 0, issues: [] },
            code: { passed: 0, total: 0, issues: [] },
            security: { passed: 0, total: 0, issues: [] }
        };

        // Run all checks
        document.addEventListener('DOMContentLoaded', function() {
            checkRequirements();
            checkDatabase();
            checkFiles();
            checkPermissions();
            checkCode();
            checkSecurity();
        });

        async function checkRequirements() {
            const response = await fetch('system_check_ajax.php?action=check_requirements');
            const result = await response.json();
            
            let html = '<ul class="list-group list-group-flush">';
            let passed = 0;
            let total = result.checks.length;
            
            result.checks.forEach(check => {
                const status = check.passed ? 'success' : 'error';
                const icon = check.passed ? 'fa-check-circle' : 'fa-times-circle';
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${check.name} <span class="${status}"><i class="fas ${icon}"></i> ${check.message}</span>
                </li>`;
                if (check.passed) passed++;
            });
            
            html += '</ul>';
            
            if (passed === total) {
                html += '<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> All requirements met!</div>';
            } else {
                html += '<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle"></i> Some requirements need attention.</div>';
                html += '<button class="btn btn-warning fix-btn" onclick="fixRequirements()"><i class="fas fa-wrench"></i> Fix Requirements</button>';
            }
            
            document.getElementById('requirements-content').innerHTML = html;
            checkResults.requirements = { passed, total, issues: result.checks.filter(c => !c.passed) };
            updateSummary();
        }

        async function checkDatabase() {
            const response = await fetch('system_check_ajax.php?action=check_database');
            const result = await response.json();
            
            let html = '<ul class="list-group list-group-flush">';
            let passed = 0;
            let total = result.checks.length;
            
            result.checks.forEach(check => {
                const status = check.passed ? 'success' : 'error';
                const icon = check.passed ? 'fa-check-circle' : 'fa-times-circle';
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${check.name} <span class="${status}"><i class="fas ${icon}"></i> ${check.message}</span>
                </li>`;
                if (check.passed) passed++;
            });
            
            html += '</ul>';
            
            if (passed === total) {
                html += '<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> Database configuration is correct!</div>';
            } else {
                html += '<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle"></i> Database issues found.</div>';
                html += '<button class="btn btn-warning fix-btn" onclick="fixDatabase()"><i class="fas fa-wrench"></i> Fix Database</button>';
            }
            
            document.getElementById('database-content').innerHTML = html;
            checkResults.database = { passed, total, issues: result.checks.filter(c => !c.passed) };
            updateSummary();
        }

        async function checkFiles() {
            const response = await fetch('system_check_ajax.php?action=check_files');
            const result = await response.json();
            
            let html = '<ul class="list-group list-group-flush">';
            let passed = 0;
            let total = result.checks.length;
            
            result.checks.forEach(check => {
                const status = check.passed ? 'success' : 'error';
                const icon = check.passed ? 'fa-check-circle' : 'fa-times-circle';
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${check.name} <span class="${status}"><i class="fas ${icon}"></i> ${check.message}</span>
                </li>`;
                if (check.passed) passed++;
            });
            
            html += '</ul>';
            
            if (passed === total) {
                html += '<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> File structure is correct!</div>';
            } else {
                html += '<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle"></i> File structure issues found.</div>';
                html += '<button class="btn btn-warning fix-btn" onclick="fixFiles()"><i class="fas fa-wrench"></i> Fix Files</button>';
            }
            
            document.getElementById('files-content').innerHTML = html;
            checkResults.files = { passed, total, issues: result.checks.filter(c => !c.passed) };
            updateSummary();
        }

        async function checkPermissions() {
            const response = await fetch('system_check_ajax.php?action=check_permissions');
            const result = await response.json();
            
            let html = '<ul class="list-group list-group-flush">';
            let passed = 0;
            let total = result.checks.length;
            
            result.checks.forEach(check => {
                const status = check.passed ? 'success' : 'error';
                const icon = check.passed ? 'fa-check-circle' : 'fa-times-circle';
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${check.name} <span class="${status}"><i class="fas ${icon}"></i> ${check.message}</span>
                </li>`;
                if (check.passed) passed++;
            });
            
            html += '</ul>';
            
            if (passed === total) {
                html += '<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> All permissions are correct!</div>';
            } else {
                html += '<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle"></i> Permission issues found.</div>';
                html += '<button class="btn btn-warning fix-btn" onclick="fixPermissions()"><i class="fas fa-wrench"></i> Fix Permissions</button>';
            }
            
            document.getElementById('permissions-content').innerHTML = html;
            checkResults.permissions = { passed, total, issues: result.checks.filter(c => !c.passed) };
            updateSummary();
        }

        async function checkCode() {
            const response = await fetch('system_check_ajax.php?action=check_code');
            const result = await response.json();
            
            let html = '<ul class="list-group list-group-flush">';
            let passed = 0;
            let total = result.checks.length;
            
            result.checks.forEach(check => {
                const status = check.passed ? 'success' : 'error';
                const icon = check.passed ? 'fa-check-circle' : 'fa-times-circle';
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${check.name} <span class="${status}"><i class="fas ${icon}"></i> ${check.message}</span>
                </li>`;
                if (check.passed) passed++;
            });
            
            html += '</ul>';
            
            if (passed === total) {
                html += '<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> No code issues found!</div>';
            } else {
                html += '<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle"></i> Code issues found.</div>';
                html += '<button class="btn btn-warning fix-btn" onclick="fixCode()"><i class="fas fa-wrench"></i> Fix Code</button>';
            }
            
            document.getElementById('code-content').innerHTML = html;
            checkResults.code = { passed, total, issues: result.checks.filter(c => !c.passed) };
            updateSummary();
        }

        async function checkSecurity() {
            const response = await fetch('system_check_ajax.php?action=check_security');
            const result = await response.json();
            
            let html = '<ul class="list-group list-group-flush">';
            let passed = 0;
            let total = result.checks.length;
            
            result.checks.forEach(check => {
                const status = check.passed ? 'success' : 'error';
                const icon = check.passed ? 'fa-check-circle' : 'fa-times-circle';
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${check.name} <span class="${status}"><i class="fas ${icon}"></i> ${check.message}</span>
                </li>`;
                if (check.passed) passed++;
            });
            
            html += '</ul>';
            
            if (passed === total) {
                html += '<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> Security settings are correct!</div>';
            } else {
                html += '<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle"></i> Security issues found.</div>';
                html += '<button class="btn btn-warning fix-btn" onclick="fixSecurity()"><i class="fas fa-wrench"></i> Fix Security</button>';
            }
            
            document.getElementById('security-content').innerHTML = html;
            checkResults.security = { passed, total, issues: result.checks.filter(c => !c.passed) };
            updateSummary();
        }

        function updateSummary() {
            const totalChecks = Object.values(checkResults).reduce((sum, cat) => sum + cat.total, 0);
            const totalPassed = Object.values(checkResults).reduce((sum, cat) => sum + cat.passed, 0);
            const totalIssues = totalChecks - totalPassed;
            
            if (totalChecks > 0) {
                const percentage = Math.round((totalPassed / totalChecks) * 100);
                let statusClass = 'success';
                let statusIcon = 'fa-check-circle';
                
                if (percentage < 100) {
                    statusClass = percentage >= 80 ? 'warning' : 'error';
                    statusIcon = percentage >= 80 ? 'fa-exclamation-triangle' : 'fa-times-circle';
                }
                
                document.getElementById('summary-content').innerHTML = `
                    <div class="alert alert-${statusClass}">
                        <i class="fas ${statusIcon}"></i> 
                        ${totalPassed}/${totalChecks} checks passed (${percentage}%)
                    </div>
                    ${totalIssues > 0 ? `<p><strong>Issues found:</strong> ${totalIssues}</p>` : ''}
                `;
                document.getElementById('summary-section').style.display = 'block';
            }
        }

        async function fixAllIssues() {
            const response = await fetch('system_check_ajax.php?action=fix_all');
            const result = await response.json();
            
            if (result.success) {
                alert('All issues have been fixed! Please refresh the page to see the updated results.');
                location.reload();
            } else {
                alert('Error fixing issues: ' + result.message);
            }
        }

        async function fixRequirements() {
            const response = await fetch('system_check_ajax.php?action=fix_requirements');
            const result = await response.json();
            
            if (result.success) {
                alert('Requirements have been fixed! Please refresh the page.');
                location.reload();
            } else {
                alert('Error fixing requirements: ' + result.message);
            }
        }

        async function fixDatabase() {
            const response = await fetch('system_check_ajax.php?action=fix_database');
            const result = await response.json();
            
            if (result.success) {
                alert('Database issues have been fixed! Please refresh the page.');
                location.reload();
            } else {
                alert('Error fixing database: ' + result.message);
            }
        }

        async function fixFiles() {
            const response = await fetch('system_check_ajax.php?action=fix_files');
            const result = await response.json();
            
            if (result.success) {
                alert('File structure has been fixed! Please refresh the page.');
                location.reload();
            } else {
                alert('Error fixing files: ' + result.message);
            }
        }

        async function fixPermissions() {
            const response = await fetch('system_check_ajax.php?action=fix_permissions');
            const result = await response.json();
            
            if (result.success) {
                alert('Permissions have been fixed! Please refresh the page.');
                location.reload();
            } else {
                alert('Error fixing permissions: ' + result.message);
            }
        }

        async function fixCode() {
            const response = await fetch('system_check_ajax.php?action=fix_code');
            const result = await response.json();
            
            if (result.success) {
                alert('Code issues have been fixed! Please refresh the page.');
                location.reload();
            } else {
                alert('Error fixing code: ' + result.message);
            }
        }

        async function fixSecurity() {
            const response = await fetch('system_check_ajax.php?action=fix_security');
            const result = await response.json();
            
            if (result.success) {
                alert('Security issues have been fixed! Please refresh the page.');
                location.reload();
            } else {
                alert('Error fixing security: ' + result.message);
            }
        }
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
