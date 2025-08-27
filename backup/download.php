<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Check if file parameter is provided
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('Location: index.php');
    exit;
}

// Sanitize filename and build path
$filename = basename($_GET['file']);
$file_path = '../backups/' . $filename;

// Check if file exists and has .sql extension
if (!file_exists($file_path) || pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
    header('Location: index.php');
    exit;
}

// Log the download action
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], 'download_backup', 'Downloaded backup file: ' . $filename]);

// Set headers for file download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Clear output buffer
ob_clean();
flush();

// Read file and output to browser
readfile($file_path);
exit;