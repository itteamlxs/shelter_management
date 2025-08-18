<?php
/**
 * Private API Endpoints
 * Sistema de Refugios - Authenticated user operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../auth/Session.php';
require_once __DIR__ . '/../models/PersonaModel.php';
require_once __DIR__ . '/../models/RefugioModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/UploadModel.php';

$session = Session::getInstance();

// Check authentication
if (!$session->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$user = $session->getUser();
$pathInfo = $_SERVER['PATH_INFO'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Route requests
    if ($pathInfo === '/dashboard') {
        handleDashboard($user);
    } elseif (str_starts_with($pathInfo, '/refugio/')) {
        handleRefugioOperations($pathInfo, $method, $user);
    } elseif (str_starts_with($pathInfo, '/admin/')) {
        handleAdminOperations($pathInfo, $method, $user);
    } elseif (str_starts_with($pathInfo, '/upload/')) {
        handleUploadOperations($pathInfo, $method, $user);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint no encontrado']);
    }

} catch (Exception $e) {
    error_log("Private API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

/**
 * Handle dashboard data
 */
function handleDashboard($user) {
    $refugioModel = new RefugioModel();
    $personaModel = new PersonaModel();

    $data = [
        'user' => [
            'username' => $user['username'],
            'rol' => $user['rol'],
            'refugio_id' => $user['refugio_id'] ?? null
        ]
    ];

    // Role-specific dashboard data
    switch ($user['rol']) {
        case 'Administrador':
            $data['stats'] = [
                'total_refugios' => $refugioModel->getTotalRefugios(),
                'total_personas' => $personaModel->getTotalPersonas(),
                'personas_activas' => $personaModel->getActivePersonas()
            ];
            $data['recent_uploads'] = getRecentUploads(null, null, 5);
            break;

        case 'Refugio':
            $refugioId = $user['refugio_id'];
            if ($refugioId) {
                $refugio = $refugioModel->getRefugioById($refugioId);
                $data['refugio'] = $refugio;
                $data['stats'] = [
                    'personas_activas' => $personaModel->getPersonasCountByRefugio($refugioId),
                    'ingresos_hoy' => $personaModel->getIngresosHoy($refugioId)
                ];
                $data['recent_uploads'] = getRecentUploads(null, $refugioId, 5);
            }
            break;

        case 'Auditor':
            $data['stats'] = [
                'total_refugios' => $refugioModel->getTotalRefugios(),
                'total_personas' => $personaModel->getTotalPersonas()
            ];
            $data['recent_uploads'] = getRecentUploads(null, null, 10);
            break;
    }

    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Handle refugio operations
 */
function handleRefugioOperations($pathInfo, $method, $user) {
    if ($user['rol'] !== 'Refugio' && $user['rol'] !== 'Administrador') {
        http_response_code(403);
        echo json_encode(['error' => 'Sin permisos para esta operación']);
        return;
    }

    $personaModel = new PersonaModel();
    $refugioId = $user['refugio_id'] ?? $_GET['refugio_id'] ?? null;

    if (!$refugioId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de refugio requerido']);
        return;
    }

    if ($pathInfo === '/refugio/personas') {
        if ($method === 'GET') {
            $search = $_GET['search'] ?? null;
            $limit = min(intval($_GET['limit'] ?? 20), 100);
            $offset = intval($_GET['offset'] ?? 0);

            $result = $personaModel->getPersonasByRefugio($refugioId, $search, $limit, $offset);
            echo json_encode($result);
        }
    }
}

/**
 * Handle admin operations
 */
function handleAdminOperations($pathInfo, $method, $user) {
    if ($user['rol'] !== 'Administrador') {
        http_response_code(403);
        echo json_encode(['error' => 'Operación solo disponible para administradores']);
        return;
    }

    if ($pathInfo === '/admin/users') {
        $userModel = new UserModel();

        if ($method === 'GET') {
            $users = $userModel->getAllUsers();
            echo json_encode(['success' => true, 'data' => $users]);
        }
    }
}

/**
 * Handle upload operations
 */
function handleUploadOperations($pathInfo, $method, $user) {
    $uploadModel = new UploadModel();

    if ($pathInfo === '/upload/history') {
        if ($method === 'GET') {
            $limit = min(intval($_GET['limit'] ?? 20), 100);
            $offset = intval($_GET['offset'] ?? 0);

            // Filter by user role
            $userId = null;
            $refugioId = null;

            if ($user['rol'] === 'Refugio') {
                $refugioId = $user['refugio_id'];
            } elseif ($user['rol'] !== 'Administrador') {
                $userId = $user['id'];
            }

            $uploads = $uploadModel->getUploadHistory($userId, $refugioId, $limit, $offset);
            echo json_encode(['success' => true, 'data' => $uploads]);
        }
    }
}

/**
 * Get recent uploads helper
 */
function getRecentUploads($userId, $refugioId, $limit) {
    $uploadModel = new UploadModel();
    return $uploadModel->getUploadHistory($userId, $refugioId, $limit, 0);
}
?>