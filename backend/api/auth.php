
<?php

require_once __DIR__ . '/../auth/Session.php';
require_once __DIR__ . '/../models/UserModel.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$session = Session::getInstance();
$userModel = new UserModel();

$method = $_SERVER['REQUEST_METHOD'];
$path = trim($_SERVER['PATH_INFO'] ?? '', '/');

try {
    switch ($method) {
        case 'POST':
            switch ($path) {
                case 'login':
                    handleLogin();
                    break;
                case 'logout':
                    handleLogout();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint no encontrado']);
            }
            break;
            
        case 'GET':
            switch ($path) {
                case 'me':
                    handleGetCurrentUser();
                    break;
                case 'csrf-token':
                    handleGetCSRFToken();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint no encontrado']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

function handleLogin() {
    global $session, $userModel;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Usuario y contraseña requeridos']);
        return;
    }
    
    // CSRF token validation for POST requests
    if (isset($input['csrf_token']) && !$session->validateCSRFToken($input['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Token CSRF inválido']);
        return;
    }
    
    $username = trim($input['username']);
    $password = $input['password'];
    
    $user = $userModel->authenticate($username, $password);
    
    if ($user) {
        $session->login($user);
        $userModel->logActivity($user['usuario_id'], 'LOGIN', 'Session', null, 'Login exitoso');
        
        echo json_encode([
            'success' => true,
            'message' => 'Login exitoso',
            'user' => [
                'usuario_id' => $user['usuario_id'],
                'username' => $user['username'],
                'rol' => $user['rol'],
                'refugio_id' => $user['refugio_id'],
                'nombre_mostrado' => $user['nombre_mostrado']
            ]
        ]);
    } else {
        // Log failed login attempt
        error_log("Failed login attempt for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales inválidas']);
    }
}

function handleLogout() {
    global $session, $userModel;
    
    if ($session->isLoggedIn()) {
        $user = $session->getUser();
        $userModel->logActivity($user['user_id'], 'LOGOUT', 'Session', null, 'Logout exitoso');
        $session->logout();
    }
    
    echo json_encode(['success' => true, 'message' => 'Logout exitoso']);
}

function handleGetCurrentUser() {
    global $session;
    
    if (!$session->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $session->getUser()
    ]);
}

function handleGetCSRFToken() {
    global $session;
    
    echo json_encode([
        'success' => true,
        'csrf_token' => $session->generateCSRFToken()
    ]);
}
