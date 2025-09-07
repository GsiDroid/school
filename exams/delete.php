<?php
require_once __DIR__ . '/../config/database.php';
session_start();
if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: index.php?success=Exam deleted.");
exit();
?>