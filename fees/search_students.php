<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!in_array($_SESSION['role'], ['Admin', 'Cashier'])) {
    http_response_code(403);
    exit('Access Denied');
}

$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$search_term = "%$query%";
$stmt = $pdo->prepare("SELECT id, first_name, last_name, admission_no FROM students WHERE first_name LIKE ? OR last_name LIKE ? OR admission_no LIKE ? LIMIT 10");
$stmt->execute([$search_term, $search_term, $search_term]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($students);
?>