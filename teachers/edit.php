<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: index.php");
    exit();
}

// Fetch teacher data from both tables
$stmt = $pdo->prepare("SELECT u.*, t.designation, t.date_of_joining FROM users u LEFT JOIN teachers t ON u.id = t.user_id WHERE u.id = ? AND u.role = 'Teacher'");
$stmt->execute([$user_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: index.php");
    exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $designation = trim($_POST['designation']);
    $date_of_joining = $_POST['date_of_joining'];
    $password = $_POST['password'];

    if (empty($full_name)) $errors[] = "Full name is required.";
    if (!$email) $errors[] = "A valid email is required.";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update users table
            $user_sql = "UPDATE users SET full_name = ?, email = ?, is_active = ?";
            $params = [$full_name, $email, $_POST['is_active']];
            if (!empty($password)) {
                $user_sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $user_sql .= " WHERE id = ?";
            $params[] = $user_id;
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->execute($params);

            // Update teachers table
            $teacher_sql = "UPDATE teachers SET designation = ?, date_of_joining = ? WHERE user_id = ?";
            $teacher_stmt = $pdo->prepare($teacher_sql);
            $teacher_stmt->execute([$designation, $date_of_joining, $user_id]);

            $pdo->commit();
            header("Location: index.php?success=Teacher updated successfully.");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    $teacher = array_merge($teacher, $_POST);
}
?>

<div class="content-header">
    <h1>Edit Teacher</h1>
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

        <form action="edit.php?id=<?php echo $user_id; ?>" method="POST" class="form-grid">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($teacher['full_name']); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($teacher['email']); ?>">
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($teacher['username']); ?>" disabled>
                <small>Username cannot be changed.</small>
            </div>
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password">
                <small>Leave blank to keep current password.</small>
            </div>
            <div class="form-group">
                <label for="designation">Designation</label>
                <input type="text" id="designation" name="designation" value="<?php echo htmlspecialchars($teacher['designation']); ?>">
            </div>
            <div class="form-group">
                <label for="date_of_joining">Date of Joining</label>
                <input type="date" id="date_of_joining" name="date_of_joining" value="<?php echo htmlspecialchars($teacher['date_of_joining']); ?>">
            </div>
            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1" <?php echo ($teacher['is_active'] == 1) ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo ($teacher['is_active'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group form-group-full">
                <button type="submit" class="btn">Update Teacher</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
