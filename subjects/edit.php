<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$subject_id) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    header("Location: index.php");
    exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);

    if (empty($subject_name)) $errors[] = "Subject name is required.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ?, subject_code = ? WHERE id = ?");
            $stmt->execute([$subject_name, $subject_code, $subject_id]);
            header("Location: index.php?success=Subject updated successfully.");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "A subject with this name already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
    $subject = array_merge($subject, $_POST);
}
?>

<div class="content-header">
    <h1>Edit Subject</h1>
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

        <form action="edit.php?id=<?php echo $subject_id; ?>" method="POST" class="form-grid">
            <div class="form-group">
                <label for="subject_name">Subject Name *</label>
                <input type="text" id="subject_name" name="subject_name" required value="<?php echo htmlspecialchars($subject['subject_name']); ?>">
            </div>
            <div class="form-group">
                <label for="subject_code">Subject Code</label>
                <input type="text" id="subject_code" name="subject_code" value="<?php echo htmlspecialchars($subject['subject_code']); ?>">
            </div>
            <div class="form-group form-group-full">
                <button type="submit" class="btn">Update Subject</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
