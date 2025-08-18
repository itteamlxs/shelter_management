
<?php
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }
    
    $username = trim($input['username']);
    $password = $input['password'];
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password cannot be empty']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Get user data
    $stmt = $db->prepare("
        SELECT usuario_id, username, password_hash, rol, refugio_id, nombre_mostrado, activo
        FROM Usuarios 
        WHERE username = ? AND activo = TRUE
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        // Audit failed login attempt
        $audit_stmt = $db->prepare("
            INSERT INTO AuditLog (usuario_id, rol, accion, objeto, resumen, ip_origen, user_agent)
            VALUES (NULL, 'Sistema', 'LOGIN_FAILED', 'Auth', ?, ?, ?)
        ");
        $audit_stmt->execute([
            "Failed login attempt for username: $username",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    
    // Update last login
    $update_stmt = $db->prepare("UPDATE Usuarios SET ultimo_login = NOW() WHERE usuario_id = ?");
    $update_stmt->execute([$user['usuario_id']]);
    
    // Generate JWT token
    $token = Auth::generateToken($user);
    
    // Audit successful login
    $audit_stmt = $db->prepare("
        INSERT INTO AuditLog (usuario_id, rol, accion, objeto, resumen, ip_origen, user_agent)
        VALUES (?, ?, 'LOGIN_SUCCESS', 'Auth', ?, ?, ?)
    ");
    $audit_stmt->execute([
        $user['usuario_id'],
        $user['rol'],
        "Successful login for user: $username",
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'usuario_id' => $user['usuario_id'],
            'username' => $user['username'],
            'rol' => $user['rol'],
            'refugio_id' => $user['refugio_id'],
            'nombre_mostrado' => $user['nombre_mostrado']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
