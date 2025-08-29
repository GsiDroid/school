<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/functions.php';
$conn = get_db_connection();

$pageTitle = "View Student";
$currentPage = "students";

$student_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$student_id) {
    header("Location: index.php");
    exit;
}

// Fetch student data
$stmt = $conn->prepare("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.current_class_id = c.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: index.php");
    exit;
}

// Fetch guardian data
$stmt = $conn->prepare("SELECT * FROM student_guardians WHERE student_id = ?");
$stmt->execute([$student_id]);
$guardians = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Student Management</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user me-1"></i>
            Student Details
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <?php if ($student['profile_image']): ?>
                        <img src="../uploads/students/<?php echo $student['profile_image']; ?>" alt="Profile Image" class="img-fluid rounded">
                    <?php else: ?>
                        <img src="../assets/img/default-student.svg" alt="Default Profile Image" class="img-fluid rounded">
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
                    <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></p>
                    <p><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] ?? 'Not Assigned'); ?></p>
                    <p><strong>Gender:</strong> <?php echo ucfirst(htmlspecialchars($student['gender'])); ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo date('d M, Y', strtotime($student['date_of_birth'])); ?></p>
                    <p><strong>Admission Date:</strong> <?php echo date('d M, Y', strtotime($student['admission_date'])); ?></p>
                    <p><strong>Status:</strong> <span class="badge bg-success"><?php echo ucfirst(htmlspecialchars($student['status'])); ?></span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Guardian Information
        </div>
        <div class="card-body">
            <?php if (count($guardians) > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Relation</th>
                            <th>Phone</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guardians as $guardian): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($guardian['name']); ?></td>
                                <td><?php echo htmlspecialchars($guardian['relation']); ?></td>
                                <td><?php echo htmlspecialchars($guardian['phone']); ?></td>
                                <td><?php echo htmlspecialchars($guardian['email']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No guardian information found.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
include_once '../includes/footer.php';
?>
