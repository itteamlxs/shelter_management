<?php
// config/auth.php - Configuración de autenticación

class AuthConfig {
    // Claves para JWT (en producción usar variables de entorno)
    const JWT_SECRET = 'your-super-secret-key-change-in-production';
    const JWT_ALGORITHM = 'HS256';
    const JWT_EXPIRY = 3600; // 1 hora
    const REFRESH_TOKEN_EXPIRY = 604800; // 7 días
    
    // Configuración de password hashing
    const PASSWORD_COST = 12;
    
    // Configuración de rate limiting
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_COOLDOWN = 900; // 15 minutos
}

// =============================================
// JWT Helper Class
// =============================================

class JWTHelper {
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => AuthConfig::JWT_ALGORITHM]);
        $payload = json_encode($payload);
        
        $headerEncoded = self::base64urlEncode($header);
        $payloadEncoded = self::base64urlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, AuthConfig::JWT_SECRET, true);
        $signatureEncoded = self::base64urlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }
    
    public static function decode($jwt) {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        $signature = self::base64urlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, AuthConfig::JWT_SECRET, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        $payload = json_decode(self::base64urlDecode($payloadEncoded), true);
        
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    private static function base64urlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64urlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

// =============================================
// Servicio de Autenticación
// =============================================

class AuthService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Autenticar usuario y generar tokens
     */
    public function authenticate($username, $password, $ip_address = null) {
        try {
            // Verificar intentos de login
            if ($this->isLoginBlocked($username, $ip_address)) {
                throw new Exception('Demasiados intentos de login. Intente más tarde.');
            }
            
            // Buscar usuario
            $stmt = $this->pdo->prepare('SELECT * FROM Usuarios WHERE username = ? AND activo = 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->recordFailedLogin($username, $ip_address);
                throw new Exception('Credenciales inválidas');
            }
            
            // Generar tokens
            $payload = [
                'usuario_id' => $user['usuario_id'],
                'username' => $user['username'],
                'rol' => $user['rol'],
                'refugio_id' => $user['refugio_id'],
                'nombre_mostrado' => $user['nombre_mostrado'],
                'iat' => time(),
                'exp' => time() + AuthConfig::JWT_EXPIRY
            ];
            
            $accessToken = JWTHelper::encode($payload);
            $refreshToken = $this->generateRefreshToken($user['usuario_id']);
            
            // Actualizar último login
            $stmt = $this->pdo->prepare('UPDATE Usuarios SET ultimo_login = NOW() WHERE usuario_id = ?');
            $stmt->execute([$user['usuario_id']]);
            
            // Limpiar intentos fallidos
            $this->clearFailedLogins($username, $ip_address);
            
            // Auditoría
            $this->logActivity($user['usuario_id'], 'LOGIN', 'Usuarios', $user['usuario_id'], 'Login exitoso', $ip_address);
            
            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => AuthConfig::JWT_EXPIRY,
                'user' => [
                    'usuario_id' => $user['usuario_id'],
                    'username' => $user['username'],
                    'rol' => $user['rol'],
                    'refugio_id' => $user['refugio_id'],
                    'nombre_mostrado' => $user['nombre_mostrado']
                ]
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Validar token de acceso
     */
    public function validateToken($token) {
        try {
            $payload = JWTHelper::decode($token);
            
            if (!$payload) {
                return false;
            }
            
            // Verificar que el usuario sigue activo
            $stmt = $this->pdo->prepare('SELECT activo FROM Usuarios WHERE usuario_id = ?');
            $stmt->execute([$payload['usuario_id']]);
            $user = $stmt->fetch();
            
            if (!$user || !$user['activo']) {
                return false;
            }
            
            return $payload;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Refrescar token usando refresh token
     */
    public function refreshToken($refreshToken, $ip_address = null) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT rt.*, u.* FROM RefreshTokens rt 
                JOIN Usuarios u ON rt.usuario_id = u.usuario_id 
                WHERE rt.token_hash = ? AND rt.expires_at > NOW() AND rt.revoked = 0 AND u.activo = 1
            ');
            $stmt->execute([hash('sha256', $refreshToken)]);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception('Refresh token inválido o expirado');
            }
            
            // Revocar el refresh token usado
            $stmt = $this->pdo->prepare('UPDATE RefreshTokens SET revoked = 1 WHERE token_id = ?');
            $stmt->execute([$result['token_id']]);
            
            // Generar nuevos tokens
            $payload = [
                'usuario_id' => $result['usuario_id'],
                'username' => $result['username'],
                'rol' => $result['rol'],
                'refugio_id' => $result['refugio_id'],
                'nombre_mostrado' => $result['nombre_mostrado'],
                'iat' => time(),
                'exp' => time() + AuthConfig::JWT_EXPIRY
            ];
            
            $newAccessToken = JWTHelper::encode($payload);
            $newRefreshToken = $this->generateRefreshToken($result['usuario_id']);
            
            // Auditoría
            $this->logActivity($result['usuario_id'], 'TOKEN_REFRESH', 'RefreshTokens', $result['token_id'], 'Token refrescado', $ip_address);
            
            return [
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => AuthConfig::JWT_EXPIRY
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Cerrar sesión (revocar refresh tokens)
     */
    public function logout($userId, $ip_address = null) {
        try {
            $stmt = $this->pdo->prepare('UPDATE RefreshTokens SET revoked = 1 WHERE usuario_id = ? AND revoked = 0');
            $stmt->execute([$userId]);
            
            // Auditoría
            $this->logActivity($userId, 'LOGOUT', 'RefreshTokens', $userId, 'Logout exitoso', $ip_address);
            
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Verificar autorización por rol
     */
    public function hasPermission($userPayload, $requiredRole, $resourceRefugioId = null) {
        $userRole = $userPayload['rol'];
        $userRefugioId = $userPayload['refugio_id'];
        
        switch ($requiredRole) {
            case 'Administrador':
                return $userRole === 'Administrador';
                
            case 'Refugio':
                if ($userRole === 'Administrador') return true;
                if ($userRole === 'Refugio') {
                    // Si se especifica un refugio, verificar que sea el del usuario
                    return $resourceRefugioId === null || $userRefugioId == $resourceRefugioId;
                }
                return false;
                
            case 'Auditor':
                return in_array($userRole, ['Administrador', 'Auditor']);
                
            default:
                return false;
        }
    }
    
    // =============================================
    // Métodos Privados
    // =============================================
    
    private function generateRefreshToken($userId) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        
        $stmt = $this->pdo->prepare('
            INSERT INTO RefreshTokens (usuario_id, token_hash, expires_at) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
        ');
        $stmt->execute([$userId, $tokenHash, AuthConfig::REFRESH_TOKEN_EXPIRY]);
        
        return $token;
    }
    
    private function isLoginBlocked($username, $ip_address) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as attempts FROM LoginAttempts 
            WHERE (username = ? OR ip_address = ?) 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            AND success = 0
        ');
        $stmt->execute([$username, $ip_address, AuthConfig::LOGIN_COOLDOWN]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= AuthConfig::MAX_LOGIN_ATTEMPTS;
    }
    
    private function recordFailedLogin($username, $ip_address) {
        $stmt = $this->pdo->prepare('
            INSERT INTO LoginAttempts (username, ip_address, success, attempted_at) 
            VALUES (?, ?, 0, NOW())
        ');
        $stmt->execute([$username, $ip_address]);
    }
    
    private function clearFailedLogins($username, $ip_address) {
        $stmt = $this->pdo->prepare('
            DELETE FROM LoginAttempts 
            WHERE (username = ? OR ip_address = ?) 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ');
        $stmt->execute([$username, $ip_address, AuthConfig::LOGIN_COOLDOWN]);
    }
    
    private function logActivity($userId, $action, $object, $objectId, $summary, $ip_address) {
        $stmt = $this->pdo->prepare('
            INSERT INTO AuditLog (usuario_id, rol, accion, objeto, objeto_id, resumen, ip_origen, creado_en) 
            SELECT ?, rol, ?, ?, ?, ?, ?, NOW() FROM Usuarios WHERE usuario_id = ?
        ');
        $stmt->execute([$userId, $action, $object, $objectId, $summary, $ip_address, $userId]);
    }
}

// =============================================
// Middleware de Autenticación
// =============================================

class AuthMiddleware {
    private $authService;
    
    public function __construct($authService) {
        $this->authService = $authService;
    }
    
    /**
     * Middleware para verificar autenticación
     */
    public function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->unauthorized('Token de acceso requerido');
        }
        
        $token = $matches[1];
        $payload = $this->authService->validateToken($token);
        
        if (!$payload) {
            $this->unauthorized('Token inválido o expirado');
        }
        
        // Almacenar usuario en variable global o contexto
        $_SESSION['user'] = $payload;
        return $payload;
    }
    
    /**
     * Middleware para verificar autorización
     */
    public function authorize($requiredRole, $resourceRefugioId = null) {
        $user = $_SESSION['user'] ?? null;
        
        if (!$user) {
            $this->unauthorized('No autenticado');
        }
        
        if (!$this->authService->hasPermission($user, $requiredRole, $resourceRefugioId)) {
            $this->forbidden('Acceso denegado');
        }
        
        return $user;
    }
    
    private function unauthorized($message) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized', 'message' => $message]);
        exit;
    }
    
    private function forbidden($message) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Forbidden', 'message' => $message]);
        exit;
    }
}

// =============================================
// Controlador de Autenticación
// =============================================

class AuthController {
    private $authService;
    
    public function __construct($authService) {
        $this->authService = $authService;
    }
    
    /**
     * POST /auth/login
     */
    public function login() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['username']) || !isset($input['password'])) {
                throw new Exception('Username y password son requeridos');
            }
            
            $username = trim($input['username']);
            $password = $input['password'];
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            
            if (empty($username) || empty($password)) {
                throw new Exception('Username y password no pueden estar vacíos');
            }
            
            $result = $this->authService->authenticate($username, $password, $ip_address);
            
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * POST /auth/refresh
     */
    public function refresh() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['refresh_token'])) {
                throw new Exception('Refresh token requerido');
            }
            
            $refreshToken = $input['refresh_token'];
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $result = $this->authService->refreshToken($refreshToken, $ip_address);
            
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Token refresh failed',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * POST /auth/logout
     */
    public function logout() {
        try {
            $authMiddleware = new AuthMiddleware($this->authService);
            $user = $authMiddleware->authenticate();
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $this->authService->logout($user['usuario_id'], $ip_address);
            
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Logout exitoso'
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Logout failed',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * GET /auth/profile
     */
    public function profile() {
        try {
            $authMiddleware = new AuthMiddleware($this->authService);
            $user = $authMiddleware->authenticate();
            
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'usuario_id' => $user['usuario_id'],
                    'username' => $user['username'],
                    'rol' => $user['rol'],
                    'refugio_id' => $user['refugio_id'],
                    'nombre_mostrado' => $user['nombre_mostrado']
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Profile access failed',
                'message' => $e->getMessage()
            ]);
        }
    }
}

// =============================================
// Utilidades de Respuesta API
// =============================================

class ApiResponse {
    public static function success($data = null, $message = null, $meta = null) {
        $response = ['success' => true];
        
        if ($data !== null) $response['data'] = $data;
        if ($message !== null) $response['message'] = $message;
        if ($meta !== null) $response['meta'] = $meta;
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    public static function error($message, $code = 400, $details = null) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'error' => true,
            'message' => $message
        ];
        
        if ($details !== null) $response['details'] = $details;
        
        echo json_encode($response);
    }
    
    public static function paginated($data, $page, $perPage, $total) {
        $totalPages = ceil($total / $perPage);
        
        $meta = [
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => (int)$total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
        
        self::success($data, null, $meta);
    }
}

// =============================================
// Validador de Entrada
// =============================================

class InputValidator {
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $error = self::validateRule($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return empty($errors) ? null : $errors;
    }
    
    private static function validateRule($field, $value, $rule) {
        switch ($rule) {
            case 'required':
                return empty($value) ? "El campo {$field} es requerido" : null;
                
            case 'email':
                return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "El campo {$field} debe ser un email válido" : null;
                
            case 'numeric':
                return !is_numeric($value) ? "El campo {$field} debe ser numérico" : null;
                
            case 'date':
                return !self::isValidDate($value) ? "El campo {$field} debe ser una fecha válida (YYYY-MM-DD)" : null;
                
            case 'time':
                return !self::isValidTime($value) ? "El campo {$field} debe ser una hora válida (HH:MM:SS)" : null;
                
            default:
                if (strpos($rule, 'max:') === 0) {
                    $max = (int)substr($rule, 4);
                    return strlen($value) > $max ? "El campo {$field} no puede tener más de {$max} caracteres" : null;
                }
                
                if (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    return strlen($value) < $min ? "El campo {$field} debe tener al menos {$min} caracteres" : null;
                }
                
                if (strpos($rule, 'in:') === 0) {
                    $options = explode(',', substr($rule, 3));
                    return !in_array($value, $options) ? "El campo {$field} debe ser uno de: " . implode(', ', $options) : null;
                }
                
                return null;
        }
    }
    
    private static function isValidDate($date) {
        return DateTime::createFromFormat('Y-m-d', $date) !== false;
    }
    
    private static function isValidTime($time) {
        return DateTime::createFromFormat('H:i:s', $time) !== false;
    }
}

?>