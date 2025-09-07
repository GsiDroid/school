<?php
require_once __DIR__ . '/../includes/header.php';

// Allow Admins, and the student themselves to view the profile
$student_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$can_view = false;

if ($_SESSION['role'] === 'Admin') {
    $can_view = true;
} elseif ($_SESSION['role'] === 'Student') {
    // Check if the logged-in student is viewing their own profile
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($current_student && $current_student['id'] == $student_id) {
        $can_view = true;
    }
}

if (!$can_view || !$student_id) {
    echo "<div class='alert alert-danger'>Access Denied or Invalid Student ID.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$sql = "SELECT s.*, c.class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "<div class='alert alert-danger'>Student not found.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}
?>

<div class="content-header">
    <h1>Student Profile</h1>
    <a href="index.php" class="btn">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="profile-container">
            <div class="profile-header">
                <img src="<?php echo $base_path; ?>uploads/students/<?php echo htmlspecialchars($student['photo'] ?: 'default.png'); ?>" alt="Student Photo" class="profile-photo">
                <div class="profile-name">
                    <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                    <p>Admission No: <?php echo htmlspecialchars($student['admission_no']); ?></p>
                    <span class="status-<?php echo $student['is_active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
            </div>
            <div class="profile-details">
                <h3>Personal Information</h3>
                <table class="profile-table">
                    <tr>
                        <th>Date of Birth</th>
                        <td><?php echo htmlspecialchars($student['date_of_birth']); ?></td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                    </tr>
                </table>

                <h3>Academic Information</h3>
                <table class="profile-table">
                    <tr>
                        <th>Class</th>
                        <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Academic Year</th>
                        <td><?php echo htmlspecialchars($student['academic_year']); ?></td>
                    </tr>
                </table>

                <h3>Contact Information</h3>
                <table class="profile-table">
                    <tr>
                        <th>Student Mobile</th>
                        <td><?php echo htmlspecialchars($student['mobile_no']); ?></td>
                    </tr>
                    <tr>
                        <th>Parent Mobile</th>
                        <td><?php echo htmlspecialchars($student['parent_mobile']); ?></td>
                    </tr>
                </table>

                <h3>Parent Information</h3>
                <table class="profile-table">
                    <tr>
                        <th>Father's Name</th>
                        <td><?php echo htmlspecialchars($student['father_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Mother's Name</th>
                        <td><?php echo htmlspecialchars($student['mother_name']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>