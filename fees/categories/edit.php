<?php
require_once __DIR__ . '/../../includes/header.php';

if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM fee_categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) { header("Location: index.php"); exit(); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if (empty($name)) $errors[] = "Category name is required.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE fee_categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            header("Location: index.php?success=Category updated.");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Category name already exists.";
        }
    }
}
?>

<div class="content-header"><h1>Edit Fee Category</h1></div>
<div class="card"><div class="card-body">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul></div>
    <?php endif; ?>
    <form action="edit.php?id=<?php echo $id; ?>" method="POST">
        <div class="form-group">
            <label for="name">Category Name *</label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($category['name']); ?>">
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($category['description']); ?></textarea>
        </div>
        <button type="submit" class="btn">Update Category</button>
    </form>
</div></div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>