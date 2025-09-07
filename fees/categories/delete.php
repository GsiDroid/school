<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM fee_categories WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?success=Category deleted.");
    } catch (PDOException $e) {
        header("Location: index.php?error=Cannot delete category, it is in use in a fee structure.");
    }
}
exit();
?>