<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
if (!$class_id) { header("Location: index.php"); exit(); }

$class = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$class->execute([$class_id]);
$class = $class->fetch(PDO::FETCH_ASSOC);

// Fetch subjects and teachers assigned to this class
$stmt = $pdo->prepare("SELECT s.id as subject_id, s.subject_name, u.id as teacher_id, u.full_name as teacher_name 
                       FROM class_subjects cs 
                       JOIN subjects s ON cs.subject_id = s.id 
                       JOIN users u ON cs.teacher_id = u.id 
                       WHERE cs.class_id = ?");
$stmt->execute([$class_id]);
$assigned_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing timetable data
$timetable_stmt = $pdo->prepare("SELECT * FROM timetable WHERE class_id = ?");
$timetable_stmt->execute([$class_id]);
$timetable_data = [];
while ($row = $timetable_stmt->fetch(PDO::FETCH_ASSOC)) {
    $timetable_data[$row['day_of_week']][$row['start_time']] = $row;
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$time_slots = ['09:00:00' => '09:00 - 10:00', '10:00:00' => '10:00 - 11:00', '11:00:00' => '11:00 - 12:00', '12:00:00' => '12:00 - 13:00', '14:00:00' => '14:00 - 15:00'];

?>
<style>.timetable-grid { display: grid; grid-template-columns: 100px repeat(6, 1fr); border: 1px solid #ccc; } .grid-header, .grid-cell, .grid-time { padding: 10px; border: 1px solid #ccc; text-align: center; } .grid-cell select { width: 100%; } </style>

<div class="content-header"><h1>Timetable for: <?php echo htmlspecialchars($class['class_name']); ?></h1></div>
<div class="card"><div class="card-body" style="overflow-x:auto;">
    <div id="response-message"></div>
    <div class="timetable-grid">
        <div class="grid-header">Time</div>
        <?php foreach ($days as $day): ?><div class="grid-header"><?php echo $day; ?></div><?php endforeach; ?>

        <?php foreach ($time_slots as $start_time => $label): ?>
            <div class="grid-time"><?php echo $label; ?></div>
            <?php foreach ($days as $day): 
                $entry = $timetable_data[$day][$start_time] ?? null;
            ?>
            <div class="grid-cell">
                <select class="timetable-slot" data-day="<?php echo $day; ?>" data-start_time="<?php echo $start_time; ?>">
                    <option value="">-- Free --</option>
                    <?php foreach ($assigned_subjects as $sub): 
                        $value = $sub['subject_id'] . '-' . $sub['teacher_id'];
                        $selected = ($entry && $entry['subject_id'] == $sub['subject_id']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($sub['subject_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div></div>

<script>
document.querySelectorAll('.timetable-slot').forEach(slot => {
    slot.addEventListener('change', function() {
        let select = this;
        let day = select.dataset.day;
        let start_time = select.dataset.start_time;
        let value = select.value;
        let [subject_id, teacher_id] = value.split('-');

        let formData = new FormData();
        formData.append('class_id', '<?php echo $class_id; ?>');
        formData.append('day', day);
        formData.append('start_time', start_time);
        formData.append('subject_id', subject_id || '0');
        formData.append('teacher_id', teacher_id || '0');

        fetch('ajax_handler.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                let msgDiv = document.getElementById('response-message');
                msgDiv.className = `alert alert-${data.status}`;
                msgDiv.textContent = data.message;
                setTimeout(() => msgDiv.textContent = '', 3000);
            });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>