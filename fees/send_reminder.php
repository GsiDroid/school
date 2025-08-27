<?php
/**
 * Fees Management Module - Send Reminder
 * Handles sending fee reminders to students/parents
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

// Set page title
$pageTitle = "Send Fee Reminder";
$currentPage = "fees";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success_message = '';
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$invoice = null;
$student = null;

// Get invoice details
if ($invoice_id > 0) {
    $stmt = $conn->prepare("SELECT fi.*, s.first_name, s.last_name, s.email, s.phone, 
                            c.name as class_name, c.section 
                            FROM fee_invoices fi 
                            JOIN students s ON fi.student_id = s.id 
                            JOIN classes c ON s.class_id = c.id 
                            WHERE fi.id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        $errors[] = "Invoice not found";
    }
}

// Get reminder templates
$stmt = $conn->prepare("SELECT * FROM communication_templates WHERE type = 'fee_reminder' ORDER BY name");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminder'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $reminder_invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
        $reminder_method = isset($_POST['reminder_method']) ? $_POST['reminder_method'] : '';
        $reminder_subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
        $reminder_message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        // Validate required fields
        if ($reminder_invoice_id <= 0) {
            $errors[] = "Invalid invoice selected";
        }
        
        if (empty($reminder_method)) {
            $errors[] = "Please select a reminder method";
        }
        
        if (empty($reminder_subject)) {
            $errors[] = "Subject is required";
        }
        
        if (empty($reminder_message)) {
            $errors[] = "Message is required";
        }
        
        // Get invoice and student details for the reminder
        if ($reminder_invoice_id > 0) {
            $stmt = $conn->prepare("SELECT fi.*, s.first_name, s.last_name, s.email, s.phone 
                                FROM fee_invoices fi 
                                JOIN students s ON fi.student_id = s.id 
                                WHERE fi.id = ?");
            $stmt->execute([$reminder_invoice_id]);
            $reminder_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reminder_invoice) {
                $errors[] = "Invoice not found";
            } else {
                // Check if student has email/phone for the selected method
                if ($reminder_method == 'email' && empty($reminder_invoice['email'])) {
                    $errors[] = "Student does not have an email address";
                }
                
                if ($reminder_method == 'sms' && empty($reminder_invoice['phone'])) {
                    $errors[] = "Student does not have a phone number";
                }
            }
        }
        
        // If no errors, send reminder
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // In a real application, you would integrate with an email or SMS service here
                // For this example, we'll just log the reminder
                
                // Log the communication
                $stmt = $conn->prepare("INSERT INTO communication_logs 
                                       (student_id, type, method, subject, message, status, sent_by) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $reminder_invoice['student_id'],
                    'fee_reminder',
                    $reminder_method,
                    $reminder_subject,
                    $reminder_message,
                    'sent', // In a real app, you'd check the actual delivery status
                    $_SESSION['user_id']
                ]);
                
                // Update invoice reminder count
                $stmt = $conn->prepare("UPDATE fee_invoices 
                                       SET reminder_count = reminder_count + 1, 
                                           last_reminder_date = CURRENT_DATE() 
                                       WHERE id = ?");
                $stmt->execute([$reminder_invoice_id]);
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $student_name = $reminder_invoice['first_name'] . ' ' . $reminder_invoice['last_name'];
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'fee_reminder_sent',
                    "Sent {$reminder_method} reminder for invoice #{$reminder_invoice['invoice_number']} to {$student_name}",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                $success_message = "Fee reminder sent successfully to {$student_name} via {$reminder_method}";
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Process bulk reminder submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk_reminders'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        $reminder_status = isset($_POST['reminder_status']) ? $_POST['reminder_status'] : '';
        $reminder_method = isset($_POST['bulk_reminder_method']) ? $_POST['bulk_reminder_method'] : '';
        $reminder_subject = isset($_POST['bulk_subject']) ? trim($_POST['bulk_subject']) : '';
        $reminder_message = isset($_POST['bulk_message']) ? trim($_POST['bulk_message']) : '';
        
        // Validate required fields
        if (empty($reminder_status)) {
            $errors[] = "Please select an invoice status";
        }
        
        if (empty($reminder_method)) {
            $errors[] = "Please select a reminder method";
        }
        
        if (empty($reminder_subject)) {
            $errors[] = "Subject is required";
        }
        
        if (empty($reminder_message)) {
            $errors[] = "Message is required";
        }
        
        // If no errors, send bulk reminders
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Get all invoices matching the status
                $stmt = $conn->prepare("SELECT fi.*, s.first_name, s.last_name, s.email, s.phone 
                                       FROM fee_invoices fi 
                                       JOIN students s ON fi.student_id = s.id 
                                       WHERE fi.status = ?");
                $stmt->execute([$reminder_status]);
                $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $sent_count = 0;
                $failed_count = 0;
                
                foreach ($invoices as $inv) {
                    // Skip if student doesn't have required contact info
                    if (($reminder_method == 'email' && empty($inv['email'])) || 
                        ($reminder_method == 'sms' && empty($inv['phone']))) {
                        $failed_count++;
                        continue;
                    }
                    
                    // In a real application, you would integrate with an email or SMS service here
                    
                    // Log the communication
                    $stmt = $conn->prepare("INSERT INTO communication_logs 
                                           (student_id, type, method, subject, message, status, sent_by) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $inv['student_id'],
                        'fee_reminder',
                        $reminder_method,
                        $reminder_subject,
                        $reminder_message,
                        'sent', // In a real app, you'd check the actual delivery status
                        $_SESSION['user_id']
                    ]);
                    
                    // Update invoice reminder count
                    $stmt = $conn->prepare("UPDATE fee_invoices 
                                           SET reminder_count = reminder_count + 1, 
                                               last_reminder_date = CURRENT_DATE() 
                                           WHERE id = ?");
                    $stmt->execute([$inv['id']]);
                    
                    $sent_count++;
                }
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'bulk_fee_reminder_sent',
                    "Sent bulk {$reminder_method} reminders to {$sent_count} students with {$reminder_status} invoices",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                $success_message = "Bulk fee reminders sent successfully to {$sent_count} students via {$reminder_method}";
                if ($failed_count > 0) {
                    $success_message .= " ({$failed_count} skipped due to missing contact information)";
                }
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Fees Management</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
            <div class="mt-2">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-list me-1"></i> Back to Invoices
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Single Reminder Form -->
        <?php if ($invoice): ?>
            <div class="col-xl-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-bell me-1"></i>
                        Send Individual Reminder
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5>Invoice Details</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Invoice Number</th>
                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>Student</th>
                                    <td><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Class</th>
                                    <td><?php echo htmlspecialchars($invoice['class_name'] . ' ' . $invoice['section']); ?></td>
                                </tr>
                                <tr>
                                    <th>Term / Year</th>
                                    <td><?php echo htmlspecialchars($invoice['term'] . ' / ' . $invoice['academic_year']); ?></td>
                                </tr>
                                <tr>
                                    <th>Amount Due</th>
                                    <td>$<?php echo number_format($invoice['balance'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Due Date</th>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                                        <?php if (strtotime($invoice['due_date']) < time()): ?>
                                            <span class="badge bg-danger ms-2">Overdue</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if ($invoice['status'] == 'paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php elseif ($invoice['status'] == 'partial'): ?>
                                            <span class="badge bg-warning">Partially Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Previous Reminders</th>
                                    <td><?php echo $invoice['reminder_count']; ?></td>
                                </tr>
                                <?php if ($invoice['last_reminder_date']): ?>
                                    <tr>
                                        <th>Last Reminder</th>
                                        <td><?php echo date('M d, Y', strtotime($invoice['last_reminder_date'])); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="reminder_method" class="form-label">Reminder Method</label>
                                <select class="form-select" id="reminder_method" name="reminder_method" required>
                                    <option value="">Select Method</option>
                                    <option value="email" <?php echo !empty($invoice['email']) ? '' : 'disabled'; ?>>
                                        Email <?php echo !empty($invoice['email']) ? '(' . htmlspecialchars($invoice['email']) . ')' : '(Not Available)'; ?>
                                    </option>
                                    <option value="sms" <?php echo !empty($invoice['phone']) ? '' : 'disabled'; ?>>
                                        SMS <?php echo !empty($invoice['phone']) ? '(' . htmlspecialchars($invoice['phone']) . ')' : '(Not Available)'; ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="template" class="form-label">Template (Optional)</label>
                                <select class="form-select" id="template" name="template">
                                    <option value="">Select Template</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>">
                                            <?php echo htmlspecialchars($template['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required 
                                       value="Fee Payment Reminder: Invoice #<?php echo $invoice['invoice_number']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="6" required>
Dear Parent/Guardian of <?php echo $invoice['first_name'] . ' ' . $invoice['last_name']; ?>,

This is a friendly reminder that the fee payment for <?php echo $invoice['term'] . ', ' . $invoice['academic_year']; ?> (Invoice #<?php echo $invoice['invoice_number']; ?>) of $<?php echo number_format($invoice['balance'], 2); ?> is <?php echo strtotime($invoice['due_date']) < time() ? 'overdue' : 'due'; ?> on <?php echo date('F d, Y', strtotime($invoice['due_date'])); ?>.

Please make the payment at your earliest convenience to avoid any late fees.

Thank you,
School Administration
                                </textarea>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" name="send_reminder" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Send Reminder
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Invoices
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Bulk Reminder Form -->
        <div class="<?php echo $invoice ? 'col-xl-6' : 'col-xl-12'; ?>">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-bell me-1"></i>
                    Send Bulk Reminders
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="reminder_status" class="form-label">Invoice Status</label>
                            <select class="form-select" id="reminder_status" name="reminder_status" required>
                                <option value="">Select Status</option>
                                <option value="pending">Pending</option>
                                <option value="partial">Partially Paid</option>
                                <option value="overdue">Overdue</option>
                            </select>
                            <div class="form-text">Select which invoices to send reminders for</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bulk_reminder_method" class="form-label">Reminder Method</label>
                            <select class="form-select" id="bulk_reminder_method" name="bulk_reminder_method" required>
                                <option value="">Select Method</option>
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                            </select>
                            <div class="form-text">Students without the selected contact method will be skipped</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bulk_template" class="form-label">Template (Optional)</label>
                            <select class="form-select" id="bulk_template" name="bulk_template">
                                <option value="">Select Template</option>
                                <?php foreach ($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bulk_subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="bulk_subject" name="bulk_subject" required 
                                   value="Fee Payment Reminder">
                        </div>
                        
                        <div class="mb-3">
                            <label for="bulk_message" class="form-label">Message</label>
                            <textarea class="form-control" id="bulk_message" name="bulk_message" rows="6" required>
Dear Parent/Guardian,

This is a friendly reminder that you have an outstanding fee payment due. Please check your invoice details and make the payment at your earliest convenience to avoid any late fees.

If you have already made the payment, please disregard this message.

Thank you,
School Administration
                            </textarea>
                            <div class="form-text">
                                You can use the following placeholders: {student_name}, {invoice_number}, {amount}, {due_date}
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" name="send_bulk_reminders" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send Bulk Reminders
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle template selection for individual reminder
        const templateSelect = document.getElementById('template');
        const subjectInput = document.getElementById('subject');
        const messageInput = document.getElementById('message');
        
        if (templateSelect) {
            templateSelect.addEventListener('change', function() {
                if (this.value) {
                    // In a real application, you would fetch the template content via AJAX
                    // For this example, we'll just show an alert
                    alert('In a real application, this would load the selected template content');
                }
            });
        }
        
        // Handle template selection for bulk reminder
        const bulkTemplateSelect = document.getElementById('bulk_template');
        const bulkSubjectInput = document.getElementById('bulk_subject');
        const bulkMessageInput = document.getElementById('bulk_message');
        
        if (bulkTemplateSelect) {
            bulkTemplateSelect.addEventListener('change', function() {
                if (this.value) {
                    // In a real application, you would fetch the template content via AJAX
                    alert('In a real application, this would load the selected template content');
                }
            });
        }
        
        // Handle status selection for bulk reminders
        const reminderStatusSelect = document.getElementById('reminder_status');
        
        if (reminderStatusSelect) {
            reminderStatusSelect.addEventListener('change', function() {
                if (this.value === 'overdue') {
                    if (bulkSubjectInput) {
                        bulkSubjectInput.value = 'URGENT: Overdue Fee Payment Reminder';
                    }
                    if (bulkMessageInput) {
                        bulkMessageInput.value = 'Dear Parent/Guardian,\n\nThis is an URGENT reminder that you have an OVERDUE fee payment. Please make the payment immediately to avoid further penalties.\n\nIf you are facing any financial difficulties, please contact the school administration to discuss possible arrangements.\n\nThank you,\nSchool Administration';
                    }
                } else if (this.value === 'pending') {
                    if (bulkSubjectInput) {
                        bulkSubjectInput.value = 'Fee Payment Reminder';
                    }
                    if (bulkMessageInput) {
                        bulkMessageInput.value = 'Dear Parent/Guardian,\n\nThis is a friendly reminder that you have an outstanding fee payment due. Please check your invoice details and make the payment at your earliest convenience to avoid any late fees.\n\nIf you have already made the payment, please disregard this message.\n\nThank you,\nSchool Administration';
                    }
                } else if (this.value === 'partial') {
                    if (bulkSubjectInput) {
                        bulkSubjectInput.value = 'Reminder: Complete Your Pending Fee Payment';
                    }
                    if (bulkMessageInput) {
                        bulkMessageInput.value = 'Dear Parent/Guardian,\n\nThank you for your partial fee payment. This is a reminder to complete the remaining balance at your earliest convenience.\n\nIf you have already completed the payment, please disregard this message.\n\nThank you,\nSchool Administration';
                    }
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>