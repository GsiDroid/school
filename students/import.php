<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/functions.php';
$conn = get_db_connection();

$pageTitle = "Import Students";
$currentPage = "students";

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        $imported_count = 0;
        $error_count = 0;

        // Skip the header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Assuming CSV format: admission_no, first_name, last_name, gender, date_of_birth, class_name, admission_date
            if (count($data) == 7) {
                $admission_no = $data[0];
                $first_name = $data[1];
                $last_name = $data[2];
                $gender = $data[3];
                $date_of_birth = $data[4];
                $class_name = $data[5];
                $admission_date = $data[6];

                // Find class id from class name
                $stmt = $conn->prepare("SELECT id FROM classes WHERE name = ?");
                $stmt->execute([$class_name]);
                $class = $stmt->fetch(PDO::FETCH_ASSOC);
                $class_id = $class ? $class['id'] : null;

                try {
                    $stmt = $conn->prepare("INSERT INTO students (admission_no, first_name, last_name, gender, date_of_birth, current_class_id, admission_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$admission_no, $first_name, $last_name, $gender, $date_of_birth, $class_id, $admission_date]);
                    $imported_count++;
                } catch (PDOException $e) {
                    $error_count++;
                    error_log("Student Import Error: " . $e->getMessage());
                }
            } else {
                $error_count++;
            }
        }

        fclose($handle);

        $message = "<div class='alert alert-success'>Import completed. $imported_count students imported, $error_count errors.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Please upload a valid CSV file.</div>";
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
            <i class="fas fa-file-import me-1"></i>
            Import Students from CSV
        </div>
        <div class="card-body">
            <?php echo $message; ?>
            <form action="import.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="csv_file" class="form-label">Select CSV File</label>
                    <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-primary">Import Students</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
            <hr>
            <h5>CSV File Format</h5>
            <p>The CSV file should have the following columns in this order:</p>
            <code>admission_no, first_name, last_name, gender, date_of_birth, class_name, admission_date</code>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
