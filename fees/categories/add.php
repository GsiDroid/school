<?php
require_once __DIR__ . '/../../includes/header.php';

if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if (empty($name)) $errors[] = "Category name is required.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO fee_categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            header("Location: index.php?success=Category added.");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Category name already exists.";
        }
    }
}
?>

<div class="content-header"><h1>Add Fee Category</h1></div>
<div class="card"><div class="card-body">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul></div>
    <?php endif; ?>
    <form action="add.php" method="POST">
        <div class="form-group">
            <label for="name">Category Name *</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <button type="submit" class="btn">Add Category</button>
    </form>
</div></div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>