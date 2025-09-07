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
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);

    if (empty($subject_name)) $errors[] = "Subject name is required.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code) VALUES (?, ?)");
            $stmt->execute([$subject_name, $subject_code]);
            header("Location: index.php?success=Subject added successfully.");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "A subject with this name already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="content-header">
    <h1>Add New Subject</h1>
    <a href="index.php" class="btn">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error):
                        ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="add.php" method="POST" class="form-grid">
            <div class="form-group">
                <label for="subject_name">Subject Name *</label>
                <input type="text" id="subject_name" name="subject_name" required value="<?php echo $_POST['subject_name'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="subject_code">Subject Code</label>
                <input type="text" id="subject_code" name="subject_code" value="<?php echo $_POST['subject_code'] ?? ''; ?>">
            </div>
            <div class="form-group form-group-full">
                <button type="submit" class="btn">Add Subject</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
