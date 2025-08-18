
<?php
/**
 * Public API Endpoints
 * No authentication required
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RefugioModel.php';
require_once __DIR__ . '/../models/PersonaModel.php';

// Parse the request
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/backend/api/public.php', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

// Route handler
try {
    switch ($path) {
        case '/landing':
            if ($method === 'GET') {
                handleLanding();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        case '/personas':
            if ($method === 'GET') {
                handlePersonas();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        case '/statistics':
            if ($method === 'GET') {
                handleStatistics();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        case '/refugios':
            if ($method === 'GET') {
                handleRefugios();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function handleLanding() {
    $refugioModel = new RefugioModel();
    $personaModel = new PersonaModel();
    
    $statistics = $refugioModel->getPublicStatistics();
    $refugios_count = $refugioModel->getAvailableSheltersCount();
    
    $response = [
        'data' => [
            'title' => 'Sistema de Refugios',
            'subtitle' => 'Plataforma para gestión segura de personas albergadas durante desastres',
            'mission' => 'Registrar, gestionar y publicar información no sensible sobre personas albergadas en refugios durante emergencias.',
            'statistics' => $statistics,
            'refugios_disponibles' => $refugios_count,
            'last_updated' => date('Y-m-d H:i:s')
        ],
        'meta' => [
            'version' => '1.0.0',
            'timestamp' => time()
        ]
    ];
    
    echo json_encode($response);
}

function handlePersonas() {
    $personaModel = new PersonaModel();
    
    // Get query parameters
    $search = $_GET['search'] ?? null;
    $refugio = $_GET['refugio'] ?? null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
    
    $offset = ($page - 1) * $per_page;
    
    $result = $personaModel->searchPublicPersonas($search, $refugio, $per_page, $offset);
    
    $response = [
        'data' => $result['data'],
        'meta' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $result['total'],
            'total_pages' => ceil($result['total'] / $per_page),
            'has_next' => $page < ceil($result['total'] / $per_page),
            'has_prev' => $page > 1
        ]
    ];
    
    echo json_encode($response);
}

function handleStatistics() {
    $refugioModel = new RefugioModel();
    $statistics = $refugioModel->getPublicStatistics();
    
    $response = [
        'data' => $statistics,
        'meta' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'cache_ttl' => 300 // 5 minutes
        ]
    ];
    
    echo json_encode($response);
}

function handleRefugios() {
    $refugioModel = new RefugioModel();
    
    // Get query parameters
    $search = $_GET['search'] ?? null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
    
    $offset = ($page - 1) * $per_page;
    
    $result = $refugioModel->searchPublicRefugios($search, $per_page, $offset);
    
    $response = [
        'data' => $result['data'],
        'meta' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $result['total'],
            'total_pages' => ceil($result['total'] / $per_page),
            'has_next' => $page < ceil($result['total'] / $per_page),
            'has_prev' => $page > 1
        ]
    ];
    
    echo json_encode($response);
}
?>
