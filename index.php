
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

// Only set JSON content type for API routes
if (strpos($request_uri, '/api/') === 0 || strpos($request_uri, '/public/') === 0 || strpos($request_uri, '/auth/') === 0 || strpos($request_uri, '/refugio/') === 0 || strpos($request_uri, '/auditor/') === 0 || strpos($request_uri, '/admin/') === 0) {
    header('Content-Type: application/json');
}

$router = new Router();

// Public routes
$router->get('/', function() {
    header('Content-Type: text/html');
    include 'views/landing.php';
});

$router->get('/public/statistics', function() {
    include 'api/public/statistics.php';
});

$router->get('/public/personas', function() {
    include 'api/public/personas.php';
});

$router->get('/public/refugios', function() {
    include 'api/public/refugios.php';
});

$router->get('/public/refugios/{id}/download', function($params) {
    include 'api/public/download.php';
});

// Auth routes
$router->post('/auth/login', function() {
    include 'api/auth/login.php';
});

$router->post('/auth/logout', function() {
    include 'api/auth/logout.php';
});

// Panel routes
$router->get('/panel', function() {
    header('Content-Type: text/html');
    include 'views/panel.php';
});

// Admin routes
$router->get('/admin/users', function() {
    include 'api/admin/users.php';
});

$router->post('/admin/users', function() {
    include 'api/admin/create_user.php';
});

$router->post('/admin/refugios', function() {
    include 'api/admin/create_refugio.php';
});

// Refugio routes
$router->get('/refugio/personas', function() {
    include 'api/refugio/personas.php';
});

$router->post('/refugio/personas', function() {
    include 'api/refugio/create_persona.php';
});

$router->post('/refugio/upload-csv', function() {
    include 'api/refugio/upload_csv.php';
});

$router->put('/refugio/profile', function() {
    include 'api/refugio/update_profile.php';
});

// Auditor routes
$router->get('/auditor/logs', function() {
    include 'api/auditor/logs.php';
});

// Start routing
try {
    $router->route();
} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
