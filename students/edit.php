<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$student_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$student_id) {
    header("Location: index.php");
    exit();
}

// Fetch student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: index.php");
    exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $admission_no = trim($_POST['admission_no']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);

    if (empty($admission_no)) $errors[] = "Admission number is required.";
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (!$class_id) $errors[] = "A valid class must be selected.";

    // Photo upload handling
    $photo_name = $student['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = __DIR__ . '/../uploads/students/';
        // Optionally, delete old photo
        if ($photo_name && file_exists($upload_dir . $photo_name)) {
            unlink($upload_dir . $photo_name);
        }
        
        $photo_name = uniqid() . '-' . basename($_FILES['photo']['name']);
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
            $errors[] = "Failed to upload new photo.";
            $photo_name = $student['photo']; // Revert to old photo on failure
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE students SET admission_no = ?, first_name = ?, last_name = ?, father_name = ?, mother_name = ?, date_of_birth = ?, gender = ?, mobile_no = ?, parent_mobile = ?, photo = ?, class_id = ?, academic_year = ?, is_active = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([
                $admission_no, $first_name, $last_name, 
                $_POST['father_name'], $_POST['mother_name'], 
                $_POST['date_of_birth'], $_POST['gender'], 
                $_POST['mobile_no'], $_POST['parent_mobile'], 
                $photo_name, $class_id, 
                $_POST['academic_year'], $_POST['is_active'],
                $student_id
            ]);
            header("Location: index.php?success=Student updated successfully.");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Admission number already exists for another student.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
    // On error, merge POST data with student data to show submitted values
    $student = array_merge($student, $_POST);
}

// Fetch classes for dropdown
$classes_stmt = $pdo->query("SELECT id, class_name, academic_year FROM classes ORDER BY academic_year DESC, class_name ASC");
$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="content-header">
    <h1>Edit Student</h1>
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

        <form action="edit.php?id=<?php echo $student_id; ?>" method="POST" enctype="multipart/form-data" class="form-grid">
            <!-- Personal Details -->
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($student['first_name']); ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($student['last_name']); ?>">
            </div>
            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth']); ?>">
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <!-- Academic Details -->
            <div class="form-group">
                <label for="admission_no">Admission No *</label>
                <input type="text" id="admission_no" name="admission_no" required value="<?php echo htmlspecialchars($student['admission_no']); ?>">
            </div>
            <div class="form-group">
                <label for="class_id">Class *</label>
                <select id="class_id" name="class_id" required>
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo ($student['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['academic_year'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="academic_year">Academic Year</label>
                <input type="text" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($student['academic_year']); ?>">
            </div>

            <!-- Contact Details -->
            <div class="form-group">
                <label for="mobile_no">Student Mobile</label>
                <input type="text" id="mobile_no" name="mobile_no" value="<?php echo htmlspecialchars($student['mobile_no']); ?>">
            </div>
            <div class="form-group">
                <label for="parent_mobile">Parent Mobile</label>
                <input type="text" id="parent_mobile" name="parent_mobile" value="<?php echo htmlspecialchars($student['parent_mobile']); ?>">
            </div>

            <!-- Parent Details -->
            <div class="form-group">
                <label for="father_name">Father's Name</label>
                <input type="text" id="father_name" name="father_name" value="<?php echo htmlspecialchars($student['father_name']); ?>">
            </div>
            <div class="form-group">
                <label for="mother_name">Mother's Name</label>
                <input type="text" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($student['mother_name']); ?>">
            </div>

            <!-- Status and Photo -->
            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1" <?php echo ($student['is_active'] == 1) ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo ($student['is_active'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="photo">Update Student Photo</label>
                <input type="file" id="photo" name="photo">
                <?php if ($student['photo']): ?>
                    <p>Current: <a href="<?php echo $base_path; ?>uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" target="_blank">View Photo</a></p>
                <?php endif; ?>
            </div>

            <div class="form-group form-group-full">
                <button type="submit" class="btn">Update Student</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>