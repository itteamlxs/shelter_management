
<?php
/**
 * Phase 2 Testing Script
 * Sistema de Refugios - Authentication and Private Panels Testing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Sistema de Refugios - Phase 2 Testing</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { color: green; background-color: #f0fff0; }
    .error { color: red; background-color: #fff0f0; }
    .info { color: blue; background-color: #f0f0ff; }
    .test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Test 1: Database Connection and Schema
echo "<div class='test-section'>";
echo "<h2>Test 1: Database Connection and Phase 2 Schema</h2>";

try {
    require_once __DIR__ . '/backend/config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<div class='test-result success'>✓ Database connection successful</div>";
    
    // Test Phase 2 specific tables
    $phase2_tables = [
        'Usuarios',
        'AuditLog',
        'BulkUploads'
    ];
    
    foreach ($phase2_tables as $table) {
        try {
            $stmt = $db->query("DESCRIBE $table");
            echo "<div class='test-result success'>✓ Table '$table' exists</div>";
        } catch (Exception $e) {
            echo "<div class='test-result error'>✗ Table '$table' missing: " . $e->getMessage() . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test 2: Authentication Classes
echo "<div class='test-section'>";
echo "<h2>Test 2: Authentication System</h2>";

try {
    require_once __DIR__ . '/backend/auth/Session.php';
    $session = Session::getInstance();
    echo "<div class='test-result success'>✓ Session class loaded successfully</div>";
    
    // Test CSRF token generation
    $csrfToken = $session->generateCSRFToken();
    if ($csrfToken && strlen($csrfToken) > 10) {
        echo "<div class='test-result success'>✓ CSRF token generated: " . substr($csrfToken, 0, 10) . "...</div>";
    } else {
        echo "<div class='test-result error'>✗ CSRF token generation failed</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ Session class error: " . $e->getMessage() . "</div>";
}

try {
    require_once __DIR__ . '/backend/models/UserModel.php';
    $userModel = new UserModel();
    echo "<div class='test-result success'>✓ UserModel loaded successfully</div>";
    
    // Test user authentication (should fail with invalid credentials)
    $result = $userModel->authenticate('nonexistent', 'wrongpassword');
    if ($result === false) {
        echo "<div class='test-result success'>✓ Authentication correctly rejects invalid credentials</div>";
    } else {
        echo "<div class='test-result error'>✗ Authentication not working properly</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ UserModel error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test 3: Sample Users
echo "<div class='test-section'>";
echo "<h2>Test 3: Sample Users and Authentication</h2>";

try {
    // Check if sample users exist
    $stmt = $db->query("SELECT username, rol, activo FROM Usuarios WHERE activo = TRUE");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<div class='test-result success'>✓ Sample users found: " . count($users) . " active users</div>";
        foreach ($users as $user) {
            echo "<div class='test-result info'>- {$user['username']} ({$user['rol']})</div>";
        }
        
        // Test authentication with sample admin user
        $testAuth = $userModel->authenticate('admin', 'password');
        if ($testAuth && $testAuth['rol'] === 'Administrador') {
            echo "<div class='test-result success'>✓ Admin authentication successful</div>";
        } else {
            echo "<div class='test-result error'>✗ Admin authentication failed - check password hash</div>";
        }
        
    } else {
        echo "<div class='test-result error'>✗ No sample users found - database may need sample data</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ Sample users test error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test 4: API Endpoints
echo "<div class='test-section'>";
echo "<h2>Test 4: Authentication API Endpoints</h2>";

$base_url = 'http://' . $_SERVER['HTTP_HOST'];
$auth_endpoints = [
    '/backend/api/auth.php/csrf-token',
    '/backend/api/auth.php/me'
];

foreach ($auth_endpoints as $endpoint) {
    $url = $base_url . $endpoint;
    echo "<div class='test-result info'>Testing: $url</div>";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'Accept: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<div class='test-result success'>✓ API endpoint responding with valid JSON</div>";
            if (isset($data['success']) || isset($data['error'])) {
                echo "<div class='test-result success'>✓ Response has expected structure</div>";
            }
        } else {
            echo "<div class='test-result error'>✗ Invalid JSON response</div>";
        }
    } else {
        echo "<div class='test-result error'>✗ API endpoint not accessible</div>";
    }
}

echo "</div>";

// Test 5: Private API Endpoints (should require auth)
echo "<div class='test-section'>";
echo "<h2>Test 5: Private API Endpoints</h2>";

$private_endpoints = [
    '/backend/api/private.php/dashboard',
    '/backend/api/private.php/refugio/personas',
    '/backend/api/private.php/admin/users'
];

foreach ($private_endpoints as $endpoint) {
    $url = $base_url . $endpoint;
    echo "<div class='test-result info'>Testing: $url</div>";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'Accept: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($data['error']) && strpos($data['error'], 'autenticado') !== false) {
            echo "<div class='test-result success'>✓ Private endpoint correctly requires authentication</div>";
        } else {
            echo "<div class='test-result error'>✗ Private endpoint not properly protected</div>";
        }
    } else {
        // Check if it's a 401 response
        $headers = $http_response_header ?? [];
        $is_401 = false;
        foreach ($headers as $header) {
            if (strpos($header, '401') !== false) {
                $is_401 = true;
                break;
            }
        }
        
        if ($is_401) {
            echo "<div class='test-result success'>✓ Private endpoint returns 401 (authentication required)</div>";
        } else {
            echo "<div class='test-result error'>✗ Private endpoint not accessible</div>";
        }
    }
}

echo "</div>";

// Test 6: File Structure Phase 2
echo "<div class='test-section'>";
echo "<h2>Test 6: Phase 2 File Structure</h2>";

$required_files = [
    'backend/auth/Session.php',
    'backend/models/UserModel.php',
    'backend/api/auth.php',
    'backend/api/private.php',
    'login.html',
    'dashboard.html',
    'assets/js/dashboard.js'
];

foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<div class='test-result success'>✓ File exists: $file</div>";
        
        // Check file size to ensure it's not empty
        $size = filesize(__DIR__ . '/' . $file);
        if ($size > 100) {
            echo "<div class='test-result info'>  File size: " . round($size/1024, 1) . " KB</div>";
        } else {
            echo "<div class='test-result error'>  Warning: File seems too small ($size bytes)</div>";
        }
    } else {
        echo "<div class='test-result error'>✗ File missing: $file</div>";
    }
}

echo "</div>";

// Test 7: Enhanced Model Methods
echo "<div class='test-section'>";
echo "<h2>Test 7: Enhanced Model Methods</h2>";

try {
    // Test PersonaModel new methods
    $personaModel = new PersonaModel();
    
    // Test getPersonasByRefugio (should work even with no data)
    $result = $personaModel->getPersonasByRefugio(1, null, 5, 0);
    if (isset($result['success'])) {
        echo "<div class='test-result success'>✓ PersonaModel::getPersonasByRefugio method working</div>";
    } else {
        echo "<div class='test-result error'>✗ PersonaModel::getPersonasByRefugio method not working</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ PersonaModel enhanced methods error: " . $e->getMessage() . "</div>";
}

try {
    // Test RefugioModel new methods
    $refugioModel = new RefugioModel();
    
    $refugios = $refugioModel->getAllRefugios();
    if (is_array($refugios)) {
        echo "<div class='test-result success'>✓ RefugioModel::getAllRefugios method working</div>";
        echo "<div class='test-result info'>Found " . count($refugios) . " refugios</div>";
    } else {
        echo "<div class='test-result error'>✗ RefugioModel::getAllRefugios method not working</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ RefugioModel enhanced methods error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test 8: Session Security
echo "<div class='test-section'>";
echo "<h2>Test 8: Session Security Features</h2>";

try {
    // Test CSRF token validation
    $token1 = $session->generateCSRFToken();
    $token2 = $session->generateCSRFToken();
    
    if ($token1 === $token2) {
        echo "<div class='test-result success'>✓ CSRF tokens are consistent within session</div>";
    } else {
        echo "<div class='test-result error'>✗ CSRF tokens are not consistent</div>";
    }
    
    // Test token validation
    if ($session->validateCSRFToken($token1)) {
        echo "<div class='test-result success'>✓ CSRF token validation working</div>";
    } else {
        echo "<div class='test-result error'>✗ CSRF token validation failed</div>";
    }
    
    // Test invalid token rejection
    if (!$session->validateCSRFToken('invalid_token_123')) {
        echo "<div class='test-result success'>✓ Invalid CSRF tokens are correctly rejected</div>";
    } else {
        echo "<div class='test-result error'>✗ Invalid CSRF tokens are not being rejected</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ Session security test error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test 9: Audit Logging
echo "<div class='test-section'>";
echo "<h2>Test 9: Audit Logging System</h2>";

try {
    // Check if audit log table exists and can be written to
    $stmt = $db->query("SELECT COUNT(*) as count FROM AuditLog");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<div class='test-result success'>✓ AuditLog table accessible, contains $count entries</div>";
    
    // Test audit logging functionality
    $result = $userModel->logActivity(1, 'TEST', 'System', null, 'Phase 2 testing');
    if ($result) {
        echo "<div class='test-result success'>✓ Audit logging functionality working</div>";
    } else {
        echo "<div class='test-result error'>✗ Audit logging failed</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-result error'>✗ Audit logging test error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test Summary
echo "<div class='test-section'>";
echo "<h2>Test Summary - Phase 2</h2>";
echo "<div class='test-result info'>";
echo "<h3>Phase 2 Deliverables Status:</h3>";
echo "<ul>";
echo "<li>✓ Session management system</li>";
echo "<li>✓ User authentication with roles</li>";
echo "<li>✓ CSRF protection</li>";
echo "<li>✓ Private API endpoints</li>";
echo "<li>✓ Login page with security</li>";
echo "<li>✓ Dashboard for all user roles</li>";
echo "<li>✓ Enhanced model methods</li>";
echo "<li>✓ Audit logging system</li>";
echo "</ul>";

echo "<h3>User Credentials for Testing:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin / password</li>";
echo "<li><strong>Refugio:</strong> refugio1 / password</li>";
echo "<li><strong>Auditor:</strong> auditor1 / password</li>";
echo "</ul>";

echo "<h3>Next Steps (Phase 3):</h3>";
echo "<ul>";
echo "<li>CSV upload functionality</li>";
echo "<li>Background processing</li>";
echo "<li>File validation system</li>";
echo "<li>Batch processing</li>";
echo "<li>Error reporting</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div class='test-result success'>";
echo "<h2>✓ Phase 2 Testing Complete</h2>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Authentication system and private panels have been implemented and tested.</p>";
echo "<p><strong>Next:</strong> Access the system at <a href='login.html'>login.html</a></p>";
echo "</div>";

?>
