<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

$conn = get_api_db_connection();

// Fetch dashboard stats
$stats = [
    'total_students' => 0,
    'today_attendance' => 0,
    'pending_fees' => 0,
    'upcoming_exams' => 0
];

// Get total students
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM students");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_students'] = (int)$result['total'];

// Get today's attendance percentage
$stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM attendance 
         WHERE DATE(date) = CURDATE() AND status = 'present') / 
        (SELECT COUNT(DISTINCT student_id) FROM attendance 
         WHERE DATE(date) = CURDATE()) * 100 AS attendance_percent
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['today_attendance'] = $result['attendance_percent'] ? round($result['attendance_percent'], 2) : 0;

// Get pending fees
$stmt = $conn->prepare("SELECT SUM(balance) AS pending FROM fee_invoices WHERE balance > 0");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['pending_fees'] = (int)$result['pending'];

// Get upcoming exams (next 7 days)
$stmt = $conn->prepare("SELECT COUNT(*) as upcoming FROM exams WHERE exam_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['upcoming_exams'] = (int)$result['upcoming'];

// Get attendance trend (last 7 days)
$attendance = ['labels' => [], 'data' => []];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $attendance['labels'][] = date('M d', strtotime($date));
    
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM attendance 
             WHERE DATE(date) = :date AND status = 'present') / 
            (SELECT COUNT(DISTINCT student_id) FROM attendance 
             WHERE DATE(date) = :date) * 100 AS attendance_percent
    ");
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $attendance['data'][] = $result['attendance_percent'] ? round($result['attendance_percent'], 2) : 0;
}

// Get fee status
$fee_status = ['paid' => 0, 'pending' => 0, 'overdue' => 0];
$stmt = $conn->prepare("
    SELECT 
        SUM(paid_amount) AS paid,
        SUM(balance) AS pending,
        SUM(CASE WHEN due_date < CURDATE() THEN balance ELSE 0 END) AS overdue
    FROM fee_invoices
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$fee_status['paid'] = (int)$result['paid'];
$fee_status['pending'] = (int)$result['pending'];
$fee_status['overdue'] = (int)$result['overdue'];

// Get recent activity (last 10 activities)
$recent_activity = [];
$stmt = $conn->prepare("
    SELECT 
        al.id,
        al.activity_type AS title,
        al.description,
        u.name AS user,
        al.created_at,
        TIMESTAMPDIFF(MINUTE, al.created_at, NOW()) AS minutes_ago
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $time_ago = '';
    if ($row['minutes_ago'] < 60) {
        $time_ago = $row['minutes_ago'] . ' min ago';
    } elseif ($row['minutes_ago'] < 1440) {
        $time_ago = floor($row['minutes_ago'] / 60) . ' hours ago';
    } else {
        $time_ago = floor($row['minutes_ago'] / 1440) . ' days ago';
    }
    
    $recent_activity[] = [
        'title' => $row['title'],
        'description' => substr($row['description'], 0, 100) . '...',
        'user' => $row['user'],
        'time_ago' => $time_ago
    ];
}

// Return all data
echo json_encode([
    'stats' => $stats,
    'attendance' => $attendance,
    'fee_status' => $fee_status,
    'recent_activity' => $recent_activity
]);
?>
