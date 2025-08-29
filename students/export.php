<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/functions.php';
$conn = get_db_connection();

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query based on filters
$params = [];
$where_clauses = [];

if (!empty($search)) {
    $where_clauses[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ?)"; 
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($class_filter > 0) {
    $where_clauses[] = "s.current_class_id = ?";
    $params[] = $class_filter;
}

if (!empty($status_filter)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get students with class information
$query = "SELECT s.*, c.name as class_name 
          FROM students s 
          LEFT JOIN classes c ON s.current_class_id = c.id 
          $where_clause 
          ORDER BY s.admission_no DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students.csv"');

    $output = fopen('php://output', 'w');

    // Add headers
    fputcsv($output, ['Admission No', 'First Name', 'Last Name', 'Class', 'Gender', 'Admission Date', 'Status']);

    // Add data
    foreach ($students as $student) {
        fputcsv($output, [
            $student['admission_no'],
            $student['first_name'],
            $student['last_name'],
            $student['class_name'] ?? 'Not Assigned',
            $student['gender'],
            $student['admission_date'],
            $student['status']
        ]);
    }

    fclose($output);
    exit;
} elseif ($format == 'pdf') {
    // Generate HTML for PDF
    $html = '<style>table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ccc; padding: 8px; } </style>';
    $html .= '<h1>Student List</h1>';
    $html .= '<table>';
    $html .= '<thead><tr><th>Admission No</th><th>Name</th><th>Class</th><th>Gender</th><th>Admission Date</th><th>Status</th></tr></thead>';
    $html .= '<tbody>';
    foreach ($students as $student) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($student['admission_no']) . '</td>';
        $html .= '<td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($student['class_name'] ?? 'Not Assigned') . '</td>';
        $html .= '<td>' . htmlspecialchars($student['gender']) . '</td>';
        $html .= '<td>' . htmlspecialchars($student['admission_date']) . '</td>';
        $html .= '<td>' . htmlspecialchars($student['status']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    echo $html;
    echo '<script>window.print();</script>';
    exit;
}
?>