<?php
session_start();
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Handle form submission for adding/editing subjects
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        set_message('error', 'Invalid request. Please try again.');
        header('Location: subjects.php');
        exit;
    }
    
    // Process form data
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $description = trim($_POST['description']);
    
    // Validate input
    if (empty($subject_name)) {
        set_message('error', 'Subject name is required.');
        header('Location: subjects.php');
        exit;
    }
    
    try {
        $pdo = get_db_connection();
        
        if ($subject_id > 0) {
            // Update existing subject
            $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, description = ? WHERE id = ?");
            $stmt->execute([$subject_name, $subject_code, $description, $subject_id]);
            set_message('success', 'Subject updated successfully.');
        } else {
            // Check if subject with same name already exists
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_name = ?");
            $stmt->execute([$subject_name]);
            if ($stmt->rowCount() > 0) {
                set_message('error', 'A subject with this name already exists.');
                header('Location: subjects.php');
                exit;
            }
            
            // Add new subject
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, description) VALUES (?, ?, ?)");
            $stmt->execute([$subject_name, $subject_code, $description]);
            set_message('success', 'Subject added successfully.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Database error: ' . $e->getMessage());
    }
    
    header('Location: subjects.php');
    exit;
}

// Handle subject deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $subject_id = intval($_GET['id']);
    
    try {
        $pdo = get_db_connection();
        
        // Check if subject is used in any exams
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE subject_id = ?");
        $stmt->execute([$subject_id]);
        $exam_count = $stmt->fetchColumn();
        
        if ($exam_count > 0) {
            set_message('error', 'Cannot delete subject. It is used in ' . $exam_count . ' exam(s).');
        } else {
            // Delete the subject
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->execute([$subject_id]);
            set_message('success', 'Subject deleted successfully.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Database error: ' . $e->getMessage());
    }
    
    header('Location: subjects.php');
    exit;
}

// Get all subjects
$subjects = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT * FROM subjects ORDER BY subject_name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message('error', 'Database error: ' . $e->getMessage());
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = 'Manage Subjects';
include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Exams</a></li>
        <li class="breadcrumb-item active">Subjects</li>
    </ol>
    
    <?php display_messages(); ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Subjects
            <button type="button" class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#subjectModal">
                <i class="fas fa-plus"></i> Add Subject
            </button>
        </div>
        <div class="card-body">
            <table id="subjectsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th>Subject Code</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($subject['description']); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-subject" 
                                data-id="<?php echo $subject['id']; ?>" 
                                data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>" 
                                data-code="<?php echo htmlspecialchars($subject['subject_code']); ?>" 
                                data-description="<?php echo htmlspecialchars($subject['description']); ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="#" class="btn btn-sm btn-danger delete-subject" data-id="<?php echo $subject['id']; ?>" data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Subject Modal -->
<div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subjectModalLabel">Add Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="subjectForm" method="post" action="subjects.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="subject_id" id="subject_id" value="0">
                    
                    <div class="mb-3">
                        <label for="subject_name" class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject_code" class="form-label">Subject Code</label>
                        <input type="text" class="form-control" id="subject_code" name="subject_code">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the subject <span id="delete-subject-name" class="fw-bold"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-delete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#subjectsTable').DataTable({
        responsive: true,
        order: [[0, 'asc']]
    });
    
    // Handle edit button click
    $('.edit-subject').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const code = $(this).data('code');
        const description = $(this).data('description');
        
        $('#subject_id').val(id);
        $('#subject_name').val(name);
        $('#subject_code').val(code);
        $('#description').val(description);
        
        $('#subjectModalLabel').text('Edit Subject');
        $('#subjectModal').modal('show');
    });
    
    // Reset modal form when closed
    $('#subjectModal').on('hidden.bs.modal', function() {
        $('#subject_id').val('0');
        $('#subject_name').val('');
        $('#subject_code').val('');
        $('#description').val('');
        $('#subjectModalLabel').text('Add Subject');
    });
    
    // Handle delete button click
    $('.delete-subject').click(function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        $('#delete-subject-name').text(name);
        $('#confirm-delete').attr('href', 'subjects.php?action=delete&id=' + id);
        $('#deleteModal').modal('show');
    });
});
</script>