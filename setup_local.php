
<?php
/**
 * Local Development Setup Script for XAMPP
 * Run this after copying files to htdocs/shelter_management/
 */

echo "=== Shelter Management Platform - Local Setup ===\n\n";

// Check PHP version
echo "1. Checking PHP version...\n";
if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
    echo "   ✓ PHP " . PHP_VERSION . " (Compatible)\n\n";
} else {
    echo "   ✗ PHP " . PHP_VERSION . " (Requires 8.0+)\n\n";
    exit(1);
}

// Check required extensions
echo "2. Checking PHP extensions...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ $ext\n";
    } else {
        echo "   ✗ $ext (Required)\n";
    }
}
echo "\n";

// Check if Composer is available
echo "3. Checking Composer...\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✓ Composer dependencies installed\n\n";
} else {
    echo "   ✗ Run 'composer install' first\n\n";
}

// Check .env file
echo "4. Checking configuration...\n";
if (file_exists('.env')) {
    echo "   ✓ .env file exists\n";
    
    // Load and check database config
    $env_content = file_get_contents('.env');
    if (strpos($env_content, 'DB_HOST=localhost') !== false) {
        echo "   ✓ Database configured for localhost\n\n";
    } else {
        echo "   ! Update DB_HOST in .env to 'localhost'\n\n";
    }
} else {
    echo "   ✗ .env file missing\n\n";
}

// Test database connection
echo "5. Testing database connection...\n";
try {
    require_once 'vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    require_once 'config/database.php';
    
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['test'] == 1) {
        echo "   ✓ Database connection successful\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n\n";
}

echo "=== Setup Instructions for XAMPP ===\n";
echo "1. Start Apache and MySQL in XAMPP Control Panel\n";
echo "2. Copy this folder to: C:\\xampp\\htdocs\\shelter_management\\\n";
echo "3. Run: composer install\n";
echo "4. Import storage/schema.sql into phpMyAdmin\n";
echo "5. Access: http://localhost/shelter_management/\n\n";

echo "=== URLs for Testing ===\n";
echo "Main App: http://localhost/shelter_management/\n";
echo "Panel: http://localhost/shelter_management/panel\n";
echo "API Test: http://localhost/shelter_management/public/statistics\n\n";

echo "Setup check completed!\n";
?>
