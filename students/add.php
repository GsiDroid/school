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
    $pdo->beginTransaction();
    try {
        // Student details
        $admission_no = trim($_POST['admission_no']);
        $first_name = trim($_POST['first_name']);
        // ... (all other student fields)

        $parent_id = null;
        // Check if creating a new parent
        if (isset($_POST['create_student_with_parent'])) {
            $parent_full_name = trim($_POST['parent_full_name']);
            $parent_email = filter_input(INPUT_POST, 'parent_email', FILTER_VALIDATE_EMAIL);
            $parent_username = trim($_POST['parent_username']);
            $parent_password = $_POST['parent_password'];

            if ($parent_full_name && $parent_email && $parent_username && $parent_password) {
                $parent_hashed_password = password_hash($parent_password, PASSWORD_DEFAULT);
                $parent_stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name, is_active) VALUES (?, ?, 'Parent', ?, ?, 1)");
                $parent_stmt->execute([$parent_username, $parent_hashed_password, $parent_email, $parent_full_name]);
                $parent_id = $pdo->lastInsertId();
            } else {
                throw new Exception("All parent fields are required to create a new parent account.");
            }
        }

        // Create student
        $sql = "INSERT INTO students (admission_no, first_name, last_name, class_id, academic_year, is_active, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?)"; // Simplified for brevity
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $admission_no, $first_name, $_POST['last_name'], 
            $_POST['class_id'], $_POST['academic_year'], $_POST['is_active'],
            $parent_id
        ]);
        
        $pdo->commit();
        header("Location: index.php?success=Student added successfully.");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = $e->getMessage();
    }

}

// Fetch classes for dropdown
$classes_stmt = $pdo->query("SELECT id, class_name, academic_year FROM classes ORDER BY academic_year DESC, class_name ASC");
$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="content-header">
    <h1>Add New Student</h1>
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

        <form action="add.php" method="POST" enctype="multipart/form-data" class="form-grid">
            <!-- Personal Details -->
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required value="<?php echo $_POST['first_name'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required value="<?php echo $_POST['last_name'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $_POST['date_of_birth'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="Male" <?php echo (($_POST['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo (($_POST['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo (($_POST['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <!-- Academic Details -->
            <div class="form-group">
                <label for="admission_no">Admission No *</label>
                <input type="text" id="admission_no" name="admission_no" required value="<?php echo $_POST['admission_no'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="class_id">Class *</label>
                <select id="class_id" name="class_id" required>
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo (($_POST['class_id'] ?? '') == $class['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['academic_year'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="academic_year">Academic Year</label>
                <input type="text" id="academic_year" name="academic_year" value="<?php echo $_POST['academic_year'] ?? '2025-2026'; ?>">
            </div>

            <!-- Contact Details -->
            <div class="form-group">
                <label for="mobile_no">Student Mobile</label>
                <input type="text" id="mobile_no" name="mobile_no" value="<?php echo $_POST['mobile_no'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="parent_mobile">Parent Mobile</label>
                <input type="text" id="parent_mobile" name="parent_mobile" value="<?php echo $_POST['parent_mobile'] ?? ''; ?>">
            </div>

            <!-- Parent Details -->
            <div class="form-group">
                <label for="father_name">Father's Name</label>
                <input type="text" id="father_name" name="father_name" value="<?php echo $_POST['father_name'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="mother_name">Mother's Name</label>
                <input type="text" id="mother_name" name="mother_name" value="<?php echo $_POST['mother_name'] ?? ''; ?>">
            </div>

            <!-- Status and Photo -->
            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="photo">Student Photo</label>
                <input type="file" id="photo" name="photo">
            </div>

            <div class="form-group form-group-full">
                <button type="submit" class="btn">Add Student</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>               </select>
            </div>
            <div class="form-group">
                <label for="photo">Student Photo</label>
                <input type="file" id="photo" name="photo">
            </div>

            <div class="form-group form-group-full">
                <button type="submit" class="btn">Add Student</button>
            </div>
        </form>

        <hr>

        <h3>Parent Account Details</h3>
        <p>You can create a new parent account or assign an existing one.</p>
        <form action="add.php" method="POST" enctype="multipart/form-data" class="form-grid">
            <!-- Hidden fields from student form to carry over -->

            <div class="form-group">
                <label for="parent_full_name">Parent's Full Name</label>
                <input type="text" id="parent_full_name" name="parent_full_name">
            </div>
            <div class="form-group">
                <label for="parent_email">Parent's Email</label>
                <input type="email" id="parent_email" name="parent_email">
            </div>
            <div class="form-group">
                <label for="parent_username">Parent's Username</label>
                <input type="text" id="parent_username" name="parent_username">
            </div>
            <div class="form-group">
                <label for="parent_password">Parent's Password</label>
                <input type="password" id="parent_password" name="parent_password">
            </div>

            <div class="form-group form-group-full">
                <button type="submit" name="create_student_with_parent" class="btn">Create Student & Parent Account</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>