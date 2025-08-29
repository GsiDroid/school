<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect to login page or show an error
    header("Location: ../login.php");
    exit;
}

// Include common functions and database connection
require_once '../includes/functions.php';
$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed.');
    }

    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);

    if ($student_id) {
        try {
            // Begin transaction
            $conn->beginTransaction();

            // Delete related records first (e.g., attendance, exam_results, etc.)
            $stmt = $conn->prepare("DELETE FROM attendance WHERE student_id = ?");
            $stmt->execute([$student_id]);

            $stmt = $conn->prepare("DELETE FROM exam_results WHERE student_id = ?");
            $stmt->execute([$student_id]);

            $stmt = $conn->prepare("DELETE FROM fee_invoices WHERE student_id = ?");
            $stmt->execute([$student_id]);

            // Finally, delete the student
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);

            // Commit transaction
            $conn->commit();

            // Set success message
            set_message('success', 'Student has been deleted successfully.');

        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            set_message('error', 'Failed to delete student. Please try again.');
            error_log("Student Deletion Error: " . $e->getMessage());
        }
    }

    // Redirect back to the student list
    header("Location: index.php");
    exit;
} else {
    // If not a POST request, redirect to the student list
    header("Location: index.php");
    exit;
}
?>