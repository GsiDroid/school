<?php
session_start();
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if exam ID is provided
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    set_message('error', 'Invalid exam ID.');
    header('Location: index.php');
    exit;
}

$exam_id = intval($_GET['exam_id']);

// Check if student ID is provided (for single result deletion)
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

try {
    $pdo = get_db_connection();
    
    // Verify exam exists
    $stmt = $pdo->prepare("SELECT id, exam_name FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam) {
        set_message('error', 'Exam not found.');
        header('Location: index.php');
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    if ($student_id > 0) {
        // Delete single student result
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            $pdo->rollBack();
            set_message('error', 'Student not found.');
            header('Location: view_results.php?exam_id=' . $exam_id);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM exam_results WHERE exam_id = ? AND student_id = ?");
        $stmt->execute([$exam_id, $student_id]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            set_message('success', 'Student result deleted successfully.');
        } else {
            $pdo->rollBack();
            set_message('error', 'No result found for this student.');
        }
    } else {
        // Delete all results for this exam
        $stmt = $pdo->prepare("DELETE FROM exam_results WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        
        $pdo->commit();
        set_message('success', 'All results for "' . $exam['exam_name'] . '" have been deleted.');
    }
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    set_message('error', 'Database error: ' . $e->getMessage());
}

// Redirect back to view results page
header('Location: view_results.php?exam_id=' . $exam_id);
exit;