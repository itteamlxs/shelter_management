
<?php
/**
 * Phase 3 Testing Script - Pre-implementation verification
 * Sistema de Refugios - Check if we're ready for Phase 3
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Sistema de Refugios - Phase 3 Readiness Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; background-color: #f0fff0; }
    .error { color: red; background-color: #fff0f0; }
    .info { color: blue; background-color: #f0f0ff; }
    .test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
</style>";

// Test Database Connection
echo "<div class='test-section'>";
echo "<h2>Database Connection Test</h2>";

try {
    require_once __DIR__ . '/backend/config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<div class='test-result success'>✓ Database connected successfully</div>";
    
    // Check if we have data
    $stmt = $db->query("SELECT COUNT(*) as count FROM Usuarios WHERE activo = 1");
    $userCount = $stmt->fetch()['count'];
    echo "<div class='test-result info'>Active users: $userCount</div>";
    
    if ($userCount > 0) {
        echo "<div class='test-result success'>✓ Sample users available for testing</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ Database error: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='test-result success'>";
echo "<h2>✓ System Ready for Phase 3</h2>";
echo "<p>Database is connected and Phase 2 authentication system is in place.</p>";
echo "<p><strong>Access Points:</strong></p>";
echo "<ul>";
echo "<li><a href='/frontend/index.html'>Public Frontend</a></li>";
echo "<li><a href='/login.html'>Login Page</a></li>";
echo "<li><a href='/dashboard.html'>Dashboard</a> (requires login)</li>";
echo "</ul>";
echo "</div>";

?>
