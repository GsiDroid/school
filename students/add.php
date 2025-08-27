<?php
/**
 * Student Management Module - Add New Student
 * Handles new student registration with personal, academic, and guardian details
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
$pageTitle = "Add New Student";
$currentPage = "students";

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get all classes for dropdown
$class_query = "SELECT id, name, section FROM classes ORDER BY name, section";
$stmt = $conn->prepare($class_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF token validation failed";
    } else {
        // Validate required fields
        $required_fields = [
            'admission_no' => 'Admission Number',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'gender' => 'Gender',
            'date_of_birth' => 'Date of Birth',
            'address' => 'Address',
            'admission_date' => 'Admission Date',
            'guardian_name' => 'Guardian Name',
            'guardian_relation' => 'Guardian Relation',
            'guardian_phone' => 'Guardian Phone'
        ];
        
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors[] = "$label is required";
            }
        }
        
        // Validate admission number format (alphanumeric)
        if (!empty($_POST['admission_no']) && !preg_match('/^[A-Za-z0-9-\/]+$/', $_POST['admission_no'])) {
            $errors[] = "Admission Number should contain only letters, numbers, hyphens, and slashes";
        }
        
        // Check if admission number already exists
        if (!empty($_POST['admission_no'])) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE admission_no = ?");
            $stmt->execute([$_POST['admission_no']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Admission Number already exists";
            }
        }
        
        // Validate email if provided
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Validate phone number if provided
        if (!empty($_POST['phone']) && !preg_match('/^[0-9+\-\s()]+$/', $_POST['phone'])) {
            $errors[] = "Invalid phone number format";
        }
        
        // Validate guardian phone
        if (!empty($_POST['guardian_phone']) && !preg_match('/^[0-9+\-\s()]+$/', $_POST['guardian_phone'])) {
            $errors[] = "Invalid guardian phone number format";
        }
        
        // Validate guardian email if provided
        if (!empty($_POST['guardian_email']) && !filter_var($_POST['guardian_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid guardian email format";
        }
        
        // Process profile image (either uploaded file or captured photo)
        $profile_image = null;
        
        // Check if we have a captured photo from webcam
        if (empty($errors) && !empty($_POST['capturedPhoto'])) {
            // Create upload directory if it doesn't exist
            $upload_dir = "../uploads/students/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Get the base64 image data (remove the data:image/jpeg;base64, part)
            $image_parts = explode(";", $_POST['capturedPhoto']);
            $image_base64 = explode(",", $image_parts[1]);
            $image_data = base64_decode($image_base64[1]);
            
            // Generate unique filename for captured photo
            $filename = time() . '_' . $_POST['admission_no'] . '_captured.jpg';
            $target_file = $upload_dir . $filename;
            
            // Save the image
            if (file_put_contents($target_file, $image_data)) {
                $profile_image = $filename;
            } else {
                $errors[] = "Failed to save captured photo";
            }
        }
        // Process uploaded file if no captured photo and no errors
        elseif (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed";
            } elseif ($_FILES['profile_image']['size'] > $max_size) {
                $errors[] = "File size exceeds the maximum limit of 2MB";
            } else {
                // Create upload directory if it doesn't exist
                $upload_dir = "../uploads/students/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $filename = time() . '_' . $_POST['admission_no'] . '_' . $_FILES['profile_image']['name'];
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $profile_image = $filename;
                } else {
                    $errors[] = "Failed to upload profile image";
                }
            }
        }
        
        // If no errors, insert student data
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Insert student data
                $stmt = $conn->prepare("INSERT INTO students 
                    (admission_no, first_name, last_name, gender, date_of_birth, blood_group, 
                    religion, nationality, address, phone, email, current_class_id, 
                    admission_date, profile_image, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $_POST['admission_no'],
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['gender'],
                    $_POST['date_of_birth'],
                    $_POST['blood_group'] ?? null,
                    $_POST['religion'] ?? null,
                    $_POST['nationality'] ?? null,
                    $_POST['address'],
                    $_POST['phone'] ?? null,
                    $_POST['email'] ?? null,
                    !empty($_POST['class_id']) ? $_POST['class_id'] : null,
                    $_POST['admission_date'],
                    $profile_image,
                    'active'
                ]);
                
                $student_id = $conn->lastInsertId();
                
                // Insert guardian information
                $stmt = $conn->prepare("INSERT INTO student_guardians 
                    (student_id, relation, name, occupation, phone, email, address, is_primary) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $student_id,
                    $_POST['guardian_relation'],
                    $_POST['guardian_name'],
                    $_POST['guardian_occupation'] ?? null,
                    $_POST['guardian_phone'],
                    $_POST['guardian_email'] ?? null,
                    $_POST['guardian_address'] ?? $_POST['address'], // Use student address if not provided
                    true // Primary guardian
                ]);
                
                // Process document uploads if any
                if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
                    $upload_dir = "../uploads/documents/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $allowed_doc_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    $max_doc_size = 5 * 1024 * 1024; // 5MB
                    
                    $document_stmt = $conn->prepare("INSERT INTO student_documents 
                        (student_id, document_type, document_name, file_path, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?)");
                    
                    for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                        if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                            $doc_type = $_POST['document_types'][$i] ?? 'Other';
                            $doc_name = $_FILES['documents']['name'][$i];
                            
                            if (!in_array($_FILES['documents']['type'][$i], $allowed_doc_types)) {
                                continue; // Skip invalid file types
                            }
                            
                            if ($_FILES['documents']['size'][$i] > $max_doc_size) {
                                continue; // Skip files exceeding size limit
                            }
                            
                            $doc_filename = time() . '_' . $_POST['admission_no'] . '_' . $doc_name;
                            $doc_target_file = $upload_dir . $doc_filename;
                            
                            if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], $doc_target_file)) {
                                $document_stmt->execute([
                                    $student_id,
                                    $doc_type,
                                    $doc_name,
                                    $doc_filename,
                                    $_SESSION['user_id']
                                ]);
                            }
                        }
                    }
                }
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_logs 
                    (user_id, activity_type, description, ip_address) 
                    VALUES (?, ?, ?, ?)");
                
                $activity_stmt->execute([
                    $_SESSION['user_id'],
                    'student_registration',
                    "Registered new student: {$_POST['first_name']} {$_POST['last_name']} (Admission No: {$_POST['admission_no']})",
                    $_SERVER['REMOTE_ADDR']
                ]);
                
                $conn->commit();
                $success = true;
                
                // Redirect to student list or view page
                header("Location: view.php?id=$student_id&success=1");
                exit();
                
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
        <li class="breadcrumb-item"><a href="index.php">Student Management</a></li>
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
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-plus me-1"></i>
            Student Registration Form
        </div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data" id="studentForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2">Personal Information</h5>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="admission_no" class="form-label">Admission Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="admission_no" name="admission_no" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="blood_group" class="form-label">Blood Group</label>
                            <select class="form-select" id="blood_group" name="blood_group">
                                <option value="">Select Blood Group</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="religion" class="form-label">Religion</label>
                            <input type="text" class="form-control" id="religion" name="religion">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="nationality" class="form-label">Nationality</label>
                            <input type="text" class="form-control" id="nationality" name="nationality">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2">Academic Information</h5>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="class_id" class="form-label">Class</label>
                            <select class="form-select" id="class_id" name="class_id">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['name'] . ' ' . $class['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="admission_date" class="form-label">Admission Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="admission_date" name="admission_date" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <div class="mb-2">
                                <button type="button" class="btn btn-primary btn-sm" id="startCamera">Take Photo</button>
                                <button type="button" class="btn btn-secondary btn-sm" id="uploadPhoto">Upload Photo</button>
                            </div>
                            <div id="cameraContainer" style="display:none;">
                                <video id="camera" width="320" height="240" class="img-fluid mb-2" style="border:1px solid #ddd;"></video>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-success btn-sm" id="capturePhoto">Capture</button>
                                    <button type="button" class="btn btn-danger btn-sm" id="cancelCapture">Cancel</button>
                                </div>
                            </div>
                            <div id="previewContainer" style="display:none;" class="mb-2">
                                <canvas id="photoPreview" width="320" height="240" class="img-fluid" style="border:1px solid #ddd;"></canvas>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-warning btn-sm" id="retakePhoto">Retake</button>
                                </div>
                            </div>
                            <div id="uploadContainer">
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                <small class="form-text text-muted">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</small>
                            </div>
                            <input type="hidden" id="capturedPhoto" name="capturedPhoto">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2">Guardian Information</h5>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="guardian_name" class="form-label">Guardian Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="guardian_name" name="guardian_name" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="guardian_relation" class="form-label">Relation <span class="text-danger">*</span></label>
                            <select class="form-select" id="guardian_relation" name="guardian_relation" required>
                                <option value="">Select Relation</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Brother">Brother</option>
                                <option value="Sister">Sister</option>
                                <option value="Uncle">Uncle</option>
                                <option value="Aunt">Aunt</option>
                                <option value="Grandfather">Grandfather</option>
                                <option value="Grandmother">Grandmother</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="guardian_occupation" class="form-label">Occupation</label>
                            <input type="text" class="form-control" id="guardian_occupation" name="guardian_occupation">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="guardian_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="guardian_phone" name="guardian_phone" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="guardian_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="guardian_email" name="guardian_email">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="guardian_address" class="form-label">Address</label>
                            <textarea class="form-control" id="guardian_address" name="guardian_address" rows="3"></textarea>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="same_address" checked>
                                <label class="form-check-label" for="same_address">
                                    Same as Student Address
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2">Documents</h5>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="document-container">
                            <div class="document-row mb-3">
                                <div class="row">
                                    <div class="col-md-5">
                                        <select class="form-select" name="document_types[]">
                                            <option value="Birth Certificate">Birth Certificate</option>
                                            <option value="Previous School Records">Previous School Records</option>
                                            <option value="Medical Records">Medical Records</option>
                                            <option value="Transfer Certificate">Transfer Certificate</option>
                                            <option value="ID Proof">ID Proof</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="file" class="form-control" name="documents[]">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-success add-document">
                                            <i class="fas fa-plus"></i> Add More
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="form-text text-muted">Max file size: 5MB. Allowed formats: PDF, JPG, PNG, DOC, DOCX</small>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Register Student
                        </button>
                        <a href="index.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle same address checkbox
        const sameAddressCheckbox = document.getElementById('same_address');
        const addressField = document.getElementById('address');
        const guardianAddressField = document.getElementById('guardian_address');
        
        // Set initial state
        if (sameAddressCheckbox.checked) {
            guardianAddressField.value = addressField.value;
            guardianAddressField.disabled = true;
        }
        
        // Update guardian address when student address changes
        addressField.addEventListener('input', function() {
            if (sameAddressCheckbox.checked) {
                guardianAddressField.value = this.value;
            }
        });
        
        // Toggle guardian address field
        sameAddressCheckbox.addEventListener('change', function() {
            if (this.checked) {
                guardianAddressField.value = addressField.value;
                guardianAddressField.disabled = true;
            } else {
                guardianAddressField.disabled = false;
            }
        });
        
        // Handle document upload
        const addDocumentBtn = document.querySelector('.add-document');
        const documentContainer = document.querySelector('.document-container');
        
        addDocumentBtn.addEventListener('click', function() {
            const documentRow = document.createElement('div');
            documentRow.className = 'document-row mb-3';
            documentRow.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <select class="form-select" name="document_types[]">
                            <option value="Birth Certificate">Birth Certificate</option>
                            <option value="Previous School Records">Previous School Records</option>
                            <option value="Medical Records">Medical Records</option>
                            <option value="Transfer Certificate">Transfer Certificate</option>
                            <option value="ID Proof">ID Proof</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="file" class="form-control" name="documents[]">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-document">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            `;
            
            documentContainer.appendChild(documentRow);
            
            // Add event listener to remove button
            documentRow.querySelector('.remove-document').addEventListener('click', function() {
                documentContainer.removeChild(documentRow);
            });
        });
        
        // Form validation
        const studentForm = document.getElementById('studentForm');
        studentForm.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validate admission number
            const admissionNo = document.getElementById('admission_no').value;
            if (!/^[A-Za-z0-9-\/]+$/.test(admissionNo)) {
                isValid = false;
                alert('Admission Number should contain only letters, numbers, hyphens, and slashes');
            }
            
            // Validate email if provided
            const email = document.getElementById('email').value;
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                isValid = false;
                alert('Please enter a valid email address');
            }
            
            // Validate guardian email if provided
            const guardianEmail = document.getElementById('guardian_email').value;
            if (guardianEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(guardianEmail)) {
                isValid = false;
                alert('Please enter a valid guardian email address');
            }
            
            // Validate phone numbers
            const phone = document.getElementById('phone').value;
            if (phone && !/^[0-9+\-\s()]+$/.test(phone)) {
                isValid = false;
                alert('Please enter a valid phone number');
            }
            
            const guardianPhone = document.getElementById('guardian_phone').value;
            
            // Handle webcam photo if captured
            const capturedPhoto = document.getElementById('capturedPhoto').value;
            if (capturedPhoto) {
                // The photo is already in the hidden field, ready to be submitted
            }
            if (!/^[0-9+\-\s()]+$/.test(guardianPhone)) {
                isValid = false;
                alert('Please enter a valid guardian phone number');
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
        
        // Set default admission date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('admission_date').value = today;
        
        // Webcam functionality
        const startCameraBtn = document.getElementById('startCamera');
        const uploadPhotoBtn = document.getElementById('uploadPhoto');
        const capturePhotoBtn = document.getElementById('capturePhoto');
        const cancelCaptureBtn = document.getElementById('cancelCapture');
        const retakePhotoBtn = document.getElementById('retakePhoto');
        const cameraContainer = document.getElementById('cameraContainer');
        const previewContainer = document.getElementById('previewContainer');
        const uploadContainer = document.getElementById('uploadContainer');
        const videoElement = document.getElementById('camera');
        const canvasElement = document.getElementById('photoPreview');
        const capturedPhotoInput = document.getElementById('capturedPhoto');
        
        let stream = null;
        
        // Start camera
        startCameraBtn.addEventListener('click', async function() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: 320, 
                        height: 240,
                        facingMode: 'user'
                    } 
                });
                videoElement.srcObject = stream;
                videoElement.play();
                
                cameraContainer.style.display = 'block';
                uploadContainer.style.display = 'none';
                previewContainer.style.display = 'none';
            } catch (err) {
                console.error('Error accessing camera:', err);
                alert('Could not access camera. Please make sure you have granted camera permissions.');
            }
        });
        
        // Switch to upload
        uploadPhotoBtn.addEventListener('click', function() {
            stopCamera();
            cameraContainer.style.display = 'none';
            previewContainer.style.display = 'none';
            uploadContainer.style.display = 'block';
            capturedPhotoInput.value = '';
        });
        
        // Capture photo
        capturePhotoBtn.addEventListener('click', function() {
            const context = canvasElement.getContext('2d');
            context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
            
            // Convert to base64 data URL with reduced quality
            const imageData = canvasElement.toDataURL('image/jpeg', 0.7);
            
            // Resize image to ensure it's under 200KB
            resizeImage(imageData, 320, 240, 0.7, function(resizedImage) {
                capturedPhotoInput.value = resizedImage;
                
                cameraContainer.style.display = 'none';
                previewContainer.style.display = 'block';
                
                stopCamera();
            });
        });
        
        // Cancel capture
        cancelCaptureBtn.addEventListener('click', function() {
            stopCamera();
            cameraContainer.style.display = 'none';
            uploadContainer.style.display = 'block';
        });
        
        // Retake photo
        retakePhotoBtn.addEventListener('click', async function() {
            previewContainer.style.display = 'none';
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: 320, 
                        height: 240,
                        facingMode: 'user'
                    } 
                });
                videoElement.srcObject = stream;
                videoElement.play();
                
                cameraContainer.style.display = 'block';
            } catch (err) {
                console.error('Error accessing camera:', err);
                alert('Could not access camera. Please make sure you have granted camera permissions.');
                uploadContainer.style.display = 'block';
            }
        });
        
        // Stop camera function
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }
        
        // Function to resize image to ensure it's under 200KB
        function resizeImage(dataUrl, maxWidth, maxHeight, quality, callback) {
            const img = new Image();
            img.onload = function() {
                let width = img.width;
                let height = img.height;
                
                // Calculate new dimensions while maintaining aspect ratio
                if (width > maxWidth) {
                    height = height * (maxWidth / width);
                    width = maxWidth;
                }
                
                if (height > maxHeight) {
                    width = width * (maxHeight / height);
                    height = maxHeight;
                }
                
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Get resized image as data URL
                let resizedImage = canvas.toDataURL('image/jpeg', quality);
                
                // Check if the size is still too large
                if (resizedImage.length > 200 * 1024) {
                    // Reduce quality further if needed
                    resizeImage(resizedImage, width * 0.9, height * 0.9, quality * 0.9, callback);
                } else {
                    callback(resizedImage);
                }
            };
            img.src = dataUrl;
        }
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>