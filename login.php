<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Include common functions
require_once 'includes/functions.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Initialize variables
$email = $password = '';
$errors = [];

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password');
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no validation errors, proceed with login
    if (empty($errors)) {
        try {
            // Prepare SQL statement to fetch user by email
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");

            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Verify user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Generate CSRF token for security
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                // Log successful login
                $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->execute([
                    $user['id'],
                    'login',
                    'User logged in successfully',
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                // Redirect to dashboard
                header("Location: index.php");
                exit;
            } else {
                $errors['login'] = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $errors['login'] = 'An error occurred during login. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    
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
        
        .login-container {
            max-width: 400px;
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
        
        .register-link {
            color: #4e73df;
            text-decoration: none;
        }
        
        .register-link:hover {
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

    <div class="container login-container">
        <div class="card">
            <div class="card-header">
                <img src="assets/img/logo.png" alt="School Logo" class="logo">
                <h4>School Management System</h4>
                <p class="mb-0">Please login to your account</p>
                <a href="home.php" class="btn btn-sm btn-outline-light mt-2"><i class="fas fa-home"></i> Visit Public Home Page</a>
            </div>
            <div class="card-body p-4">
                <?php if (isset($errors['login'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($errors['login']); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
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
                    
                    <div class="mb-4">
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
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <div class="text-muted mb-2">Don't have an account?</div>
                <a href="register.php" class="register-link">Register Now</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
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
    </script>
</body>
</html>
