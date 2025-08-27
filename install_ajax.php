<?php
/**
 * AJAX Handler for Installation Process
 */

header('Content-Type: application/json');

// Prevent direct access
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

$action = $_GET['action'] ?? $_POST['action'];

switch ($action) {
    case 'check_requirements':
        checkRequirements();
        break;
    case 'test_db':
        testDatabase();
        break;
    case 'create_admin':
        createAdmin();
        break;
    case 'install':
        installSystem();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function checkRequirements() {
    $checks = [];
    
    // Check PHP version
    $checks[] = [
        'name' => 'PHP Version (>= 7.4)',
        'passed' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'message' => 'Current: ' . PHP_VERSION
    ];
    
    // Check required extensions
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'fileinfo'];
    foreach ($required_extensions as $ext) {
        $checks[] = [
            'name' => 'PHP Extension: ' . $ext,
            'passed' => extension_loaded($ext),
            'message' => extension_loaded($ext) ? 'Loaded' : 'Not loaded'
        ];
    }
    
    // Check directory permissions
    $directories = ['uploads', 'uploads/documents', 'uploads/images', 'uploads/receipts', 'assets/img'];
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $checks[] = [
            'name' => 'Directory: ' . $dir,
            'passed' => is_writable($dir),
            'message' => is_writable($dir) ? 'Writable' : 'Not writable'
        ];
    }
    
    // Check if config directory is writable
    $checks[] = [
        'name' => 'Config Directory',
        'passed' => is_writable('config'),
        'message' => is_writable('config') ? 'Writable' : 'Not writable'
    ];
    
    echo json_encode(['success' => true, 'checks' => $checks]);
}

function testDatabase() {
    $host = $_POST['db_host'] ?? 'localhost';
    $name = $_POST['db_name'] ?? 'school_management';
    $user = $_POST['db_user'] ?? 'root';
    $pass = $_POST['db_pass'] ?? '';
    
    try {
        // Test connection without database
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Test connection with database
        $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo json_encode(['success' => true, 'message' => 'Database connection successful']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    }
}

function createAdmin() {
    $name = $_POST['admin_name'] ?? '';
    $email = $_POST['admin_email'] ?? '';
    $password = $_POST['admin_password'] ?? '';
    $confirm_password = $_POST['admin_confirm_password'] ?? '';
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        return;
    }
    
    echo json_encode(['success' => true, 'message' => 'Admin account validation successful']);
}

function installSystem() {
    $host = $_POST['db_host'] ?? 'localhost';
    $name = $_POST['db_name'] ?? 'school_management';
    $user = $_POST['db_user'] ?? 'root';
    $pass = $_POST['db_pass'] ?? '';
    $admin_name = $_POST['admin_name'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    
    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute SQL file
        $sqlFile = 'database/school_management_fixed.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Split SQL file into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)), 'strlen');
            
            // Execute each statement
            foreach ($statements as $statement) {
                if (strpos($statement, 'DELIMITER') !== false) {
                    continue; // Skip DELIMITER statements
                }
                
                // Handle triggers separately
                if (strpos($statement, 'CREATE TRIGGER') !== false) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore if trigger already exists
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                } else {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore if table already exists
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
            }
        }
        
        // Create admin user
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$admin_name, $admin_email, $hashed_password]);
        
        // Create default classes
        $default_classes = [
            ['name' => 'Class 1', 'section' => 'A'],
            ['name' => 'Class 1', 'section' => 'B'],
            ['name' => 'Class 2', 'section' => 'A'],
            ['name' => 'Class 2', 'section' => 'B'],
            ['name' => 'Class 3', 'section' => 'A'],
            ['name' => 'Class 3', 'section' => 'B'],
            ['name' => 'Class 4', 'section' => 'A'],
            ['name' => 'Class 4', 'section' => 'B'],
            ['name' => 'Class 5', 'section' => 'A'],
            ['name' => 'Class 5', 'section' => 'B']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO classes (name, section) VALUES (?, ?)");
        foreach ($default_classes as $class) {
            try {
                $stmt->execute([$class['name'], $class['section']]);
            } catch (PDOException $e) {
                // Ignore if already exists
            }
        }
        
        // Create default subjects
        $default_subjects = [
            'Mathematics', 'English', 'Science', 'Social Studies', 
            'Computer Science', 'Physical Education', 'Art', 'Music'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        foreach ($default_subjects as $subject) {
            try {
                $stmt->execute([$subject]);
            } catch (PDOException $e) {
                // Ignore if already exists
            }
        }
        
        // Create default fee categories
        $default_categories = [
            'Tuition Fee', 'Transportation Fee', 'Library Fee', 'Laboratory Fee', 'Sports Fee'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO fee_categories (name) VALUES (?)");
        foreach ($default_categories as $category) {
            try {
                $stmt->execute([$category]);
            } catch (PDOException $e) {
                // Ignore if already exists
            }
        }
        
        // Create default expense categories
        $default_expense_categories = [
            'Salaries', 'Utilities', 'Maintenance', 'Supplies', 'Transportation', 'Other'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO expense_categories (name) VALUES (?)");
        foreach ($default_expense_categories as $category) {
            try {
                $stmt->execute([$category]);
            } catch (PDOException $e) {
                // Ignore if already exists
            }
        }
        
        // Create default gallery categories
        $default_gallery_categories = [
            'Events', 'Sports', 'Academics', 'Cultural', 'Other'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO gallery_categories (name) VALUES (?)");
        foreach ($default_gallery_categories as $category) {
            try {
                $stmt->execute([$category]);
            } catch (PDOException $e) {
                // Ignore if already exists
            }
        }
        
        // Update database configuration file
        updateDatabaseConfig($host, $name, $user, $pass);
        
        // Create installation lock file
        file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
        
        echo json_encode(['success' => true, 'message' => 'Installation completed successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Installation failed: ' . $e->getMessage()]);
    }
}

function updateDatabaseConfig($host, $name, $user, $pass) {
    $config_content = "<?php
/**
 * Database Configuration File
 * 
 * This file establishes a connection to the MySQL database using PDO
 * for secure database interactions as specified in the requirements.
 */

// Database credentials
define('DB_HOST', '$host');
define('DB_NAME', '$name');
define('DB_USER', '$user'); // Change in production
define('DB_PASS', '$pass');     // Change in production

/**
 * Database Class for handling database connections
 */
class Database {
    private \$host = DB_HOST;
    private \$dbname = DB_NAME;
    private \$username = DB_USER;
    private \$password = DB_PASS;
    private \$conn;
    
    /**
     * Get database connection
     * @return PDO Database connection object
     */
    public function getConnection() {
        \$this->conn = null;
        
        try {
            // Set error mode
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Create a new PDO instance
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->dbname . \";charset=utf8mb4\",
                \$this->username,
                \$this->password,
                \$options
            );
        } catch (PDOException \$e) {
            // Log error and display user-friendly message
            error_log(\"Database Connection Error: \" . \$e->getMessage());
            die(\"Connection failed: The system is currently unavailable. Please try again later.\");
        }
        
        return \$this->conn;
    }
}";
    
    file_put_contents('config/database.php', $config_content);
}
?>
