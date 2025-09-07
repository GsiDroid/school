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
    $class_name = trim($_POST['class_name']);
    $academic_year = trim($_POST['academic_year']);

    if (empty($class_name)) $errors[] = "Class name is required.";
    if (empty($academic_year)) $errors[] = "Academic year is required.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO classes (class_name, academic_year) VALUES (?, ?)");
            $stmt->execute([$class_name, $academic_year]);
            header("Location: index.php?success=Class added successfully.");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "A class with this name already exists for the specified academic year.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="content-header">
    <h1>Add New Class</h1>
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
                <label for="class_name">Class Name *</label>
                <input type="text" id="class_name" name="class_name" required value="<?php echo $_POST['class_name'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="academic_year">Academic Year *</label>
                <input type="text" id="academic_year" name="academic_year" required placeholder="e.g., 2025-2026" value="<?php echo $_POST['academic_year'] ?? '2025-2026'; ?>">
            </div>
            <div class="form-group form-group-full">
                <button type="submit" class="btn">Add Class</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
