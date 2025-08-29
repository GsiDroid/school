<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/functions.php';
$conn = get_db_connection();

$pageTitle = "Edit Student";
$currentPage = "students";

$student_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$student_id) {
    header("Location: index.php");
    exit;
}

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: index.php");
    exit;
}

// Get all classes for dropdown
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $admission_no = filter_input(INPUT_POST, 'admission_no', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $date_of_birth = filter_input(INPUT_POST, 'date_of_birth');
    $current_class_id = filter_input(INPUT_POST, 'current_class_id', FILTER_VALIDATE_INT);
    $admission_date = filter_input(INPUT_POST, 'admission_date');

    // Handle file upload
    $profile_image = $student['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../uploads/students/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $profile_image = basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $profile_image;
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
    }

    try {
        $stmt = $conn->prepare("UPDATE students SET first_name = ?, last_name = ?, admission_no = ?, gender = ?, date_of_birth = ?, current_class_id = ?, admission_date = ?, profile_image = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $admission_no, $gender, $date_of_birth, $current_class_id, $admission_date, $profile_image, $student_id]);

        set_message('success', 'Student updated successfully.');
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Failed to update student. Please try again.');
        error_log("Student Update Error: " . $e->getMessage());
    }
}

include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Student Management</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-edit me-1"></i>
            Edit Student Information
        </div>
        <div class="card-body">
            <form action="edit.php?id=<?php echo $student_id; ?>" method="POST" enctype="multipart/form-data">
                <!-- Student Details -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="admission_no" class="form-label">Admission No</label>
                        <input type="text" class="form-control" id="admission_no" name="admission_no" value="<?php echo htmlspecialchars($student['admission_no']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="male" <?php echo ($student['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($student['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($student['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo $student['date_of_birth']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="current_class_id" class="form-label">Class</label>
                        <select class="form-select" id="current_class_id" name="current_class_id" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($student['current_class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="admission_date" class="form-label">Admission Date</label>
                        <input type="date" class="form-control" id="admission_date" name="admission_date" value="<?php echo $student['admission_date']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="profile_image" class="form-label">Profile Image</label>
                        <input class="form-control" type="file" id="profile_image" name="profile_image">
                        <?php if ($student['profile_image']): ?>
                            <div class="mt-2">
                                <img src="../uploads/students/<?php echo $student['profile_image']; ?>" alt="Profile Image" width="100">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update Student</button>
            </form>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
