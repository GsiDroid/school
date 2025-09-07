<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
if (!$class_id) { header("Location: index.php"); exit(); }

$class = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$class->execute([$class_id]);
$class = $class->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amounts = $_POST['amounts'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("REPLACE INTO fee_structure (class_id, category_id, amount) VALUES (?, ?, ?)");
        foreach ($amounts as $category_id => $amount) {
            if (is_numeric($amount) && $amount >= 0) {
                $stmt->execute([$class_id, $category_id, $amount]);
            }
        }
        $pdo->commit();
        header("Location: index.php?success=Fee structure updated for " . urlencode($class['class_name']));
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to update structure: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM fee_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$structure_stmt = $pdo->prepare("SELECT category_id, amount FROM fee_structure WHERE class_id = ?");
$structure_stmt->execute([$class_id]);
$current_structure = $structure_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="content-header"><h1>Fee Structure for: <?php echo htmlspecialchars($class['class_name']); ?></h1></div>
<div class="card">
    <div class="card-body">
        <?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
        <form action="fee_structure.php?class_id=<?php echo $class_id; ?>" method="POST">
            <table class="table">
                <thead><tr><th>Fee Category</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $category): 
                        $amount = $current_structure[$category['id']] ?? '0.00';
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><input type="number" step="0.01" name="amounts[<?php echo $category['id']; ?>]" value="<?php echo htmlspecialchars($amount); ?>" class="form-control"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn">Save Structure</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>