<?php
require_once __DIR__ . '/../includes/header.php';

// Role check
if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
if (!$class_id) {
    header("Location: ../classes/index.php?error=Invalid class specified.");
    exit();
}

// Fetch class details
$class_stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$class_stmt->execute([$class_id]);
$class = $class_stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    header("Location: ../classes/index.php?error=Class not found.");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignments = $_POST['assignments'] ?? [];
    
    $pdo->beginTransaction();
    try {
        // 1. Delete all existing assignments for this class
        $delete_stmt = $pdo->prepare("DELETE FROM class_subjects WHERE class_id = ?");
        $delete_stmt->execute([$class_id]);

        // 2. Insert new assignments
        $insert_stmt = $pdo->prepare("INSERT INTO class_subjects (class_id, subject_id, teacher_id) VALUES (?, ?, ?)");
        foreach ($assignments as $subject_id => $teacher_id) {
            if (!empty($teacher_id)) { // Only assign if a teacher is selected
                $insert_stmt->execute([$class_id, $subject_id, $teacher_id]);
            }
        }

        $pdo->commit();
        header("Location: ../classes/index.php?success=Subject assignments updated successfully for " . urlencode($class['class_name']));
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to update assignments: " . $e->getMessage();
    }
}

// Fetch all subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all teachers
$teachers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Teacher' AND is_active = 1 ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current assignments for this class
$current_assignments_stmt = $pdo->prepare("SELECT subject_id, teacher_id FROM class_subjects WHERE class_id = ?");
$current_assignments_stmt->execute([$class_id]);
$current_assignments = $current_assignments_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<div class="content-header">
    <h1>Assign Subjects for: <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['academic_year'] . ')'); ?></h1>
    <a href="../classes/index.php" class="btn">Back to Classes</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="assign.php?class_id=<?php echo $class_id; ?>" method="POST">
            <table class="table">
                <thead>
                    <tr>
                        <th>Assign</th>
                        <th>Subject</th>
                        <th>Assign Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): 
                        $subject_id = $subject['id'];
                        $is_assigned = isset($current_assignments[$subject_id]);
                        $assigned_teacher_id = $current_assignments[$subject_id] ?? null;
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="assignments[<?php echo $subject_id; ?>]" value="" 
                                       onchange="this.nextElementSibling.disabled = !this.checked; this.value = this.checked ? <?php echo $assigned_teacher_id ?? 'null' ?> : ''" 
                                       <?php if ($is_assigned) echo 'checked'; ?>>
                            </td>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td>
                                <select name="assignments[<?php echo $subject_id; ?>]" <?php if (!$is_assigned) echo 'disabled'; ?>>
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php if ($teacher['id'] == $assigned_teacher_id) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="form-group form-group-full" style="margin-top: 1.5rem;">
                <button type="submit" class="btn">Save Assignments</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>