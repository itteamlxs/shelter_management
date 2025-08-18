
<?php

require_once __DIR__ . '/../auth/Session.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/RefugioModel.php';
require_once __DIR__ . '/../models/PersonaModel.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$session = Session::getInstance();
$userModel = new UserModel();
$refugioModel = new RefugioModel();
$personaModel = new PersonaModel();

// Check authentication
if (!$session->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = trim($_SERVER['PATH_INFO'] ?? '', '/');
$user = $session->getUser();

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($path, $user);
            break;
        case 'POST':
            handlePostRequest($path, $user);
            break;
        case 'PUT':
            handlePutRequest($path, $user);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    error_log("Private API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

function handleGetRequest($path, $user) {
    global $refugioModel, $personaModel, $userModel;
    
    $pathParts = explode('/', $path);
    
    switch ($pathParts[0]) {
        case 'dashboard':
            handleDashboard($user);
            break;
            
        case 'refugio':
            if (!$session->checkRole(['Refugio', 'Administrador'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Sin permisos']);
                return;
            }
            
            if (isset($pathParts[1])) {
                switch ($pathParts[1]) {
                    case 'personas':
                        $refugioId = $user['rol'] === 'Refugio' ? $user['refugio_id'] : ($_GET['refugio_id'] ?? null);
                        if (!$refugioId) {
                            http_response_code(400);
                            echo json_encode(['error' => 'refugio_id requerido']);
                            return;
                        }
                        
                        $search = $_GET['search'] ?? null;
                        $page = (int)($_GET['page'] ?? 1);
                        $perPage = min((int)($_GET['per_page'] ?? 20), 100);
                        
                        $result = $personaModel->getPersonasByRefugio($refugioId, $search, $perPage, ($page - 1) * $perPage);
                        echo json_encode($result);
                        break;
                        
                    case 'stats':
                        $refugioId = $user['rol'] === 'Refugio' ? $user['refugio_id'] : ($_GET['refugio_id'] ?? null);
                        if (!$refugioId) {
                            http_response_code(400);
                            echo json_encode(['error' => 'refugio_id requerido']);
                            return;
                        }
                        
                        $stats = $refugioModel->getRefugioStats($refugioId);
                        echo json_encode(['success' => true, 'data' => $stats]);
                        break;
                }
            }
            break;
            
        case 'admin':
            if (!$session->checkRole('Administrador')) {
                http_response_code(403);
                echo json_encode(['error' => 'Sin permisos de administrador']);
                return;
            }
            
            if (isset($pathParts[1])) {
                switch ($pathParts[1]) {
                    case 'users':
                        $users = $userModel->getAllUsers();
                        echo json_encode(['success' => true, 'data' => $users]);
                        break;
                        
                    case 'refugios':
                        $refugios = $refugioModel->getAllRefugios();
                        echo json_encode(['success' => true, 'data' => $refugios]);
                        break;
                }
            }
            break;
            
        case 'auditor':
            if (!$session->checkRole(['Auditor', 'Administrador'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Sin permisos de auditor']);
                return;
            }
            
            if (isset($pathParts[1]) && $pathParts[1] === 'logs') {
                $page = (int)($_GET['page'] ?? 1);
                $perPage = min((int)($_GET['per_page'] ?? 50), 100);
                $offset = ($page - 1) * $perPage;
                
                $logs = getAuditLogs($perPage, $offset);
                echo json_encode(['success' => true, 'data' => $logs]);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
    }
}

function handlePostRequest($path, $user) {
    global $session, $personaModel, $refugioModel, $userModel;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // CSRF validation
    if (!isset($input['csrf_token']) || !$session->validateCSRFToken($input['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Token CSRF inválido']);
        return;
    }
    
    $pathParts = explode('/', $path);
    
    switch ($pathParts[0]) {
        case 'refugio':
            if (!$session->checkRole(['Refugio', 'Administrador'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Sin permisos']);
                return;
            }
            
            if (isset($pathParts[1]) && $pathParts[1] === 'register-person') {
                handleRegisterPerson($input, $user);
            }
            break;
            
        case 'admin':
            if (!$session->checkRole('Administrador')) {
                http_response_code(403);
                echo json_encode(['error' => 'Sin permisos de administrador']);
                return;
            }
            
            if (isset($pathParts[1])) {
                switch ($pathParts[1]) {
                    case 'create-user':
                        handleCreateUser($input, $user);
                        break;
                        
                    case 'create-refugio':
                        handleCreateRefugio($input, $user);
                        break;
                }
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
    }
}

function handlePutRequest($path, $user) {
    // Handle PUT requests for updates
    http_response_code(501);
    echo json_encode(['error' => 'Funcionalidad no implementada aún']);
}

function handleDashboard($user) {
    global $refugioModel, $personaModel;
    
    $data = [];
    
    switch ($user['rol']) {
        case 'Refugio':
            if ($user['refugio_id']) {
                $data['refugio_stats'] = $refugioModel->getRefugioStats($user['refugio_id']);
                $data['recent_persons'] = $personaModel->getPersonasByRefugio($user['refugio_id'], null, 5, 0);
            }
            break;
            
        case 'Administrador':
            $data['global_stats'] = $refugioModel->getPublicStatistics();
            $data['refugios_overview'] = $refugioModel->getAllRefugios();
            break;
            
        case 'Auditor':
            $data['recent_activity'] = getAuditLogs(10, 0);
            break;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function handleRegisterPerson($input, $user) {
    global $personaModel, $userModel;
    
    // Validate required fields
    $required = ['nombre_preferido', 'edad_rango', 'genero', 'fecha_ingreso', 'hora_ingreso', 'area_asignada'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    $refugioId = $user['rol'] === 'Refugio' ? $user['refugio_id'] : $input['refugio_id'];
    if (!$refugioId) {
        http_response_code(400);
        echo json_encode(['error' => 'refugio_id requerido']);
        return;
    }
    
    try {
        $personaId = $personaModel->createPersona($input);
        if ($personaId) {
            $result = $personaModel->registerInRefugio(
                $personaId,
                $refugioId,
                $input['fecha_ingreso'],
                $input['hora_ingreso'],
                $input['area_asignada'],
                $input['estatus'] ?? 'Alojado',
                $input['observaciones'] ?? null,
                $user['user_id']
            );
            
            if ($result) {
                $userModel->logActivity(
                    $user['user_id'], 
                    'CREATE', 
                    'Persona', 
                    $personaId, 
                    "Registro de nueva persona: {$input['nombre_preferido']}"
                );
                
                echo json_encode(['success' => true, 'persona_id' => $personaId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al registrar en refugio']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear persona']);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleCreateUser($input, $user) {
    global $userModel;
    
    $required = ['username', 'password', 'rol', 'nombre_mostrado'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    $result = $userModel->createUser(
        $input['username'],
        $input['password'],
        $input['rol'],
        $input['refugio_id'] ?? null,
        $input['nombre_mostrado']
    );
    
    if ($result) {
        $userModel->logActivity(
            $user['user_id'], 
            'CREATE', 
            'Usuario', 
            null, 
            "Creación de usuario: {$input['username']}"
        );
        
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear usuario']);
    }
}

function handleCreateRefugio($input, $user) {
    global $refugioModel, $userModel;
    
    $required = ['nombre_refugio', 'ubicacion', 'capacidad_maxima', 'fecha_apertura'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    $refugioId = $refugioModel->createRefugio($input);
    
    if ($refugioId) {
        $userModel->logActivity(
            $user['user_id'], 
            'CREATE', 
            'Refugio', 
            $refugioId, 
            "Creación de refugio: {$input['nombre_refugio']}"
        );
        
        echo json_encode(['success' => true, 'refugio_id' => $refugioId]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear refugio']);
    }
}

function getAuditLogs($limit, $offset) {
    global $db;
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT al.*, u.username, u.nombre_mostrado
            FROM AuditLog al
            LEFT JOIN Usuarios u ON al.usuario_id = u.usuario_id
            ORDER BY al.creado_en DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get audit logs error: " . $e->getMessage());
        return [];
    }
}
