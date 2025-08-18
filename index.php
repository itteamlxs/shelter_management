
<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/router.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set up error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Handle static files first
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path for XAMPP subdirectory installations
$script_name = dirname($_SERVER['SCRIPT_NAME']);
if ($script_name !== '/' && strpos($request_uri, $script_name) === 0) {
    $request_uri = substr($request_uri, strlen($script_name));
}

// Serve static assets
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/', $request_uri)) {
    $file_path = __DIR__ . $request_uri;
    if (file_exists($file_path)) {
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml'
        ];
        
        header('Content-Type: ' . ($mime_types[$ext] ?? 'application/octet-stream'));
        readfile($file_path);
        exit;
    } else {
        http_response_code(404);
        echo "File not found";
        exit;
    }
}

// CORS headers for API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}

header('Access-Control-Allow-Origin: *');

// Get clean path for API detection
$clean_path = $request_uri;
$script_name = dirname($_SERVER['SCRIPT_NAME']);
if ($script_name !== '/' && strpos($clean_path, $script_name) === 0) {
    $clean_path = substr($clean_path, strlen($script_name));
}

$router = new Router();

// Public routes
$router->get('/', function() {
    header('Content-Type: text/html');
    include 'views/landing.php';
});

$router->get('/public/statistics', function() {
    header('Content-Type: application/json');
    include 'api/public/statistics.php';
});

$router->get('/public/personas', function() {
    header('Content-Type: application/json');
    include 'api/public/personas.php';
});

$router->get('/public/refugios', function() {
    header('Content-Type: application/json');
    include 'api/public/refugios.php';
});

$router->get('/public/refugios/{id}/download', function($params) {
    include 'api/public/download.php';
});

// Auth routes
$router->get('/login', function() {
    header('Content-Type: text/html');
    include 'views/login.php';
});

$router->post('/auth/login', function() {
    header('Content-Type: application/json');
    include 'api/auth/login.php';
});

$router->post('/auth/logout', function() {
    header('Content-Type: application/json');
    include 'api/auth/logout.php';
});

// Protected routes - require authentication
$router->get('/dashboard', function() {
    // Check if user is authenticated
    $user = Auth::getCurrentUser();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    header('Content-Type: text/html');
    include 'views/dashboard.php';
});

$router->get('/panel', function() {
    // Redirect panel to dashboard for backward compatibility
    header('Location: /dashboard');
    exit;
});

// Admin routes - require admin authentication
$router->get('/admin/users', function() {
    header('Content-Type: application/json');
    include 'api/admin/users.php';
});

$router->post('/admin/users', function() {
    header('Content-Type: application/json');
    include 'api/admin/create_user.php';
});

$router->post('/admin/refugios', function() {
    header('Content-Type: application/json');
    include 'api/admin/create_refugio.php';
});

// Refugio routes - require refugio authentication
$router->get('/refugio/personas', function() {
    header('Content-Type: application/json');
    include 'api/refugio/personas.php';
});

$router->post('/refugio/personas', function() {
    header('Content-Type: application/json');
    include 'api/refugio/create_persona.php';
});

$router->post('/refugio/upload-csv', function() {
    header('Content-Type: application/json');
    include 'api/refugio/upload_csv.php';
});

$router->put('/refugio/profile', function() {
    header('Content-Type: application/json');
    include 'api/refugio/update_profile.php';
});

// Auditor routes - require auditor authentication
$router->get('/auditor/logs', function() {
    header('Content-Type: application/json');
    include 'api/auditor/logs.php';
});

// Start routing
try {
    $router->route();
} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    http_response_code(500);
    if (strpos($clean_path, '/api/') === 0 || strpos($clean_path, '/auth/') === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Internal server error']);
    } else {
        echo "Internal server error";
    }
}
