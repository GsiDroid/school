<?php
require_once 'includes/header.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Default welcome
$welcome_message = "<p>Use the sidebar to navigate through the system.</p>";
$widgets = [];

if ($role === 'Admin') {
    $student_count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $teacher_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Teacher'")->fetchColumn();
    $class_count = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
    $fees_today = $pdo->query("SELECT SUM(amount_paid) FROM fee_payments WHERE payment_date = CURDATE()")->fetchColumn();

    $widgets = [
        ['title' => 'Total Students', 'value' => $student_count, 'icon' => 'bi-people-fill'],
        ['title' => 'Total Teachers', 'value' => $teacher_count, 'icon' => 'bi-person-video3'],
        ['title' => 'Total Classes', 'value' => $class_count, 'icon' => 'bi-bank'],
        ['title' => 'Fees Collected Today', 'value' => '$' . number_format($fees_today ?: 0, 2), 'icon' => 'bi-cash-coin'],
    ];
} elseif ($role === 'Teacher') {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT class_id) FROM class_subjects WHERE teacher_id = ?");
    $stmt->execute([$user_id]);
    $class_count = $stmt->fetchColumn();

    $widgets = [
        ['title' => 'My Assigned Classes', 'value' => $class_count, 'icon' => 'bi-bank', 'link' => $base_path . 'attendance/index.php'],
        ['title' => 'Take Attendance', 'value' => 'Go', 'icon' => 'bi-calendar-check-fill', 'link' => $base_path . 'attendance/index.php'],
        ['title' => 'Enter Results', 'value' => 'Go', 'icon' => 'bi-pencil-square', 'link' => $base_path . 'results/index.php'],
    ];
} elseif ($role === 'Student') {
    $stmt = $pdo->prepare("SELECT c.class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.user_id = ?");
    $stmt->execute([$user_id]);
    $class_name = $stmt->fetchColumn();

    $widgets = [
        ['title' => 'My Class', 'value' => $class_name, 'icon' => 'bi-bank'],
        ['title' => 'My Results', 'value' => 'View', 'icon' => 'bi-journal-text', 'link' => $base_path . 'results/my_results.php'],
        ['title' => 'My Timetable', 'value' => 'View', 'icon' => 'bi-table', 'link' => $base_path . 'timetable/my_schedule.php'],
    ];
} elseif ($role === 'Cashier') {
    $fees_today = $pdo->query("SELECT SUM(amount_paid) FROM fee_payments WHERE payment_date = CURDATE() AND created_by = $user_id")->fetchColumn();
    $widgets = [
        ['title' => 'Fees Collected Today', 'value' => '

?>

<div class="content-header">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
</div>

<div class="dashboard-grid">
    <?php foreach ($widgets as $widget): ?>
        <div class="dashboard-widget">
            <?php if (isset($widget['link'])): ?><a href="<?php echo $widget['link']; ?>" class="widget-link"><?php endif; ?>
            <div class="widget-icon"><i class="bi <?php echo $widget['icon']; ?>"></i></div>
            <div class="widget-content">
                <div class="widget-title"><?php echo $widget['title']; ?></div>
                <div class="widget-value"><?php echo $widget['value']; ?></div>
            </div>
            <?php if (isset($widget['link'])): ?></a><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($role === 'Admin'): ?>
<div class="card" style="margin-top: 2rem;">
    <div class="card-header"><h3>Monthly Fee Collection</h3></div>
    <div class="card-body">
        <canvas id="feesChart"></canvas>
    </div>
</div>
<?php 
    $fee_chart_data_stmt = $pdo->query("SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount_paid) as total FROM fee_payments GROUP BY month ORDER BY month ASC LIMIT 6");
    $fee_chart_data = $fee_chart_data_stmt->fetchAll(PDO::FETCH_ASSOC);
    $chart_labels = json_encode(array_column($fee_chart_data, 'month'));
    $chart_values = json_encode(array_column($fee_chart_data, 'total'));
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('feesChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo $chart_labels; ?>,
        datasets: [{
            label: 'Fees Collected',
            data: <?php echo $chart_values; ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } }
    }
});
</script>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
?> . number_format($fees_today ?: 0, 2), 'icon' => 'bi-cash-coin'],
        ['title' => 'Collect Fees', 'value' => 'Go', 'icon' => 'bi-receipt', 'link' => $base_path . 'fees/collect.php'],
    ];
} elseif ($role === 'Parent') {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, photo FROM students WHERE parent_id = ?");
    $stmt->execute([$user_id]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $welcome_message = "<p>Select one of your children below to view their details.</p>";
}

?>

<div class="content-header">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
</div>

<div class="dashboard-grid">
    <?php foreach ($widgets as $widget): ?>
        <div class="dashboard-widget">
            <?php if (isset($widget['link'])): ?><a href="<?php echo $widget['link']; ?>" class="widget-link"><?php endif; ?>
            <div class="widget-icon"><i class="bi <?php echo $widget['icon']; ?>"></i></div>
            <div class="widget-content">
                <div class="widget-title"><?php echo $widget['title']; ?></div>
                <div class="widget-value"><?php echo $widget['value']; ?></div>
            </div>
            <?php if (isset($widget['link'])): ?></a><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($role === 'Admin'): ?>
<div class="card" style="margin-top: 2rem;">
    <div class="card-header"><h3>Monthly Fee Collection</h3></div>
    <div class="card-body">
        <canvas id="feesChart"></canvas>
    </div>
</div>
<?php 
    $fee_chart_data_stmt = $pdo->query("SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount_paid) as total FROM fee_payments GROUP BY month ORDER BY month ASC LIMIT 6");
    $fee_chart_data = $fee_chart_data_stmt->fetchAll(PDO::FETCH_ASSOC);
    $chart_labels = json_encode(array_column($fee_chart_data, 'month'));
    $chart_values = json_encode(array_column($fee_chart_data, 'total'));
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('feesChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo $chart_labels; ?>,
        datasets: [{
            label: 'Fees Collected',
            data: <?php echo $chart_values; ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } }
    }
});
</script>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
?>