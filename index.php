<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Note: Using a generic password for demonstration. Replace with password_verify().
        // The password hash '$2y$10$E.qLp3b7V6aC.1xY.b.d.e/U3f5j3.Z.cW/gY.h.i.j.k.l.m.n' is for 'password123'
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['name'];
            
            log_activity($pdo, $user['id'], "User logged in");

            header("Location: home.php");
            exit();
        } else {
            $error_message = "Invalid username or password.";
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <h2>School Management System</h2>
        <form action="index.php" method="POST">
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="login-footer">
            <p>Default Logins:</p>
            <ul>
                <li><b>Admin:</b> admin / password123</li>
                <li><b>Teacher:</b> teacher1 / password123</li>
                <li><b>Cashier:</b> cashier1 / password123</li>
                <li><b>Student:</b> student1 / password123</li>
            </ul>
        </div>
    </div>
</body>
</html>ml>