<?php
/**
 * Database Connection Update Script
 * This script updates all PHP files in the project to use the functions.php file for database connection
 */

// Define the root directory
$root_dir = __DIR__;

// Counter for updated files
$updated_files = 0;

// Function to recursively scan directories
function scan_directory($dir) {
    global $updated_files;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'update_db_connections.php') {
            continue;
        }
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            // Skip vendor directory if it exists
            if (basename($path) === 'vendor') {
                continue;
            }
            scan_directory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            update_file($path);
        }
    }
}

// Function to update database connection in a file
function update_file($file_path) {
    global $updated_files;
    
    $content = file_get_contents($file_path);
    $original_content = $content;
    
    // Skip files that already include functions.php
    if (strpos($content, "require_once '../includes/functions.php'") !== false ||
        strpos($content, "require_once 'includes/functions.php'") !== false) {
        return;
    }
    
    // Pattern 1: Replace database connection code with functions.php
    $pattern1 = "/\/\/ Include database connection\s*require_once ['\"](.+?)['\"];\s*\/\/ Get database connection\s*\$db = new Database\(\);\s*\$conn = \$db->getConnection\(\);/s";
    
    // Determine the correct path to functions.php
    $relative_path = get_relative_path($file_path);
    
    if (strpos($file_path, DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR) !== false) {
        // If the file is in the includes directory
        $replacement1 = "// Include common functions\nrequire_once 'functions.php';\n\n// Get database connection\n\$conn = get_db_connection();";
    } elseif (dirname($file_path) === $GLOBALS['root_dir']) {
        // If the file is in the root directory
        $replacement1 = "// Include common functions\nrequire_once 'includes/functions.php';\n\n// Get database connection\n\$conn = get_db_connection();";
    } else {
        // For files in subdirectories
        $replacement1 = "// Include common functions\nrequire_once '{$relative_path}includes/functions.php';\n\n// Get database connection\n\$conn = get_db_connection();";
    }
    
    $content = preg_replace($pattern1, $replacement1, $content);
    
    // Pattern 2: Replace only the database connection include
    $pattern2 = "/\/\/ Include database connection\s*require_once ['\"](.+?)['\"];/s";
    
    if (strpos($file_path, DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR) !== false) {
        $replacement2 = "// Include common functions\nrequire_once 'functions.php';";
    } elseif (dirname($file_path) === $GLOBALS['root_dir']) {
        $replacement2 = "// Include common functions\nrequire_once 'includes/functions.php';";
    } else {
        $replacement2 = "// Include common functions\nrequire_once '{$relative_path}includes/functions.php';";
    }
    
    $content = preg_replace($pattern2, $replacement2, $content);
    
    // If the content was changed, write it back to the file
    if ($content !== $original_content) {
        file_put_contents($file_path, $content);
        echo "Updated: {$file_path}\n";
        $updated_files++;
    }
}

// Function to get the relative path to the root directory
function get_relative_path($file_path) {
    $file_dir = dirname($file_path);
    $root_dir = $GLOBALS['root_dir'];
    
    if ($file_dir === $root_dir) {
        return '';
    }
    
    $relative_path = '';
    $path_parts = explode(DIRECTORY_SEPARATOR, str_replace($root_dir . DIRECTORY_SEPARATOR, '', $file_dir));
    
    foreach ($path_parts as $part) {
        $relative_path .= '../';
    }
    
    return $relative_path;
}

// Start the update process
echo "Starting database connection update...\n";
scan_directory($root_dir);
echo "Update completed. {$updated_files} files were updated.\n";