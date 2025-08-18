
<?php
/**
 * CSV Upload API Endpoint
 * Sistema de Refugios - Phase 3
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../auth/Session.php';
require_once __DIR__ . '/../models/UploadModel.php';

$session = Session::getInstance();

// Check authentication
if (!$session->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$user = $session->getUser();

// Only Admin and Refugio users can upload
if (!in_array($user['rol'], ['Administrador', 'Refugio'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos para subir archivos']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $uploadModel = new UploadModel();
        
        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$session->validateCSRFToken($csrfToken)) {
            http_response_code(400);
            echo json_encode(['error' => 'Token CSRF inválido']);
            exit;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No se subió ningún archivo válido']);
            exit;
        }
        
        $file = $_FILES['csv_file'];
        
        // Validate file type
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);
        
        if (!in_array($mimeType, $allowedTypes) && !str_ends_with($file['name'], '.csv')) {
            http_response_code(400);
            echo json_encode(['error' => 'Solo se permiten archivos CSV']);
            exit;
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'El archivo es demasiado grande (máximo 5MB)']);
            exit;
        }
        
        // Get refugio_id if user is Refugio role
        $refugio_id = null;
        if ($user['rol'] === 'Refugio') {
            $refugio_id = $user['refugio_id'] ?? null;
            if (!$refugio_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Usuario de refugio sin refugio asignado']);
                exit;
            }
        } else {
            // Admin can specify refugio_id
            $refugio_id = $_POST['refugio_id'] ?? null;
            if (!$refugio_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Debe especificar un refugio']);
                exit;
            }
        }
        
        // Process CSV upload
        $result = $uploadModel->processCSVUpload(
            $file['tmp_name'],
            $file['name'],
            $user['id'],
            $refugio_id
        );
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Archivo procesado exitosamente',
                'data' => $result['data']
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => $result['error'],
                'details' => $result['details'] ?? null
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    error_log("Upload API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
