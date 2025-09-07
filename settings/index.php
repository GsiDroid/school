<?php
require_once __DIR__ . '/../includes/header.php';
if ($_SESSION['role'] !== 'Admin') { exit('Access Denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $settings = $_POST['settings'];
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, trim($value)]);
    }

    // Handle logo upload
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
        $upload_dir = __DIR__ . '/../assets/img/';
        $logo_name = 'logo.' . pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $upload_dir . $logo_name)) {
            $stmt->execute(['school_logo', $logo_name]);
        }
    }

    $pdo->commit();
    header("Location: index.php?success=Settings updated.");
    exit();
}

$settings_stmt = $pdo->query("SELECT * FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<div class="content-header"><h1>System Settings</h1></div>
<div class="card"><div class="card-body">
    <?php if(isset($_GET['success'])) echo "<div class='alert alert-success'>".htmlspecialchars($_GET['success'])."</div>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="school_name">School Name</label>
            <input type="text" name="settings[school_name]" value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="current_academic_year">Current Academic Year</label>
            <input type="text" name="settings[current_academic_year]" value="<?php echo htmlspecialchars($settings['current_academic_year'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="school_logo">School Logo</label>
            <input type="file" name="school_logo">
            <?php if (!empty($settings['school_logo'])): ?>
                <img src="<?php echo $base_path; ?>assets/img/<?php echo htmlspecialchars($settings['school_logo']); ?>" alt="Current Logo" style="max-height: 50px; margin-top: 10px;">
            <?php endif; ?>
        </div>
        <button type="submit" class="btn">Save Settings</button>
    </form>
</div></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>