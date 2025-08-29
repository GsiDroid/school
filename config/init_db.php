<?php
/**
 * Database Initialization Script
 * This script creates the database and tables if they don't exist
 */

require_once __DIR__ . '/database.php';

try {
    // Create database if it doesn't exist
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "<p>Database created or already exists.</p>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/../database/school_management_no_triggers.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL file into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)), 'strlen');
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (strpos($statement, 'DELIMITER') !== false) {
                // Skip DELIMITER statements as they're MySQL client specific
                continue;
            }
            
            // Handle triggers separately
            if (strpos($statement, 'CREATE TRIGGER') !== false) {
                try {
                    $pdo->exec($statement);
                    echo "<p>Trigger created successfully.</p>";
                } catch (PDOException $e) {
                    // Ignore if trigger already exists
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "<p>Trigger already exists.</p>";
                    } else {
                        echo "<p>Error creating trigger: " . $e->getMessage() . "</p>";
                    }
                }
            } else {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignore if table already exists
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "<p>Table already exists.</p>";
                    } else {
                        echo "<p>Error executing SQL: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
        
        echo "<p>Database schema created successfully.</p>";
        
        // Check if admin user exists, if not create default admin
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            // Create default admin user
            $name = 'System Administrator';
            $email = 'admin@schoolms.com';
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $role = 'admin';
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role]);
            
            echo "<p>Default admin user created with email: admin@schoolms.com and password: admin123</p>";
            echo "<p><strong>Please change the default password after first login!</strong></p>";
        }
        
    } else {
        echo "<p>SQL file not found: $sqlFile</p>";
    }
    
} catch (PDOException $e) {
    die("<p>Database initialization failed: " . $e->getMessage() . "</p>");
}

echo "<p>Database initialization completed.</p>";
?>