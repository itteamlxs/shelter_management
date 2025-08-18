
<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    echo "Testing database connection...\n";
    
    $db = Database::getInstance();
    
    // Test basic connection
    $stmt = $db->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['test'] == 1) {
        echo "✓ Database connection successful\n";
    }
    
    // Test views
    echo "\nTesting views...\n";
    
    $views = [
        'vw_public_estadisticas',
        'vw_public_personas',
        'vw_public_refugios'
    ];
    
    foreach ($views as $view) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $view");
            $stmt->execute();
            $result = $stmt->fetch();
            echo "✓ View $view: {$result['count']} records\n";
        } catch (Exception $e) {
            echo "✗ View $view: Error - " . $e->getMessage() . "\n";
        }
    }
    
    // Test stored procedures
    echo "\nTesting stored procedures...\n";
    
    try {
        $stmt = $db->prepare("CALL sp_estadisticas_refugio(1)");
        $stmt->execute();
        echo "✓ Stored procedure sp_estadisticas_refugio working\n";
    } catch (Exception $e) {
        echo "✗ Stored procedure error: " . $e->getMessage() . "\n";
    }
    
    echo "\nDatabase test completed!\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
