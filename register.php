<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Include common functions
require_once 'includes/functions.php';

// Get database connection
$conn = get_db_connection();

// Initialize variables
$name = $email = $password = $confirm_password = $admin_passcode = '';
$errors = [];

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin_passcode = $_POST['admin_passcode'] ?? '';
    
    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Full name is required';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    }
    
    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Validate admin passcode
    if (empty($admin_passcode)) {
        $errors['admin_passcode'] = 'Admin passcode is required';
    } elseif ($admin_passcode !== '623264') { // Required admin passcode as specified
        $errors['admin_passcode'] = 'Invalid admin passcode';
    }
    
    // If no validation errors, proceed with registration
    if (empty($errors)) {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare SQL statement to insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $hashed_password, 'admin']);
            
            // Get the new user ID
            $user_id = $conn->lastInsertId();
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'admin';
            
            // Generate CSRF token for security
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Log successful registration
            $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([
                $user_id,
                'registration',
                'New admin user registered',
                $_SERVER['REMOTE_ADDR']
            ]);
            
            // Redirect to dashboard
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors['registration'] = 'An error occurred during registration. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - School Management System</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-container {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #4e73df;
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .logo {
            max-width: 80px;
            margin-bottom: 1rem;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .login-link {
            color: #4e73df;
            text-decoration: none;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        .theme-selector {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .theme-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin: 0 5px;
            cursor: pointer;
            border: 2px solid #fff;
        }
        
        .theme-blue { background-color: #4e73df; }
        .theme-green { background-color: #1cc88a; }
        .theme-dark { background-color: #5a5c69; }
        .theme-orange { background-color: #f6c23e; }
        .theme-purple { background-color: #6f42c1; }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
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

    <div class="container register-container">
        <div class="card">
            <div class="card-header">
                <img src="assets/img/logo.png" alt="School Logo" class="logo">
                <h4>School Management System</h4>
                <p class="mb-0">Admin Registration</p>
            </div>
            <div class="card-body p-4">
                <?php if (isset($errors['registration'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($errors['registration']); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['name']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['email']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                id="password" name="password" required>
                            <span class="input-group-text toggle-password" style="cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                            <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['password']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="password-strength-meter mt-2">
                            <div class="password-strength bg-danger" style="width: 0%;"></div>
                        </div>
                        <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['confirm_password']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="admin_passcode" class="form-label">Admin Passcode</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control <?php echo isset($errors['admin_passcode']) ? 'is-invalid' : ''; ?>" 
                                id="admin_passcode" name="admin_passcode" required>
                            <?php if (isset($errors['admin_passcode'])): ?>
                            <div class="invalid-feedback">
                                <?php echo htmlspecialchars($errors['admin_passcode']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">Required for admin registration.</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Register</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <div class="text-muted mb-2">Already have an account?</div>
                <a href="login.php" class="login-link">Login Here</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.querySelector('.password-strength');
            let strength = 0;
            
            // Check length
            if (password.length >= 8) strength += 25;
            
            // Check for lowercase letters
            if (password.match(/[a-z]+/)) strength += 25;
            
            // Check for uppercase letters
            if (password.match(/[A-Z]+/)) strength += 25;
            
            // Check for numbers or special characters
            if (password.match(/[0-9]+/) || password.match(/[^a-zA-Z0-9]+/)) strength += 25;
            
            // Update strength meter
            strengthMeter.style.width = strength + '%';
            
            // Update color based on strength
            if (strength <= 25) {
                strengthMeter.className = 'password-strength bg-danger';
            } else if (strength <= 50) {
                strengthMeter.className = 'password-strength bg-warning';
            } else if (strength <= 75) {
                strengthMeter.className = 'password-strength bg-info';
            } else {
                strengthMeter.className = 'password-strength bg-success';
            }
        });
        
        // Theme selector functionality
        document.querySelectorAll('.theme-btn').forEach(button => {
            button.addEventListener('click', function() {
                const theme = this.getAttribute('data-theme');
                let primaryColor, secondaryColor;
                
                switch(theme) {
                    case 'blue':
                        primaryColor = '#4e73df';
                        secondaryColor = '#2e59d9';
                        break;
                    case 'green':
                        primaryColor = '#1cc88a';
                        secondaryColor = '#169b6b';
                        break;
                    case 'dark':
                        primaryColor = '#5a5c69';
                        secondaryColor = '#484a54';
                        break;
                    case 'orange':
                        primaryColor = '#f6c23e';
                        secondaryColor = '#dda20a';
                        break;
                    case 'purple':
                        primaryColor = '#6f42c1';
                        secondaryColor = '#5a32a3';
                        break;
                }
                
                document.documentElement.style.setProperty('--primary-color', primaryColor);
                document.documentElement.style.setProperty('--secondary-color', secondaryColor);
                
                // Update header background
                document.querySelector('.card-header').style.backgroundColor = primaryColor;
                
                // Update button colors
                document.querySelector('.btn-primary').style.backgroundColor = primaryColor;
                document.querySelector('.btn-primary').style.borderColor = primaryColor;
                
                // Store theme preference
                localStorage.setItem('theme', theme);
            });
        });
        
        // Load saved theme preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.querySelector(`.theme-${savedTheme}`).click();
            }
        });
        
        // Check password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>