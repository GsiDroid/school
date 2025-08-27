<?php
/**
 * Delete Expense
 * Handles the deletion of expense records
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include common functions
require_once '../includes/functions.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Check if it's a POST request and expense_id is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['expense_id'])) {
    $expense_id = (int)$_POST['expense_id'];
    
    // Get expense details to find receipt file if exists
    $stmt = $conn->prepare("SELECT receipt_file FROM expenses WHERE id = ?");
    $stmt->execute([$expense_id]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete receipt file if exists
    if ($expense && !empty($expense['receipt_file'])) {
        $file_path = '../uploads/expenses/' . $expense['receipt_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete the expense record
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$expense_id]);
    
    // Check if deletion was successful
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Expense deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete expense. Please try again.";
    }
    
    // Redirect back to expenses list
    header("Location: index.php");
    exit();
} else {
    // Invalid request, redirect to expenses list
    $_SESSION['error_message'] = "Invalid request";
    header("Location: index.php");
    exit();
}
?>