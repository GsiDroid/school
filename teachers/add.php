<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // User account details
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    // Teacher specific details
    $designation = trim($_POST['designation']);
    $date_of_joining = $_POST['date_of_joining'];

    if (empty($username)) $errors[] = "Username is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (!$email) $errors[] = "A valid email is required.";

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // 1. Create the user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_sql = "INSERT INTO users (username, password, role, email, full_name, is_active) VALUES (?, ?, 'Teacher', ?, ?, ?)";
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->execute([$username, $hashed_password, $email, $full_name, $_POST['is_active']]);
            $user_id = $pdo->lastInsertId();

            // 2. Create the teacher profile
            $teacher_sql = "INSERT INTO teachers (user_id, designation, date_of_joining) VALUES (?, ?, ?)";
            $teacher_stmt = $pdo->prepare($teacher_sql);
            $teacher_stmt->execute([$user_id, $designation, $date_of_joining]);
            
            $pdo->commit();
            header("Location: index.php?success=Teacher added successfully.");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $errors[] = "Username or email already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="content-header">
    <h1>Add New Teacher</h1>
    <a href="index.php" class="btn">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="add.php" method="POST" class="form-grid">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" required value="<?php echo $_POST['full_name'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required value="<?php echo $_POST['email'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required value="<?php echo $_POST['username'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="designation">Designation</label>
                <input type="text" id="designation" name="designation" value="<?php echo $_POST['designation'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="date_of_joining">Date of Joining</label>
                <input type="date" id="date_of_joining" name="date_of_joining" value="<?php echo $_POST['date_of_joining'] ?? date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="form-group form-group-full">
                <button type="submit" class="btn">Add Teacher</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
