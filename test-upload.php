<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Upload Test\n";
echo "===========\n\n";

// Check if uploads directory is writable
$upload_dir = __DIR__ . '/uploads/users';
echo "Upload directory: $upload_dir\n";
echo "Directory exists: " . (is_dir($upload_dir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($upload_dir) ? 'YES' : 'NO') . "\n";
echo "Current user: " . get_current_user() . "\n";
echo "PHP user: " . posix_getpwuid(posix_geteuid())['name'] . "\n\n";

// Test file creation
$test_file = $upload_dir . '/test-' . time() . '.txt';
$result = file_put_contents($test_file, 'test');
if ($result !== false) {
    echo "✅ Successfully created test file: $test_file\n";
    unlink($test_file);
} else {
    echo "❌ Failed to create test file\n";
}

echo "\nChecking upload.php function...\n";
require_once './includes/upload.php';
echo "✅ upload.php loaded successfully\n";
